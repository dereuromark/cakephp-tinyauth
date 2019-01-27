<?php

namespace TinyAuth\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent as CakeAuthComponent;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Event\Event;
use InvalidArgumentException;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowAdapter\AllowAdapterInterface;
use TinyAuth\Auth\AllowAdapter\IniAllowAdapter;

/**
 * TinyAuth AuthComponent to handle all authentication in a central ini file.
 *
 * @property \Cake\Controller\Component\RequestHandlerComponent $RequestHandler
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class AuthComponent extends CakeAuthComponent {

	use AclTrait;

	/**
	 * @var array
	 */
	protected $_defaultTinyAuthConfig = [
		'allowAdapter' => IniAllowAdapter::class,
		'cache' => '_cake_core_',
		'autoClearCache' => null, // Set to true to delete cache automatically in debug mode, keep null for auto-detect
		'allowCacheKey' => 'tiny_auth_allow',
		'allowFilePath' => null, // Possible to locate ini file at given path e.g. Plugin::configPath('Admin'), filePath is also available for shared config
		'allowFile' => 'auth_allow.ini',
	];

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array $config
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += $this->_defaultTinyAuthConfig;

		parent::__construct($registry, $config);

		// BC config check
		if ($this->getConfig('cacheKey')) {
			$this->setConfig('allowCacheKey', $this->getConfig('cacheKey'));
		}
		if ($this->getConfig('file')) {
			$this->setConfig('allowFile', $this->getConfig('file'));
		}
		if ($this->getConfig('filePath')) {
			$this->setConfig('allowFilePath', $this->getConfig('filePath'));
		}
	}

	/**
	 * @param array $config The config data.
	 * @return void
	 */
	public function initialize(array $config) {
		parent::initialize($config);

		$this->_prepareAuthentication();
	}

	/**
	 * Events supported by this component.
	 *
	 * @return array
	 */
	public function implementedEvents() {
		return [
			'Controller.beforeRender' => 'beforeRender',
		] + parent::implementedEvents();
	}

	/**
	 * @param \Cake\Event\Event $event
	 * @return \Cake\Http\Response|null
	 */
	public function beforeRender(Event $event) {
		/** @var \Cake\Controller\Controller $controller */
		$controller = $event->getSubject();

		$authUser = (array)$this->user();
		$controller->set('_authUser', $authUser);
	}

	/**
	 * @return void
	 */
	protected function _prepareAuthentication() {
		$authentication = $this->_getAuth($this->getConfig('allowFilePath'));

		$params = $this->request->getAttribute('params');
		foreach ($authentication as $rule) {
			if ($params['plugin'] && $params['plugin'] !== $rule['plugin']) {
				continue;
			}
			if (!empty($params['prefix']) && $params['prefix'] !== $rule['prefix']) {
				continue;
			}
			if ($params['controller'] !== $rule['controller']) {
				continue;
			}

			if ($rule['all']) {
				$this->allow();
			} elseif (!empty($rule['allow'])) {
				$this->allow($rule['allow']);
			}
			if (!empty($rule['deny'])) {
				$this->deny($rule['deny']);
			}
		}
	}

	/**
	 * Parse ini file and returns the allowed actions.
	 *
	 * Uses cache for maximum performance.
	 *
	 * @param string|null $path
	 * @return array
	 */
	protected function _getAuth($path = null) {
		if ($this->getConfig('autoClearCache') && Configure::read('debug')) {
			Cache::delete($this->getConfig('allowCacheKey'), $this->getConfig('cache'));
		}
		$auth = Cache::read($this->getConfig('allowCacheKey'), $this->getConfig('cache'));
		if ($auth !== false) {
			return $auth;
		}

		if ($path === null) {
			$path = $this->getConfig('allowFilePath');
		}

		$config = $this->getConfig();
		$config['filePath'] = $path;
		$config['file'] = $config['allowFile'];
		unset($config['allowFilePath']);
		unset($config['allowFile']);

		$auth = $this->_loadAllowAdapter($config['allowAdapter'])->getAllow($this->_getAvailableRoles(), $config);

		Cache::write($this->getConfig('allowCacheKey'), $auth, $this->getConfig('cache'));
		return $auth;
	}

	/**
	 * Finds the authentication adapter to use for this request.
	 *
	 * @param string $adapter Acl adapter to load.
	 * @return \TinyAuth\Auth\AllowAdapter\AllowAdapterInterface
	 * @throws \Cake\Core\Exception\Exception
	 * @throws \InvalidArgumentException
	 */
	protected function _loadAllowAdapter($adapter) {
		if (!class_exists($adapter)) {
			throw new Exception(sprintf('The Acl Adapter class "%s" was not found.', $adapter));
		}

		$adapterInstance = new $adapter();
		if (!($adapterInstance instanceof AllowAdapterInterface)) {
			throw new InvalidArgumentException(sprintf(
				'TinyAuth Acl adapters have to implement %s.', AllowAdapterInterface::class
			));
		}

		return $adapterInstance;
	}

}
