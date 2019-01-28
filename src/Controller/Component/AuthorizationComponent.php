<?php

namespace TinyAuth\Controller\Component;

use Authorization\Controller\Component\AuthorizationComponent as CakeAuthorizationComponent;
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

}
