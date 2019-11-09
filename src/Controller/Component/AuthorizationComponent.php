<?php

namespace TinyAuth\Controller\Component;

use Authorization\Controller\Component\AuthorizationComponent as CakeAuthorizationComponent;
use Cake\Controller\ComponentRegistry;
use RuntimeException;
use TinyAuth\Auth\AclTrait;
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

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array $config
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += Config::all();

		parent::__construct($registry, $config);

		if ($registry->has('Auth') && get_class($registry->get('Auth')) === AuthComponent::class) {
			throw new RuntimeException('You cannot use TinyAuth.Authorization component and former TinyAuth.Auth component together.');
		}
	}

	/**
	 * Action authorization handler.
	 *
	 * Checks identity and model authorization.
	 *
	 * @return void
	 */
	public function authorizeAction() {
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

}
