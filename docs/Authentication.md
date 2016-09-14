# TinyAuth Authentication
The fast and easy way for user authentication in CakePHP 3.x applications.

Use TinyAuth Componont if you want to add instant (and easy) action whitelisting to your application.

## Basic Features
- INI file (static) based access rights (controller-action setup)
- Lightweight and incredibly fast

Do NOT use if
- you want to dynamically adjust what pages are public inside controllers

## Enabling

Authentication is set up in your controller's `initialize` method:

```php
// src/Controller/AppController

public function initialize() {
	parent::initialize();

	$this->loadComponent('TinyAuth.Auth', [
		'autoClearCache' => ...
	]);
}
```

That is basically already all for the code changes :-) Super-easy!

## auth_allow.ini

TinyAuth expects an ``auth_allow.ini`` file in your config directory.
Use it to specify what actions are not protected by authentication.

The section key syntax follows the CakePHP naming convention for plugins.

Make sure to create an entry for each action you want to expose and use:

- one or more action names
- the ``*`` wildcard to allow access to all actions of that controller

```ini
; ----------------------------------------------------------
; UsersController
; ----------------------------------------------------------
Users = index
; ----------------------------------------------------------
; UsersController using /api prefixed route
; ----------------------------------------------------------
api/Users = index, view, edit
; ----------------------------------------------------------
; UsersController using /admin prefixed route
; ----------------------------------------------------------
admin/Users = *
; ----------------------------------------------------------
; AccountsController in plugin named Accounts
; ----------------------------------------------------------
Accounts.Accounts = view, edit
; ----------------------------------------------------------
; AccountsController in plugin named Accounts using /admin
; prefixed route
; ----------------------------------------------------------
Accounts.admin/Accounts = index
```

## Caching

TinyAuth makes heavy use of caching to achieve optimal performance.

You may however want to disable caching while developing to prevent
confusing (outdated) results.

To disable caching either:

- pass ``true`` to the ``autoClearCache`` configuration option
- use the example below to disable caching automatically for CakePHP debug mode

```php
$this->loadComponent('TinyAuth.Auth', [
	'autoClearCache' => Configure::read('debug')
)]
```

## Configuration

TinyAuth AuthComponent supports the following configuration options.

Option | Type | Description
:----- | :--- | :----------
autoClearCache|bool|True will generate a new ACL cache file every time.
filePath|string|Full path to the INI file. Defaults to `ROOT . DS . 'config' . DS`.
file|string|Name of the INI file. Defaults to `auth_allow.ini`.
cache|string|Cache type. Defaults to `_cake_core_`.
cacheKey|string|Cache key. Defaults to `tiny_auth_allow`.
