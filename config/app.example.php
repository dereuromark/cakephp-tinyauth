<?php

/**
 * TinyAuth Example Configuration
 *
 * Merge the keys below into your application's config/app.php (or
 * config/app_local.php) — do not replace the whole file, since this snippet
 * only contains this plugin's configuration. When copying entries that
 * reference imported classes, use fully-qualified class names or move the
 * `use` imports to the top of the target file. Customize the values as needed.
 *
 * The canonical defaults are defined in src/Utility/Config.php (the static
 * default config array merged with `Configure::read('TinyAuth')`). This file
 * documents those defaults so you can override individual keys. Only set the
 * keys you need; anything omitted falls back to the values shown below.
 */

return [
	'TinyAuth' => [
		// allow (public access) configuration
		'allowAdapter' => \TinyAuth\Auth\AllowAdapter\IniAllowAdapter::class, // Adapter resolving public/allowed actions
		'allowFilePath' => null, // Path to the allow INI file, e.g. Plugin::configPath('Admin'); filePath is also honored for shared config
		'allowFile' => 'auth_allow.ini', // File name of the allow rules
		'allowNonPrefixed' => false, // true allows all non-prefixed controller actions as public access
		'allowPrefixes' => [], // Prefixes whitelisted as public access

		// acl (authorization) configuration
		'aclAdapter' => \TinyAuth\Auth\AclAdapter\IniAclAdapter::class, // Adapter resolving ACL rules
		'idColumn' => 'id', // ID column in the users table
		'roleColumn' => 'role_id', // Foreign key for the role id in users table or pivot table
		'userColumn' => 'user_id', // Foreign key for the user id in the pivot table (multi-role setups only)
		'aliasColumn' => 'alias', // Column in roles table holding the role alias/slug
		'roleIdColumn' => 'id', // Primary key column in roles table (use 'uuid' for UUID-based systems)
		'rolesTable' => 'Roles', // Configure key holding available roles OR class name of the roles table
		'usersTable' => 'Users', // Name of the users table
		'pivotTable' => null, // Pivot table name (multi-role setups only)
		'multiRole' => false, // true enables multi-role/HABTM authorization (requires a valid pivot table)
		'superAdminRole' => null, // Id of the super admin role granting access to ALL resources
		'superAdmin' => null, // Super admin value granting access to ALL resources
		'superAdminColumn' => null, // Column of the super admin
		'authorizeByPrefix' => false, // true for 1:1 prefix-to-role matching, or a list of [prefix => role(s)]
		'allowLoggedIn' => false, // true grants logged-in users access to all actions except those under 'protectedPrefix'
		'protectedPrefix' => 'Admin', // Prefix name (or array) blacklisted when 'allowLoggedIn' is enabled
		'autoClearCache' => null, // true to auto-delete cache in debug mode; null auto-detects (uses Configure debug)
		'aclFilePath' => null, // Path to the ACL INI file, e.g. Plugin::configPath('Admin'); filePath is also honored for shared config
		'aclFile' => 'auth_acl.ini', // File name of the ACL rules
		'includeAuthentication' => false, // true to include public auth access into hasAccess() checks (requires Configure configuration)

		// Used by the sync/add commands (src/Sync/Syncer.php and src/Sync/Adder.php)
		// to restrict which route prefixes are scanned. Null scans all.
		// 'prefixes' => null,
	],
];
