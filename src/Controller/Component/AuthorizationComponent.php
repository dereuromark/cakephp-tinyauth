<?php

namespace TinyAuth\Controller\Component;

use Authorization\Controller\Component\AuthorizationComponent as CakeAuthorizationComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Event\Event;
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
	}

	/**
	 * Callback for Controller.startup event.
	 *
	 * @param \Cake\Event\Event $event Event instance.
	 * @return \Cake\Http\Response|null
	 */
	public function startup(Event $event) {
		$this->_prepareAuthorization($event);

		return null;
	}

	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	protected function _prepareAuthorization(Event $event) {
		//TODO 'skipAuthorization' from allow ini config
		//TODO 'authorizeModel' and maybe 'actionMap'
		//TODO rest
	}

}
