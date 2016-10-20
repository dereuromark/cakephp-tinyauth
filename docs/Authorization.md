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

public function initialize() {
	parent::initialize();

	$this->loadComponent('TinyAuth.Auth', [
		'authorize' => [
			'TinyAuth.Tiny' => [
				...
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
				...
			]
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
// config/app.php

/*
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

### Multiple files and merging
You can specify multiple paths in your config, e.g. when you have plugins and separated the definitions across them.
Make sure you are using each section key only once, though. The first definition will be kept and all others for the same section key are ignored.


## Caching

TinyAuth makes heavy use of caching to achieve optimal performance.
By default it will not use caching in debug mode, though.

To modify the caching behavior set the ``autoClearCache`` configuration option:
```php
'TinyAuth.Tiny' => [
	'autoClearCache' => true|false
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
filePath|string|Full path to the acl.ini. Can also be an array of multiple paths. Defaults to `ROOT . DS . 'config' . DS`.
file|string|Name of the INI file. Defaults to `acl.ini`.
cache|string|Cache type. Defaults to `_cake_core_`.
cacheKey|string|Cache key. Defaults to `tiny_auth_acl`.


## AuthUserComponent
Add the AuthUserComponent and you can easily check permissions inside your controller scope:
```php
$this->loadComponent('TinyAuth.AuthUser');
```

Maybe you only want to redirect to a certain action if that is accessible for this user (role):
```php
if ($this->AuthUser->hasAccess(['action' => 'forModeratorOnly'])) {
	return $this->redirect(['action' => 'forModeratorOnly']);
}
// Do something else
```

Or if that person is of a certain role in general:
```php
if ($this->AuthUser->hasRole('mod') { // Either by alias or id
	// OK, do something now
}
```

For any action that get's the user id passed, you can also ask:
```php
$isMe = $this->AuthUser->isMe($userEntity->id);
// This would be equal to
$isMe = $this->AuthUser->id() == $userEntity->id;
```

## AuthHelper
The helper assists with the same in the templates.

Include the helper in your `AppView.php`:
```php
$this->loadHelper('TinyAuth.AuthUser');
```

Note that this helper only works if you also enabled the above component, as it needs some data to be passed down.

All the above gotchas also are available in the views and helpers now (id, isMe, roles, hasRole, hasRoles, hasAccess).
But on top, if you want to display certain content or a link for specific roles, you can do that, too.

Let's say we only want to print an admin backend link if the role can access it:
```php
echo $this->AuthUser->link('Admin Backend', ['prefix' => 'admin', 'action' => 'index']);
```
It will not show anything for all others.

Let's say we only want to print the delete link if the role is actually allowed to do that:
```php
echo $this->AuthUser->postLink('Delete this', ['action' => 'delete', $id], ['confirm' => 'Sure?']);
```

You can also do more complex things:
```php
if ($this->AuthUser->hasAccess(['action' => 'secretArea'])) {
	echo 'Only for you: ';
	echo $this->Html->link('admin/index', ['action' => 'secretArea']);
	echo ' (do not tell anyone else!);
}
```

## Tips

### Use constants instead of magic strings
If you are using the `hasRole()` or `hasRoles()` checks with a DB roles table, it is always better to use the aliases than the IDs (as the IDs can change).
But even so, it is better not to use magic strings like `'moderator'`, but define constants in your bootstrap for each:
````php
// In your bootstrap
define('ROLE_MOD', 'moderator');

// In your template
if ($this->AuthUser->hasRole(ROLE_MOD)) {
	...
}
```
This way, if you ever refactor them, it will be easier to adjust all occurrences, it will also be possible to use auto-completion type-hinting.

### Leverage session
Especially when working with multi-role setup, it can be useful to not every time read the current user's roles from the database.
When logging in a user you can write the roles to the session right away. 
If available here, TinyAuth will use those and will not try to query the roles table (or the `roles_users` pivot table).

For basic single-role setup, the session is expected to be filled like
```php
'Auth' => [
	'User' => [
		'id' => '1',
		'role_id' => '1',
		...
	]	
];
```
The expected `'role_id'` session key is configurable via `roleColumn` config key.

For a multi-role setup it can be either the normalized array form
```php
'Auth' => [
	'User' => [
		'id' => '1',
		...
		'Roles' => [
			'id => '1',
			...
		],
	]	
];
```
or the simplified numeric list form
```php
'Auth' => [
	'User' => [
		'id' => '1',
		...
		'Roles' => [
			'1', 
			'2',
			...
		]
	]	
];
```
The expected `'Roles'` session key is configurable via `rolesTable` config key.

When logging the user in you can have a custom handler modifying your input accordingly, prior to calling
```php
// Modify or add roles in $user
$this->Auth->setUser($user);
```

The easiest way here to contain the roles, however, is to have your custom `findAuth()` finder which also fetches those.
See [customizing-find-query](http://book.cakephp.org/3.0/en/controllers/components/authentication.html#customizing-find-query).
