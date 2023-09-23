<?php

namespace TinyAuth\Controller\Component;

use Cake\Controller\ComponentRegistry;
use RuntimeException;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;
use TinyAuth\Utility\Config;

/**
 * TinyAuth AuthComponent to handle all authentication in a central INI file.
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class AuthComponent extends LegacyAuthComponent {

	use AclTrait;
	use AllowTrait;

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array $config
	 * @throws \RuntimeException
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += Config::all();
		if ($config && empty($config['className'])) {
			$config['className'] = 'TinyAuth.Auth';
		}

		parent::__construct($registry, $config);
		if ($registry->has('Authentication') && get_class($registry->get('Authentication')) === AuthenticationComponent::class) {
			throw new RuntimeException('You cannot use new TinyAuth.Authentication component and this TinyAuth.Auth component together.');
		}
		if ($registry->has('Authorization') && get_class($registry->get('Authorization')) === AuthorizationComponent::class) {
			throw new RuntimeException('You cannot use new TinyAuth.Authorization component and this TinyAuth.Auth component together.');
		}
	}

	/**
	 * @param array $config The config data.
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$params = $this->_registry->getController()->getRequest()->getAttribute('params');
		$this->_prepareAuthentication($params);
	}

	/**
	 * @param array $params
	 * @return void
	 */
	protected function _prepareAuthentication(array $params) {
		$rule = $this->_getAllowRule($params);
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

}
