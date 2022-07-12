<?php

namespace TinyAuth\Controller\Component;

use Authentication\Controller\Component\AuthenticationComponent as CakeAuthenticationComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Exception\Exception;
use Cake\Routing\Router;
use RuntimeException;
use TinyAuth\Auth\AllowTrait;
use TinyAuth\Utility\Config;

/**
 * TinyAuth AuthenticationComponent to handle all authentication in a central INI file.
 *
 * Make sure you have the new Authentication plugin installed if you want to use this component.
 * Otherwise, just use the Auth component.
 *
 * @link https://github.com/cakephp/authentication
 */
class AuthenticationComponent extends CakeAuthenticationComponent {

	use AllowTrait;

	/**
	 * @param \Cake\Controller\ComponentRegistry $registry
	 * @param array $config
	 * @throws \RuntimeException
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$config += Config::all();

		parent::__construct($registry, $config);

		if ($registry->has('Auth') && get_class($registry->get('Auth')) === AuthComponent::class) {
			throw new RuntimeException('You cannot use TinyAuth.Authentication component and former TinyAuth.Auth component together.');
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function startup(): void {
		$this->_prepareAuthentication();

		parent::startup();
	}

	/**
	 * Checks if a given URL is public.
	 *
	 * If no URL is given it will default to the current request URL.
	 *
	 * @param array $url
	 * @throws \Cake\Core\Exception\Exception
	 * @return bool
	 */
	public function isPublic(array $url = []) {
		if (!$url) {
			$url = $this->getController()->getRequest()->getAttribute('params');
		}

		if (isset($url['_name'])) {
			//throw MissingRouteException if necessary
			Router::url($url);
			$routes = Router::getRouteCollection()->named();
			$defaults = $routes[$url['_name']]->defaults;
			if (!isset($defaults['action']) || !isset($defaults['controller'])) {
				throw new Exception('Controller or action name could not be null.');
			}
			$url = [
				'prefix' => !empty($defaults['prefix']) ? $defaults['prefix'] : null,
				'plugin' => !empty($defaults['plugin']) ? $defaults['plugin'] : null,
				'controller' => $defaults['controller'],
				'action' => $defaults['action'],
			];
		} else {
			$params = $this->getController()->getRequest()->getAttribute('params');
			$url += [
				'prefix' => !empty($params['prefix']) ? $params['prefix'] : null,
				'plugin' => !empty($params['plugin']) ? $params['plugin'] : null,
				'controller' => $params['controller'],
				'action' => 'index',
			];
		}

		$rule = $this->_getAllowRule($url);

		return $this->_isActionAllowed($rule, $url['action']);
	}

	/**
	 * @return void
	 */
	protected function _prepareAuthentication() {
		$params = $this->_registry->getController()->getRequest()->getAttribute('params');
		if ( !isset($params['plugin']) ) {
			$params['plugin'] = NULL;
		}
		if ( !isset($params['prefix']) ) {
			$params['prefix'] = NULL;
		}

		$rule = $this->_getAllowRule($params);

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
