# TinyAuth Authorization
The fast and easy way for user authorization in CakePHP 3.x applications.

Enable TinyAuth Authorize adapter if you want to add instant (and easy) role based
access (RBAC) to your application.

## Basic Features
- Singe or multi role
- DB (dynamic) or Configure based role definition
- INI file (static) based access rights (controller-action/role setup)
- Lightweight and incredibly fast

Do NOT use if
- you need ROW based access
- you want to dynamically adjust access rights (or enhance it with a web frontend yourself)

## Enabling

Assuming you already have authentication set up correctly you can enable
authorization in your controller's `beforeFilter` like this example:

```php
// src/Controller/AppController

use Cake\Event\Event;

public function beforeFilter(Event $event) {
	parent::beforeFilter($event);

	$this->loadComponent('Auth', [
		'authorize' => [
			'TinyAuth.Tiny' => [
				'allowUser' => false,
				'authorizeByPrefix' => false,
				'prefixes' => [],
				'superAdminRole' => null
			]
		]
	]);
}
```

## Roles

You need to define some roles for TinyAuth to work, for example:

```php
// config/app_custom.php


/**
* Optionally define constants for easy referencing throughout your code
*/
define('ROLE_USER', 1);
define('ROLE_ADMIN', 2);
define('ROLE_SUPERADMIN', 9);

return [
	'Roles' => [
		'user' => ROLE_USER,
		'admin' => ROLE_ADMIN,
		'superadmin' => ROLE_SUPERADMIN
	]
];
```

## acl.ini

TinyAuth expects an ``acl.ini`` file in your config directory.
Use it to specify who gets access to which resources.

The section key syntax follows the CakePHP naming convention for plugins.

Make sure to create an entry for each action you want to expose and use:

- one or more role names (groups granted access)
- the ``*`` wildcard to allow access to all authenticated users

```ini
; ----------------------------------------------------------
; Userscontroller
; ----------------------------------------------------------
[Users]
index = user, admin, undefined-role
edit, view = user, admin
* = admin
; ----------------------------------------------------------
; UsersController using /api prefixed route
; ----------------------------------------------------------
[api/Users]
view = user
* = admin
; ----------------------------------------------------------
; UsersController using /admin prefixed route
; ----------------------------------------------------------
[admin/Users]
* = admin
; ----------------------------------------------------------
; AccountsController in plugin named Accounts
; ----------------------------------------------------------
[Accounts.Accounts]
view, edit = user
* = admin
; ----------------------------------------------------------
; AccountsController in plugin named Accounts using /admin
; prefixed route
; ----------------------------------------------------------
[Accounts.admin/Accounts]
* = admin
; ----------------------------------------------------------
; CompaniesController in plugin named Accounts
; ----------------------------------------------------------
[Accounts.Companies]
view, edit = user
* = admin
; ----------------------------------------------------------
; CompaniesController in plugin named Accounts using /admin
; prefixed route
; ----------------------------------------------------------
[Accounts.admin/Companies]
* = admin
```

## Configuration

TinyAuth supports the following configuration options.

Option | Type | Description
:----- | :--- | :----------
roleColumn|string|Name of column in user table holding role id (only used for single-role per user/BT)
rolesTable|string|Name of Configure key holding all available roles OR class name of roles database table
multiRole|boolean|True will enable multi-role/HABTM authorization (requires a valid join table)
superAdminRole|int|Id of the super admin role. Users with this role will have access to ALL resources.
authorizeByPrefix|boolean|If prefixed routes should be auto-handled by their matching role name.
prefixes|array|A list of authorizeByPrefix handled prefixes.
allowUser|boolean|True will give authenticated users access to all resources except those using the `adminPrefix`
adminPrefix|string|Name of the prefix used for admin pages. Defaults to admin.
autoClearCache|Boolean|True will generate a new ACL cache file every time.
