<?php

namespace TinyAuth\View\Helper;

use Cake\Core\Exception\CakeException;
use Cake\Routing\Router;
use Cake\View\Helper;
use Cake\View\View;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;
use TinyAuth\Utility\Config;

class AuthenticationHelper extends Helper {

	use AclTrait;
	use AllowTrait;

	/**
	 * @var array
	 */
	protected array $helpers = [];

	/**
	 * @param \Cake\View\View $View The View this helper is being attached to.
	 * @param array $config Configuration settings for the helper.
	 */
	public function __construct(View $View, array $config = []) {
		$config += Config::all();

		parent::__construct($View, $config);
	}

	/**
	 * Checks if a given URL is public.
	 *
	 * If no URL is given it will default to the current request URL.
	 *
	 * @param array $url
	 * @return bool
	 */
	public function isPublic(array $url = []) {
		if (!$url) {
			$url = $this->_View->getRequest()->getAttribute('params');
		}

		if (isset($url['_name'])) {
			//throw MissingRouteException if necessary
			Router::url($url);
			$routes = Router::getRouteCollection()->named();
			$defaults = $routes[$url['_name']]->defaults;
			if (!isset($defaults['action']) || !isset($defaults['controller'])) {
				throw new CakeException('Controller or action name could not be null.');
			}
			$url = [
				'prefix' => !empty($defaults['prefix']) ? $defaults['prefix'] : null,
				'plugin' => !empty($defaults['plugin']) ? $defaults['plugin'] : null,
				'controller' => $defaults['controller'],
				'action' => $defaults['action'],
			];
		} else {
			$params = $this->_View->getRequest()->getAttribute('params');
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

}
