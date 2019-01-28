<?php

namespace TinyAuth\Controller\Component;

use Authentication\Controller\Component\AuthenticationComponent as CakeAuthenticationComponent;
use Cake\Controller\ComponentRegistry;
use TinyAuth\Auth\AllowAdapter\IniAllowAdapter;
use TinyAuth\Auth\AllowTrait;

/**
 * TinyAuth AuthenticationComponent to handle all authentication in a central ini file.
 */
class AuthenticationComponent extends CakeAuthenticationComponent {

	use AllowTrait;

	/**
	 * @var array
	 */
	protected $_defaultTinyAuthConfig = [
		'allowAdapter' => IniAllowAdapter::class,
		'cache' => '_cake_core_',
		'autoClearCache' => null, // Set to true to delete cache automatically in debug mode, keep null for auto-detect
		'allowCacheKey' => 'tiny_auth_allow',
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
	 * {inheritDoc}
	 *
	 * @return void
	 */
	public function startup() {
		$this->_prepareAuthentication();

		parent::startup();
	}

	/**
	 * @return void
	 */
	protected function _prepareAuthentication() {
		$rule = $this->_getRule($this->_registry->getController()->getRequest()->getAttribute('params'));
		if (!$rule) {
			return;
		}

		if (in_array('*', $rule['deny'], true)) {
			return;
		}

		$allowed = [];
		if (in_array('*', $rule['allow'], true)) {
			$allowed = $this->_getAllActions();
		} elseif (!empty($rule['allow'])) {
			$allowed = $rule['allow'];
		}
		if (!empty($rule['deny'])) {
			$allowed = array_diff($allowed, $rule['deny']);
		}

		if (!$allowed) {
			return;
		}

		/*
		if ($allowAll) {
			$this->setConfig('requireIdentity', false);
			return;
		}
		*/

		$this->allowUnauthenticated($allowed);
	}

	/**
	 * @param array $params
	 * @return array
	 */
	protected function _getRule(array $params) {
		$rules = $this->_getAllow($this->getConfig('allowFilePath'));
		foreach ($rules as $rule) {
			if ($params['plugin'] && $params['plugin'] !== $rule['plugin']) {
				continue;
			}
			if (!empty($params['prefix']) && $params['prefix'] !== $rule['prefix']) {
				continue;
			}
			if ($params['controller'] !== $rule['controller']) {
				continue;
			}

			return $rule;
		}

		return [];
	}

	/**
	 * @return array
	 */
	protected function _getAllActions() {
		$controller = $this->_registry->getController();

		return get_class_methods($controller);
	}

}
