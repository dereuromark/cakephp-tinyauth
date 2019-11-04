# TinyAuth Authentication
The fast and easy way for user authentication in CakePHP 3.x applications.

Use TinyAuth Component if you want to add instant (and easy) action whitelisting to your application.
You can allow/deny per controller action or with wildcards also per controller and more.

## Basic Features
- INI file (static) based access rights (controller-action setup)
- Lightweight and incredibly fast

Do NOT use if
- you want to dynamically adjust what pages are public inside controllers

## Enabling

Authentication is set up in your controller's `initialize()` method:

```php
// src/Controller/AppController

public function initialize() {
    parent::initialize();

    $this->loadComponent('TinyAuth.Auth');
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
- use `"!actionName"` (quotes are important then) to deny certain actions

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
; AccountsController in plugin named Accounts using /my-admin
; prefixed route (assuming you are using recommended DashedRoute class)
; ----------------------------------------------------------
Accounts.my_admin/Accounts = index
```

Note: Prefixes are always `lowercase_underscored`. The route inflects to the final casing if needed. 
Nested prefixes are joined using `/`, e.g. `my/admin/nested/`.

Using only "allowing" is recommended for security reasons.
Careful with denying, as this can accidentally open up more than desired actions. If you really want to use it:

```ini
Users = "!secret",*
```
Meaning: Allow all "Users" controller actions by default, but keep authentication required for "secret" action.

Note that deny always trumps allow, if both are declared for an action.

### Multiple files and merging
You can specify multiple paths in your config, e.g. when you have plugins and separated the definitions across them.
Make sure you are using each key only once, though. The first definition will be kept and all others for the same key are ignored.

### Mixing with code
It is possible to have mixed INI and code rules. Those will get merged prior to authentication.
So in case any of your controllers (or plugin controllers) contain such a statement, it will merge itself with your INI whitelist:
```php
// In your controller
use Cake\Event\Event;
...

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);

        $this->Auth->allow(['index', 'view']);
    }
```
This can be interested when migrating slowly to TinyAuth, for example.
Once you move such a code based rule into the INI file, you can safely remove those lines of code in your controller :)

### allow() vs deny()
Since 1.11.0 you can also mix it with deny() calls. From how the AuthComponent works, all allow() calls need be done before calling deny().
As such TinyAuth injects its list now before `Controller::beforeFilter()` gets called.


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
allowFilePath|string|Full path to the INI file. Can also be an array of paths. Defaults to `ROOT . DS . 'config' . DS`.
allowFile|string|Name of the INI file. Defaults to `auth_allow.ini`.
allowAdapter|string|Class name, defaults to `IniAllowAdapter::class`.
