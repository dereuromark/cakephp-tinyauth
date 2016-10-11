# TinyAuth Authentication
The fast and easy way for user authentication in CakePHP 3.x applications.

Use TinyAuth Component if you want to add instant (and easy) action whitelisting to your application.

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

### Mixing with code
It is possible to have mixed INI and code rules. Those will get merged prior to authentication.
So in case any of your controllers (or plugin controllers) contain such a statement, it will merge itself with your INI whitelist:
```php
// In your controller
use Cake\Event\Event;
...

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		$this->Auth->allow('index', 'view');
	}
```
This can be interested when migrating slowly to TinyAuth, for example.
Once you move such a code based rule into the INI file, you can safely remove those lines of code in your controller :)


## Caching

TinyAuth makes heavy use of caching to achieve optimal performance.
By default it will not use caching in debug mode, though.

To modify the caching behavior set the ``autoClearCache`` configuration option:
```php
$this->loadComponent('TinyAuth.Auth', [
	'autoClearCache' => true|false
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
