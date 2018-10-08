<?php

namespace TinyAuth\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent as CakeAuthComponent;
use Cake\Core\Configure;
use Cake\Event\Event;
use TinyAuth\Auth\AclTrait;

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
		'cache' => '_cake_core_',
		'cacheKey' => 'tiny_auth_allow',
		'autoClearCache' => null, // Set to true to delete cache automatically in debug mode, keep null for auto-detect
		'authFilePath' => null, // Possible to locate ini file at given path e.g. Plugin::configPath('Admin')
		'authFile' => 'auth_allow.ini',
	];

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array $config
	 * @throws \Cake\Core\Exception\Exception
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += $this->_defaultTinyAuthConfig;

		parent::__construct($registry, $config);
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
	 * @param \Cake\Event\Event $event Event instance.
	 * @return \Cake\Http\Response|null
	 */
	public function authCheck(Event $event) {
		$this->_prepareAuthentication();

		return parent::authCheck($event);
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
		$authentication = $this->_getAuth($this->getConfig('authFilePath'));

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

			if ($rule['actions'] === []) {
				$this->allow();
				return;
			}

			$this->allow($rule['actions']);
		}
	}

	/**
	 * Parse ini file and returns the allowed actions.
	 *
	 * Uses cache for maximum performance.
	 *
	 * @param string|null $path
	 * @return array Actions
	 */
	protected function _getAuth($path = null) {
		if ($this->getConfig('autoClearCache') && Configure::read('debug')) {
			Cache::delete($this->getConfig('cacheKey'), $this->getConfig('cache'));
		}
		$roles = Cache::read($this->getConfig('cacheKey'), $this->getConfig('cache'));
		if ($roles !== false) {
			return $roles;
		}

		if ($path === null) {
			$path = $this->getConfig('filePath');
		}
		$iniArray = $this->_parseFiles($path, $this->getConfig('authFile'));

		$res = [];
		foreach ($iniArray as $key => $actions) {
			$res[$key] = $this->_deconstructIniKey($key);
			$res[$key]['map'] = $actions;

			$actions = explode(',', $actions);

			if (in_array('*', $actions)) {
				$res[$key]['actions'] = [];
				continue;
			}

			foreach ($actions as $action) {
				$action = trim($action);
				if (!$action) {
					continue;
				}

				$res[$key]['actions'][] = $action;
			}
		}

		Cache::write($this->getConfig('cacheKey'), $res, $this->getConfig('cache'));
		return $res;
	}

}
