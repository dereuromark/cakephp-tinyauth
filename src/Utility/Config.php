<?php

namespace TinyAuth\Utility;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use TinyAuth\Auth\AclAdapter\IniAclAdapter;
use TinyAuth\Auth\AllowAdapter\IniAllowAdapter;

class Config {

	/**
	 * Configuration sets.
	 *
	 * @var array
	 */
	protected static $_config = [];

	/**
	 * @var array
	 */
	protected static $_defaultConfig = [
		'allowAdapter' => IniAllowAdapter::class,
		'allowFilePath' => null,
		'allowFile' => 'auth_allow.ini',

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
		'autoClearCache' => null, // Set to true to delete cache automatically in debug mode, keep null for auto-detect
		'aclFilePath' => null, // Possible to locate INI file at given path e.g. Plugin::configPath('Admin'), filePath is also available for shared config
		'aclFile' => 'auth_acl.ini',
		'includeAuthentication' => false, // Set to true to include public auth access into hasAccess() checks. Note, that this requires Configure configuration.
	];

	/**
	 * @return array
	 */
	public static function all() {
		if (!static::$_config) {
			$config = (array)Configure::read('TinyAuth') + static::$_defaultConfig;

			if (!$config['prefixes'] && !empty($config['authorizeByPrefix'])) {
				throw new Exception('Invalid TinyAuthorization setup for `authorizeByPrefix`. Please declare `prefixes`.');
			}

			if ($config['autoClearCache'] === null) {
				$config['autoClearCache'] = Configure::read('debug');
			}

			static::$_config = $config;
		}

		return static::$_config;
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public static function get($key) {
		$config = static::all();
		if (!isset($config[$key])) {
			throw new Exception('Key ' . $key . ' not found in config.');
		}

		return $config[$key];
	}

	/**
	 * @return void
	 */
	public static function drop() {
		static::$_config = [];
	}

}
