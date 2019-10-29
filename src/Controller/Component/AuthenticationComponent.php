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
		'autoClearCache' => null, // Set to true to delete cache automatically in debug mode, keep null for auto-detect
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
	}

	/**
	 * {@inheritDoc}
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
		$rule = $this->_getAllowRule($this->_registry->getController()->getRequest()->getAttribute('params'));
		if (!$rule) {
			return;
		}

		if (in_array('*', $rule['deny'], true)) {
			return;
		}

		$allowed = $this->unauthenticatedActions;
		if (in_array('*', $rule['allow'], true)) {
			$allowed = $this->_getAllActions();
		} elseif (!empty($rule['allow'])) {
			$allowed = array_merge($allowed, $rule['allow']);
		}
		if (!empty($rule['deny'])) {
			$allowed = array_diff($allowed, $rule['deny']);
		}

		if (!$allowed) {
			return;
		}

		$this->allowUnauthenticated($allowed);
	}

	/**
	 * @return array
	 */
	protected function _getAllActions() {
		$controller = $this->_registry->getController();

		return get_class_methods($controller);
	}

}
