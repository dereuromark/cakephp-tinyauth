<?php

namespace TinyAuth\View\Helper;

use Cake\Core\Exception\Exception;
use Cake\View\Helper;
use Cake\View\View;
use RuntimeException;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AuthUserTrait;

/**
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\FormHelper $Form
 */
class AuthUserHelper extends Helper {

	use AclTrait, AuthUserTrait;

	/**
	 * @var array
	 */
	public $helpers = ['Html', 'Form'];

	/**
	 * @param \Cake\View\View $View The View this helper is being attached to.
	 * @param array $config Configuration settings for the helper.
	 */
	public function __construct(View $View, array $config = []) {
		$config = $this->_prepareConfig($config);

		parent::__construct($View, $config);
	}

	/**
	 * This is only for usage with already logged in persons as this uses the ACL (not allow) data.
	 *
	 * If you need to support also public methods (via Controller or allow INI etc), you could make a fake
	 * "public" role that copies over the public actions into such a dummy role.
	 * This is a workaround for the time being until we can find a cleaner solution.
	 *
	 * @param array $url
	 * @return bool
	 * @throws \Cake\Core\Exception\Exception
	 */
	public function hasAccess(array $url) {
		$params = $this->request->getAttribute('params');
		$url += [
			'prefix' => !empty($params['prefix']) ? $params['prefix'] : null,
			'plugin' => !empty($params['plugin']) ? $params['plugin'] : null,
			'controller' => $params['controller'],
			'action' => 'index',
		];

		if (!isset($this->_View->viewVars['_authUser'])) {
			throw new Exception('Variable _authUser containing the user data needs to be passed down. The TinyAuth.Auth component does it automatically, if loaded.');
		}
				
		return $this->_check($this->_View->viewVars['_authUser'], $url);
	}

	/**
	 * Options:
	 * - default: Default to show instead, defaults to empty string.
	 *   Set to true to show just title text when not allowed.
	 * and all other link() options
	 *
	 * @param string $title
	 * @param array $url
	 * @param array $options
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
	 * @param array $options
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
	 * @param array $options
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
	 * @return array
	 */
	protected function _getUser() {
		if (!isset($this->_View->viewVars['_authUser'])) {
			throw new RuntimeException('AuthUser helper needs AuthUser component to function');
		}
		return $this->_View->viewVars['_authUser'];
	}

}
