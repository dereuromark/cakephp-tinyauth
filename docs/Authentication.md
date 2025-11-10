# TinyAuth Authentication
The fast and easy way for user authentication in CakePHP applications.

## Prerequisites

**IMPORTANT:** This component wraps the official CakePHP Authentication plugin.
You **must** install and understand it first:

### Required Steps

1. **Install the official plugin:**
   ```bash
   composer require cakephp/authentication
   ```

2. **Configure middleware and authentication service:**
   See [AuthenticationPlugin.md](AuthenticationPlugin.md) for complete TinyAuth-specific setup instructions.

   Also refer to the [official CakePHP Authentication documentation](https://book.cakephp.org/authentication/3/en/index.html) for:
   - Middleware configuration
   - Authentication service setup
   - Identity configuration
   - Login/logout implementation

3. **Load TinyAuth component** (see [Enabling](#enabling) section below)

### What TinyAuth Authentication Adds

Once the official plugin is configured, TinyAuth Authentication Component adds:
- **INI-based action whitelisting** - Define public actions in `auth_allow.ini` instead of code
- **Zero controller modifications** - No need to call `allowUnauthenticated()` in every controller
- **Plugin compatibility** - Automatically works with third-party plugins

## Basic Features
- INI file (static) based access rights (controller-action setup)
- Lightweight and incredibly fast

Do NOT use if
- you want to dynamically adjust what pages are public inside controllers

## Quick setups
TinyAuth, to live up to its name, offers a few quick setups.

### Allow non-prefixed
If you have non-prefixed controllers that you want to make public and keep prefixed ones protected:
```php
'allowNonPrefixed' => true,
```
Any such action will now be public by default.

You can always set up "deny" rules for any action to protect a specific one from public access.

### Prefix based allow
If you want to allow certain prefixes on top, you can use:
```php
'allowPrefixes' => [
    'MyPrefix',
    'Nested/Prefix',
],
```

>**Note:** Prefixes are always `CamelCased` (even if routing makes them to `dashed-ones` in the URL).

Careful: Nested prefixes currently also match (and inherit) by parent.
So if `MyPrefix` is allowed, `MyPrefix/Sub` and other nested ones would also automatically be allowed.
You would need to explicitly set up ACL rules here to deny those if needed.

At the same time you can always set up "deny" rules for any allowed prefix to revoke the set default.

## Enabling

Authentication is set up in your controller's `initialize()` method:

```php
// src/Controller/AppController

public function initialize() {
    parent::initialize();

    $this->loadComponent('TinyAuth.Authentication');
}
```

That is basically already all for the code changes :-) Super-easy!

## auth_allow.ini

TinyAuth expects an ``auth_allow.ini`` file in your config directory.
Use it to specify what actions are not protected by authentication.

The section key syntax follows the CakePHP naming convention:
```
PluginName.MyPrefix/MyController
```

Make sure to create an entry for each action you want to expose and use:

- one or more action names
- the ``*`` wildcard to grant access to all actions of that controller
- use `"!actionName"` (quotes are important then) to deny certain actions

```ini
; ----------------------------------------------------------
; UsersController
; ----------------------------------------------------------
Users = index
; ----------------------------------------------------------
; UsersController using /api prefixed route
; ----------------------------------------------------------
Api/Users = index, view, edit
; ----------------------------------------------------------
; UsersController using /admin prefixed route
; ----------------------------------------------------------
Admin/Users = *
; ----------------------------------------------------------
; AccountsController in plugin named Accounts
; ----------------------------------------------------------
Accounts.Accounts = view, edit
; ----------------------------------------------------------
; AccountsController in plugin named Accounts using /my-admin
; prefixed route (assuming you are using recommended DashedRoute class)
; ----------------------------------------------------------
Accounts.MyAdmin/Accounts = index
```

>**Note:** Prefixes are always `CamelCased`. The route inflects to the final casing if needed.
Nested prefixes are joined using `/`, e.g. `MyAdmin/Nested`.

Using only "granting" is recommended for security reasons.
Careful with denying, as this can accidentally open up more than desired actions. If you really want to use it:

```ini
Users = "!secret",*
```
Meaning: Grant public access to all "Users" controller actions by default, but keep authentication required for "secret" action.

Note that denying always trumps granting, if both are declared for an action.

### Multiple files and merging
You can specify multiple paths in your config, e.g. when you have plugins and separated the definitions across them.
Make sure you are using each key only once, though. The first definition will be kept and all others for the same key are ignored.

### Template with defaults
See the `config/` folder and the default template for popular plugins.
You can copy out any default rules you want to use in your project.

## Mixing with code
It is possible to have mixed INI and code rules. Those will get merged prior to authentication.
So in case any of your controllers (or plugin controllers) contain such a statement, it will merge itself with your INI whitelist:
```php
// In your controller
use Cake\Event\EventInterface;
...

    public function beforeFilter(EventInterface $event): void {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['index', 'view']);
    }
```
This can be interested when migrating slowly to TinyAuth, for example.
Once you move such a code based rule into the INI file, you can safely remove those lines of code in your controller :)

### allow() vs deny()
Since 1.11.0 you can also mix it with `deny()` calls. From how the Authentication component works, all allow() calls need be done before calling deny().
As such TinyAuth injects its list now before `Controller::beforeFilter()` gets called.

Note: It is also advised to move away from these controller calls.

## Caching

TinyAuth makes heavy use of caching to achieve optimal performance.
By default, it will not use caching in debug mode, though.

To modify the caching behavior set the ``autoClearCache`` configuration option:
```php
$this->loadComponent('TinyAuth.Authentication', [
    'autoClearCache' => true|false,
]);
```

## Authentication Helper

The Authentication helper provides template-level methods for checking if URLs are public.

### Setup

Load the helper in your AppView:

```php
// src/View/AppView.php

public function initialize(): void {
    parent::initialize();

    $this->loadHelper('TinyAuth.Authentication');
}
```

### Available Methods

#### `isPublic(array $url = [])`

Check if a given URL is public (allowed in `auth_allow.ini`):

```php
// Check if current page is public
<?php if ($this->Authentication->isPublic()): ?>
    <p>This page is public</p>
<?php endif; ?>

// Check if a specific URL is public
<?php if ($this->Authentication->isPublic(['controller' => 'Users', 'action' => 'register'])): ?>
    <?= $this->Html->link('Register', ['controller' => 'Users', 'action' => 'register']); ?>
<?php endif; ?>
```

#### Named Routes Support

The helper also supports named routes:

```php
<?php if ($this->Authentication->isPublic(['_name' => 'users:register'])): ?>
    <p>Registration is public</p>
<?php endif; ?>
```

### Example Usage

#### Conditional Navigation

```php
<nav>
    <ul>
        <?php if ($this->Authentication->isPublic(['action' => 'index'])): ?>
            <li><?= $this->Html->link('Home', ['action' => 'index']); ?></li>
        <?php endif; ?>

        <?php if ($this->Authentication->isPublic(['controller' => 'Articles', 'action' => 'view'])): ?>
            <li><?= $this->Html->link('Articles', ['controller' => 'Articles']); ?></li>
        <?php endif; ?>
    </ul>
</nav>
```

#### Show Login Link Only on Public Pages

```php
<?php if ($this->Authentication->isPublic() && !$this->AuthUser->id()): ?>
    <div class="login-prompt">
        <?= $this->Html->link('Login', ['controller' => 'Users', 'action' => 'login']); ?>
    </div>
<?php endif; ?>
```

## Configuration

TinyAuth Authentication component supports the following configuration options.

 Option         | Type   | Description
:---------------|:-------|:---------------------------------------------------------------------------------------------------
 autoClearCache | bool   | True will generate a new ACL cache file every time.
 allowFilePath  | string | Full path to the INI file. Can also be an array of paths. Defaults to `ROOT . DS . 'config' . DS`.
 allowFile      | string | Name of the INI file. Defaults to `auth_allow.ini`.
 allowAdapter   | string | Class name, defaults to `IniAllowAdapter::class`.
