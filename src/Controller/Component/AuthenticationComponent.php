<?php

namespace TinyAuth\Controller\Component;

use Authentication\Controller\Component\AuthenticationComponent as CakeAuthenticationComponent;
use TinyAuth\Auth\AllowAdapter\IniAllowAdapter;
use TinyAuth\Auth\AllowTrait;

/**
 * TinyAuth AuthenticationComponent to handle all authentication in a central ini file.
 */
class AuthenticationComponent extends CakeAuthenticationComponent {

	use AllowTrait;

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
