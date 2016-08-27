<?php

namespace TinyAuth\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent as CakeAuthComponent;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Event\Event;
use TinyAuth\Utility\Utility;

/**
 * TinyAuth AuthComponent to handle all authentication in a central ini file.
 */
class AuthComponent extends CakeAuthComponent {

	/**
	 * @var array
	 */
	protected $_defaultTinyAuthConfig = [
		'cache' => '_cake_core_',
		'cacheKey' => 'tiny_auth_allow',
		'autoClearCache' => false, // Set to true to delete cache automatically in debug mode
		'filePath' => null, // Possible to locate ini file at given path e.g. Plugin::configPath('Admin')
		'file' => 'auth_allow.ini',
	];

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array $config
	 * @throws \Cake\Core\Exception\Exception
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += $this->_defaultTinyAuthConfig;

		parent::__construct($registry, $config);

		if (!in_array($config['cache'], Cache::configured())) {
			throw new Exception(sprintf('Invalid TinyAuth cache `%s`', $config['cache']));
		}
	}

	/**
	 * @param \Cake\Event\Event $event Event instance.
	 * @return \Cake\Network\Response|null
	 */
	public function startup(Event $event) {
		$this->_prepareAuthentication();

		return parent::startup($event);
	}

	/**
	 * @return void
	 */
	protected function _prepareAuthentication() {
		$authentication = $this->_getAuth($this->_config['filePath']);

		$params = $this->request->params;
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
		if ($path === null) {
			$path = ROOT . DS . 'config' . DS;
		}

		if ($this->_config['autoClearCache'] && Configure::read('debug')) {
			Cache::delete($this->_config['cacheKey'], $this->_config['cache']);
		}
		$roles = Cache::read($this->_config['cacheKey'], $this->_config['cache']);
		if ($roles !== false) {
			return $roles;
		}

		$iniArray = Utility::parseFile($path . $this->_config['file']);

		$res = [];
		foreach ($iniArray as $key => $actions) {
			$res[$key] = Utility::deconstructIniKey($key);
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

		Cache::write($this->_config['cacheKey'], $res, $this->_config['cache']);
		return $res;
	}

}
