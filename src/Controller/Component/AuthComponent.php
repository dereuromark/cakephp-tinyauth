<?php

namespace TinyAuth\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent as CakeAuthComponent;
use Cake\Core\Exception\Exception;
use Cake\Event\Event;
use InvalidArgumentException;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowAdapter\AllowAdapterInterface;
use TinyAuth\Auth\AllowAdapter\IniAllowAdapter;
use TinyAuth\Auth\AllowTrait;

/**
 * TinyAuth AuthComponent to handle all authentication in a central ini file.
 *
 * @property \Cake\Controller\Component\RequestHandlerComponent $RequestHandler
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class AuthComponent extends CakeAuthComponent {

	use AclTrait;
	use AllowTrait;

	/**
	 * @var array
	 */
	protected $_defaultTinyAuthConfig = [
		'allowAdapter' => IniAllowAdapter::class,
		'cache' => '_cake_core_',
		'autoClearCache' => null, // Set to true to delete cache automatically in debug mode, keep null for auto-detect
		'allowCacheKey' => 'tinyauth_allow',
		'allowFilePath' => null, // Possible to locate ini file at given path e.g. Plugin::configPath('Admin'), filePath is also available for shared config
		'allowFile' => 'tinyauth_allow.ini',
	];

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array $config
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += $this->_defaultTinyAuthConfig;

		parent::__construct($registry, $config);
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
		$rule = $this->_getAllowRule($this->_registry->getController()->getRequest()->getAttribute('params'));
		if (!$rule) {
			return;
		}

		if (in_array('*', $rule['allow'], true)) {
			$this->allow();
		} elseif (!empty($rule['allow'])) {
			$this->allow($rule['allow']);
		}
		if (in_array('*', $rule['deny'], true)) {
			$this->deny();
		} elseif (!empty($rule['deny'])) {
			$this->deny($rule['deny']);
		}
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
