<?php
namespace TinyAuth\Auth;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use InvalidArgumentException;
use TinyAuth\Auth\AclAdapter\IniAclAdapter;
use TinyAuth\Auth\AllowAdapter\AllowAdapterInterface;

trait AllowTrait {

	/**
	 * @var \TinyAuth\Auth\AllowAdapter\AllowAdapterInterface|null
	 */
	protected $_allowAdapter;

	/**
	 * @return array
	 */
	protected function _defaultConfig() {
		$defaults = [
			'aclAdapter' => IniAclAdapter::class,
			'idColumn' => 'id', // ID Column in users table
			'roleColumn' => 'role_id', // Foreign key for the Role ID in users table or in pivot table
			'userColumn' => 'user_id', // Foreign key for the User id in pivot table. Only for multi-roles setup
			'aliasColumn' => 'alias', // Name of column in roles table holding role alias/slug
			'rolesTable' => 'Roles', // name of Configure key holding available roles OR class name of roles table
			'usersTable' => 'Users', // name of the Users table
			'pivotTable' => null, // Should be used in multi-roles setups
			'multiRole' => false, // true to enables multirole/HABTM authorization (requires a valid pivot table)
			'superAdminRole' => null, // id of super admin role, which grants access to ALL resources
			'superAdmin' => null, // super admin, which grants access to ALL resources
			'superAdminColumn' => null, // Column of super admin
			'authorizeByPrefix' => false,
			'prefixes' => [], // Whitelisted prefixes (only used when allowAdmin is enabled), leave empty to use all available
			'allowUser' => false, // enable to allow ALL roles access to all actions except prefixed with 'adminPrefix'
			'adminPrefix' => 'admin', // name of the admin prefix route (only used when allowUser is enabled)
			'cache' => '_cake_core_',
			'aclCacheKey' => 'tiny_auth_acl',
			'allowCacheKey' => 'tiny_auth_allow', // This is needed to fetch allow info from the correct cache. Must be the same as set in AuthComponent.
			'autoClearCache' => null, // Set to true to delete cache automatically in debug mode, keep null for auto-detect
			'aclFilePath' => null, // Possible to locate INI file at given path e.g. Plugin::configPath('Admin'), filePath is also available for shared config
			'aclFile' => 'tinyauth_acl.ini',
			'includeAuthentication' => false, // Set to true to include public auth access into hasAccess() checks. Note, that this requires Configure configuration.
		];
		$config = (array)Configure::read('TinyAuth') + $defaults;

		return $config;
	}

	/**
	 * Finds the authentication adapter to use for this request.
	 *
	 * @param string $adapter Acl adapter to load.
	 * @return \TinyAuth\Auth\AllowAdapter\AllowAdapterInterface
	 * @throws \Cake\Core\Exception\Exception
	 * @throws \InvalidArgumentException
	 */
	protected function _loadAllowAdapter($adapter) {
		if (!class_exists($adapter)) {
			throw new Exception(sprintf('The Acl Adapter class "%s" was not found.', $adapter));
		}

		$adapterInstance = new $adapter();
		if (!($adapterInstance instanceof AllowAdapterInterface)) {
			throw new InvalidArgumentException(sprintf(
				'TinyAuth Acl adapters have to implement %s.', AllowAdapterInterface::class
			));
		}

		return $adapterInstance;
	}

}
