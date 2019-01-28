<?php

namespace TinyAuth\Controller\Component;

use Authorization\Controller\Component\AuthorizationComponent as CakeAuthorizationComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Event\Event;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowAdapter\IniAllowAdapter;

/**
 * TinyAuth AuthorizationComponent to handle all authorization in a central ini file.
 */
class AuthorizationComponent extends CakeAuthorizationComponent {

	use AclTrait;

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
		$config += ($this->_defaultTinyAuthConfig + $this->_defaultConfig());

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
