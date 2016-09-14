# TinyAuth Authorization
The fast and easy way for user authorization in CakePHP 3.x applications.

Enable TinyAuth Authorize adapter if you want to add instant (and easy) role
based access control (RBAC) to your application.

## Basic Features
- Single or multi role
- DB (dynamic) or Configure based role definition
- INI file (static) based access rights (controller-action/role setup)
- Lightweight and incredibly fast

Do NOT use if
- you need ROW based access
- you want to dynamically adjust access rights (or enhance it with a web
frontend yourself)

## Enabling

Assuming you already have authentication set up correctly you can enable
authorization in your controller's `beforeFilter` like this example:

```php
// src/Controller/AppController

use Cake\Event\Event;

public function beforeFilter(Event $event) {
	parent::beforeFilter($event);

	$this->loadComponent('TinyAuth.Auth', [
		'authorize' => [
			'TinyAuth.Tiny' => [
				'multiRole' => false
			],
			...
		]
	]);
}
```
TinyAuth Authorize can be used in combination with any [CakePHP Authentication Type](http://book.cakephp.org/3.0/en/controllers/components/authentication.html#choosing-an-authentication-type), as well.


Please note that `TinyAuth.Auth` replaces the default CakePHP `Auth` component. Do not try to load both at once.
You can also use the default one, if you only want to use ACL (authorization):
```php
	$this->loadComponent('Auth', [
		'authorize' => [
			'TinyAuth.Tiny' => [
				'multiRole' => false
			],
			...
		]
	]);
```


## Roles

TinyAuth requires the presence of roles to function so create those first using
one of the following two options.

### Configure based roles

Define your roles in a Configure array if you want to prevent database role
lookups, for example:

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

### Database roles
When you choose to store your roles in the database TinyAuth expects a table
named ``roles``. If you prefer to use another table name simply specify it using the
``rolesTable`` configuration option.

>**Note:** make sure to add an "alias" field to your roles table (used as slug
identifier in the acl.ini file)

Example of a record from a valid roles table:

```php
'id' => '11'
'name' => 'User'
'description' => 'Basic authenticated user'
'alias' => 'user'
'created' => '2010-01-07 03:36:33'
'modified' => '2010-01-07 03:36:33'
```

> Please note that you do NOT need Configure based roles when using database
> roles. Also make sure to remove (or rename) existing Configure based roles
> since TinyAuth will always first try to find a matching Configure roles array
> before falling back to using the database.

## Users

### Single-role

When using the single-role-per-user model TinyAuth expects your Users model to
contain an column named ``role_id``. If you prefer to use another column name
simply specify it using the ``roleColumn`` configuration option.

The ``roleColumn`` option is also used on pivot table in a multi-role setup.

### Multi-role
When using the multiple-roles-per-user model:

- your database MUST have a ``roles`` table
- your database MUST have a valid join table (e.g. ``roles_users``). This can be overridden with the ``pivotTable`` option.
- the configuration option ``multiRole`` MUST be set to ``true``

Example of a record from a valid join table:

```php
'id' => 1
'user_id' => 1
'role_id' => 1
```

If you want to have default database tables here for multi-role auth, you can use the plugin shipped Migrations file:
```
bin/cake migrations migrate -p TinyAuth
```
Alternatively you can copy and paste this migration file to your `app/Config` folder and adjust the fields and table names and then use that modified version instead.

## acl.ini

TinyAuth expects an ``acl.ini`` file in your config directory.
Use it to specify who gets access to which resources.

The section key syntax follows the CakePHP naming convention for plugins.

Make sure to create an entry for each action you want to expose and use:

- one or more role names (groups granted access)
- the ``*`` wildcard to allow access to all authenticated users

```ini
; ----------------------------------------------------------
; UsersController
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

## Caching

TinyAuth makes heavy use of caching to achieve optimal performance.
By default it will not use caching in debug mode, though.

You may however want to disable caching while developing RBAC to prevent
confusing (outdated) results.

To disable caching either:

- pass ``true`` to the ``autoClearCache`` configuration option
- use the example below to disable caching automatically for CakePHP debug mode

```php
'TinyAuth.Tiny' => [
	'autoClearCache' => Configure::read('debug')
]
```

## Configuration

TinyAuthorize adapter supports the following configuration options.

Option | Type | Description
:----- | :--- | :----------
roleColumn|string|Name of column in user table holding role id (used for foreign key in users table in a single role per user setup, or in the pivot table on multi-roles setup)
userColumn|string|Name of column in pivot table holding role id (only used in pivot table on multi-roles setup)
aliasColumn|string|Name of the column for the alias in the role table
idColumn|string|Name of the ID Column in users table
rolesTable|string|Name of Configure key holding all available roles OR class name of roles database table
usersTable|string|Class name of the users table.
pivotTable|string|Name of the pivot table, for a multi-group setup.
rolesTablePlugin|string|Name of the plugin for the roles table, if any.
pivotTablePlugin|string|Name of the plugin for the pivot table, if any.
multiRole|bool|True will enable multi-role/HABTM authorization (requires a valid join table)
superAdminRole|int|Id of the super admin role. Users with this role will have access to ALL resources.
superAdmin|int or string|Id/name of the super admin. Users with this id/name will have access to ALL resources.null/0/"0" take disable it
superAdminColumn|string|Column of super admin in user table.default use idColumn option
authorizeByPrefix|bool|If prefixed routes should be auto-handled by their matching role name.
prefixes|array|A list of authorizeByPrefix handled prefixes.
allowUser|bool|True will give authenticated users access to all resources except those using the `adminPrefix`
adminPrefix|string|Name of the prefix used for admin pages. Defaults to admin.
autoClearCache|bool|True will generate a new ACL cache file every time.
filePath|string|Full path to the acl.ini. Defaults to `ROOT . DS . 'config' . DS`.
file|string|Name of the INI file. Defaults to `acl.ini`.
cache|string|Cache type. Defaults to `_cake_core_`.
cacheKey|string|Cache key. Defaults to `tiny_auth_acl`.


## Auth user data
For reading auth user data take a look at [Tools plugin AuthUser component/helper](https://github.com/dereuromark/cakephp-tools/blob/master/docs/Auth/Auth.md).
