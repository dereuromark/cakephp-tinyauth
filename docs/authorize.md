# TinyAuth Autorization

Enable TinyAuth Authorize if you want to add instant (and easy) role based
access to your application.

## Enabling

Assuming you already have Authentiction set up correctly you can enable
Authorization in your controllers beforeFilter like this example:

```php
// src/Controller/AppController

use Cake\Event\Event;

public function beforeFilter(Event $event)
{
	parent::beforeFilter($event);
	$this->loadComponent('Auth', [
		'authorize' => [
			'TinyAuth.Tiny' => [
				'autoClearCache' => true,
				'allowUser' => false,
				'allowAdmin' => false,
				'adminRole' => 'admin',
				'superAdminRole' => null
			]
		]
	]);
}
```

## Roles

You need to define some roles for Authorize to work, for example:

```php
// config/app_custom.php


/**
* Optionally define constants for easy referencing throughout your code
*/
define('ROLE_USERS', 1);
define('ROLE_ADMINS', 2);
define('ROLE_SUPERADMIN', 9);

return [
	'Roles' => [
		'user' => ROLE_USERS,
		'admin' => ROLE_ADMINS,
		'superadmin' => ROLE_SUPERADMIN
	]
];
```

## acl.ini

Authorize expects an ``acl.ini`` file in your config directory.
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

Authorize supports the following configuration options.

Option  | Type|Description
--------|------------
allowUser|boolean|True will give authenticated users access to all resources except those using the `adminPrefix`
allowAdmin|boolean|True will give users with a role id matching `adminRole` access to all resources using the `adminPrefix`
adminRole|int|Id of the role you will use as admins. Users with this role are granted access to all actions using `adminPrefix` but only when `allowAdmin` is enabled
superAdminRole|int|Id of the super admin role. Users with this role will have access to ALL resources.
adminPrefix|string|Name of the prefix used for admin pages. Defaults to admin.
autoClearCache|Boolean|True will generate a new acl cache file every time.
aclKey|string|Name of the column holding your user role id (only for single role per user/BT)
aclTable|string|Name of the table holding your user roles (only for multiple roles per user/HABTM)
