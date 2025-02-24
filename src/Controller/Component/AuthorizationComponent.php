<?php

namespace TinyAuth\Controller\Component;

use Authorization\Controller\Component\AuthorizationComponent as CakeAuthorizationComponent;
use Cake\Controller\ComponentRegistry;
use RuntimeException;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;
use TinyAuth\Utility\Config;

/**
 * TinyAuth AuthorizationComponent to handle all authorization in a central INI file.
 *
 * Make sure you have the new Authorization plugin installed if you want to use this component.
 * Otherwise, just use the Auth component.
 *
 * @link https://github.com/cakephp/authorization
 */
class AuthorizationComponent extends CakeAuthorizationComponent {

	use AclTrait;
	use AllowTrait;

	/**
	 * @var \TinyAuth\Controller\Component\AuthenticationComponent|null
	 */
	protected $_authentication;

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array<string, mixed> $config
	 * @throws \RuntimeException
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += Config::all();

		parent::__construct($registry, $config);

		if ($registry->has('Auth') && get_class($registry->get('Auth')) === AuthComponent::class) {
			throw new RuntimeException('You cannot use TinyAuth.Authorization component and former TinyAuth.Auth component together.');
		}

		if ($registry->getController()->components()->has('Authentication')) {
			/** @var \TinyAuth\Controller\Component\AuthenticationComponent $authentication */
			$authentication = $registry->getController()->components()->get('Authentication');
			$this->_authentication = $authentication;
		}
	}

	/**
	 * Action authorization handler.
	 *
	 * Checks identity and model authorization.
	 *
	 * @return void
	 */
	public function authorizeAction(): void {
		if ($this->_isUnauthenticatedAction()) {
			$this->skipAuthorization();

			return;
		}

		$request = $this->getController()->getRequest();

		$action = $request->getParam('action');
		$skipAuthorization = $this->checkAction($action, 'skipAuthorization');
		if ($skipAuthorization) {
			$this->skipAuthorization();

			return;
		}

		$this->authorize($request, 'access');

		parent::authorizeAction();
	}

	/**
	 * Is public already thanks to Authentication component.
	 *
	 * @return bool
	 */
	protected function _isUnauthenticatedAction() {
		if ($this->_authentication === null) {
			return false;
		}

		$unauthenticatedActions = $this->_authentication->getUnauthenticatedActions();
		$request = $this->getController()->getRequest();

		$action = $request->getParam('action');

		return in_array($action, $unauthenticatedActions, true);
	}

}
