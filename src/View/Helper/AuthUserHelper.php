<?php

namespace TinyAuth\View\Helper;

use ArrayAccess;
use Authentication\Identity;
use Cake\Core\Exception\CakeException;
use Cake\Routing\Router;
use Cake\View\Helper;
use Cake\View\View;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;
use TinyAuth\Auth\AuthUserTrait;
use TinyAuth\Utility\Config;

/**
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\FormHelper $Form
 */
class AuthUserHelper extends Helper {

	use AclTrait;
	use AllowTrait;
	use	AuthUserTrait;

	/**
	 * @var array
	 */
	protected array $helpers = ['Html', 'Form'];

	/**
	 * @param \Cake\View\View $View The View this helper is being attached to.
	 * @param array<string, mixed> $config Configuration settings for the helper.
	 */
	public function __construct(View $View, array $config = []) {
		$config += Config::all();

		parent::__construct($View, $config);
	}

	/**
	 * @return \ArrayAccess|array|null
	 */
	public function identity(): ArrayAccess|array|null {
		/** @var \Authorization\Identity|\Authentication\Identity|null $identity */
		$identity = $this->_View->getRequest()->getAttribute('identity');
		if (!$identity) {
			return null;
		}

		/** @var \ArrayAccess|array|null */
		return $identity->getOriginalData();
	}

	/**
	 * This is only for usage with already logged in persons as this uses the ACL (not allow) data.
	 *
	 * If you need to support also public methods (via Controller or allow INI etc), you need to enable
	 * `includeAuthentication` config and make sure all actions are whitelisted in auth allow INI file.
	 *
	 * @param array $url
	 * @throws \Cake\Core\Exception\CakeException
	 * @return bool
	 */
	public function hasAccess(array $url) {
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

		$authUser = $this->_View->get('_authUser');
		if ($authUser === null && !$this->getConfig('includeAuthentication')) {
			throw new CakeException('Variable _authUser containing the user data needs to be passed down. The TinyAuth.Auth component does it automatically, if loaded.');
		}
		$userArray = ($authUser instanceof ArrayAccess && method_exists($authUser, 'toArray')) ? $authUser->toArray() : (array)$authUser;

		return $this->_checkUser($userArray, $url);
	}

	/**
	 * Options:
	 * - default: Default to show instead, defaults to empty string.
	 *   Set to true to show just title text when not allowed.
	 * and all other link() options
	 *
	 * @param string $title
	 * @param array $url
	 * @param array<string, mixed> $options
	 * @return string
	 */
	public function link($title, array $url, array $options = []) {
		if (!$this->hasAccess($url)) {
			return $this->_default($title, $options);
		}
		unset($options['default']);

		return $this->Html->link($title, $url, $options);
	}

	/**
	 * Options:
	 * - default: Default to show instead, defaults to empty string.
	 *   Set to true to show just title text when not allowed.
	 * and all other link() options
	 *
	 * @param string $title
	 * @param array $url
	 * @param array<string, mixed> $options
	 * @return string
	 */
	public function postLink($title, array $url, array $options = []) {
		if (!$this->hasAccess($url)) {
			return $this->_default($title, $options);
		}
		unset($options['default']);

		return $this->Form->postLink($title, $url, $options);
	}

	/**
	 * @param string $title
	 * @param array<string, mixed> $options
	 * @return string
	 */
	protected function _default($title, array $options) {
		$options += [
			'default' => '',
			'escape' => true,
		];

		if ($options['default'] === true) {
			return ($options['escape'] === false) ? $title : h($title);
		}

		return $options['default'];
	}

	/**
	 * @throws \Cake\Core\Exception\CakeException
	 * @return array
	 */
	protected function _getUser(): array {
		if (class_exists(Identity::class)) {
			$identity = $this->identity();

			return $identity ? $this->_toArray($identity) : [];
		}

		$authUser = $this->_View->get('_authUser');
		if ($authUser === null) {
			throw new CakeException('TinyAuth.AuthUser helper needs TinyAuth.AuthUser component to function. Please make sure it is loaded in your controller.');
		}

		return (array)$authUser;
	}

}
