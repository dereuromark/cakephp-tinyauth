# TinyAuth Authentication

The fast and easy way for user authentication in CakePHP applications using INI-based configuration.

## Overview

TinyAuth's Authentication component wraps the official [CakePHP Authentication plugin](https://github.com/cakephp/authentication) and adds INI-based configuration for public actions, eliminating the need for code changes in every controller.

**What TinyAuth Adds:**
- **INI-based action whitelisting** - Define public actions in `auth_allow.ini` instead of code
- **Zero controller modifications** - No need to call `allowUnauthenticated()` in every controller
- **Plugin compatibility** - You can still use all the core plugin functionality.

**When to Use:**
- ✅ You want centralized authentication configuration
- ✅ You need simple public action whitelisting
- ✅ You want to avoid scattering auth logic across controllers

**When NOT to Use:**
- ❌ You need to dynamically adjust public pages inside controllers
- ❌ You prefer code-based configuration over INI files

---

# Part 1: Official Plugin Setup

Before using TinyAuth features, you must set up the official CakePHP Authentication plugin.

## Step 1: Install the Official Plugin

```bash
composer require cakephp/authentication
```

**📖 Official Documentation:** [book.cakephp.org/authentication/3](https://book.cakephp.org/authentication/3/en/index.html)

**GitHub Repository:** [github.com/cakephp/authentication](https://github.com/cakephp/authentication)

**Key Official Resources:**
- [Quick Start Guide](https://book.cakephp.org/authentication/3/en/quick-start.html) - Basic setup tutorial
- [Authenticators](https://book.cakephp.org/authentication/3/en/authenticators.html) - Session, Form, Token, Cookie, etc.
- [Identifiers](https://book.cakephp.org/authentication/3/en/identifiers.html) - Password, Token, JWT, etc.
- [URL Checkers](https://book.cakephp.org/authentication/3/en/url-checkers.html) - Controlling where authenticators apply

## Step 2: Implement Authentication Interface

Your `Application` class must implement `AuthenticationServiceProviderInterface`:

```php
// src/Application.php
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\AuthenticationServiceInterface;
use Psr\Http\Message\ServerRequestInterface;

class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    // ...
}
```

**📖 See:** [Application Setup](https://book.cakephp.org/authentication/3/en/quick-start.html#application-setup) in official docs

## Step 3: Configure Middleware

Add the authentication middleware to your `Application` class:

```php
// src/Application.php
use Authentication\Middleware\AuthenticationMiddleware;

public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
{
    // ... other middleware

    $middlewareQueue->add(new AuthenticationMiddleware($this));

    // ... other middleware

    return $middlewareQueue;
}
```

**📖 See:** [Middleware Setup](https://book.cakephp.org/authentication/3/en/middleware.html) in official docs

## Step 4: Configure Authentication Service

Implement `getAuthenticationService()` in your `Application` class to configure how users authenticate:

```php
// src/Application.php
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\Identifier\AbstractIdentifier;
use Cake\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
{
    $service = new AuthenticationService();

    // Define where users should be redirected to when they are not authenticated
    $service->setConfig([
        'unauthenticatedRedirect' => Router::url([
            'prefix' => false,
            'plugin' => false,
            'controller' => 'Users',
            'action' => 'login',
        ]),
        'queryParam' => 'redirect',
    ]);

    $fields = [
        AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
        AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
    ];

    // Identifier configuration for password-based authentication
    $passwordIdentifier = [
        'Authentication.Password' => [
            'fields' => $fields,
            'resolver' => [
                'className' => 'Authentication.Orm',
                // Optional: Use custom finder for active users only
                // 'finder' => 'active',
            ],
        ],
    ];

    // Load the authenticators. Session should be first.
    $service->loadAuthenticator('Authentication.Session');

    $service->loadAuthenticator('Authentication.Form', [
        'identifier' => $passwordIdentifier,
        'fields' => $fields,
        'urlChecker' => 'Authentication.CakeRouter',
        'loginUrl' => Router::url([
            'prefix' => false,
            'plugin' => false,
            'controller' => 'Users',
            'action' => 'login',
        ]),
    ]);

    // Optional: Cookie authenticator for "remember me" functionality
    $service->loadAuthenticator('Authentication.Cookie', [
        'identifier' => $passwordIdentifier,
        'rememberMeField' => 'remember_me',
        'fields' => $fields,
        'urlChecker' => 'Authentication.CakeRouter',
        'loginUrl' => Router::url([
            'prefix' => false,
            'plugin' => false,
            'controller' => 'Users',
            'action' => 'login',
        ]),
    ]);

    return $service;
}
```

**Note:** Each authenticator has its own `identifier` configuration. The Session authenticator doesn't need one as it uses the stored identity.

**📖 See:** [Authentication Service](https://book.cakephp.org/authentication/3/en/authentication-service.html) in official docs

---

# Part 2: TinyAuth Features

## Step 5: Load TinyAuth Component

Now load the TinyAuth Authentication component in your controller:

```php
// src/Controller/AppController.php

public function initialize(): void
{
    parent::initialize();

    $this->loadComponent('TinyAuth.Authentication', [
        // TinyAuth-specific options (see Configuration section below)
    ]);
}
```

That's it for code changes! Now configure your public actions via INI file.

## Step 6: Configure auth_allow.ini

TinyAuth expects an `auth_allow.ini` file in your config directory. Use it to specify what actions are not protected by authentication.

### Basic Syntax

The section key syntax follows the CakePHP naming convention:
```
PluginName.MyPrefix/MyController
```

Create an entry for each action you want to expose:
- one or more action names
- the `*` wildcard to grant access to all actions of that controller
- use `"!actionName"` (quotes are important) to deny certain actions

### Example Configuration

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
>Nested prefixes are joined using `/`, e.g. `MyAdmin/Nested`.

### Deny Rules (Use with Caution)

Using only "granting" is recommended for security reasons. Careful with denying, as this can accidentally open up more than desired actions:

```ini
Users = "!secret",*
```
Meaning: Grant public access to all "Users" controller actions by default, but keep authentication required for "secret" action.

**Note:** Denying always trumps granting, if both are declared for an action.

### Multiple Files and Merging

You can specify multiple paths in your config, e.g. when you have plugins and separated the definitions across them. Make sure you are using each key only once, though. The first definition will be kept and all others for the same key are ignored.

### Template with Defaults

See the `config/` folder and the default template for popular plugins. You can copy out any default rules you want to use in your project.

## Quick Setups

TinyAuth offers a few quick setup options for common scenarios:

### Allow Non-Prefixed Actions

If you have non-prefixed controllers that you want to make public and keep prefixed ones protected:

```php
$this->loadComponent('TinyAuth.Authentication', [
    'allowNonPrefixed' => true,
]);
```

Any non-prefixed action will now be public by default. You can always set up "deny" rules for any action to protect a specific one from public access.

### Prefix-Based Allow

If you want to allow certain prefixes:

```php
$this->loadComponent('TinyAuth.Authentication', [
    'allowPrefixes' => [
        'MyPrefix',
        'Nested/Prefix',
    ],
]);
```

>**Note:** Prefixes are always `CamelCased` (even if routing makes them to `dashed-ones` in the URL).

**Careful:** Nested prefixes currently also match (and inherit) by parent. So if `MyPrefix` is allowed, `MyPrefix/Sub` and other nested ones would also automatically be allowed. You would need to explicitly set up deny rules here if needed.

## Mixing with Code

It is possible to have mixed INI and code rules. Those will get merged prior to authentication. So in case any of your controllers (or plugin controllers) contain such a statement, it will merge itself with your INI whitelist:

```php
// In your controller
use Cake\Event\EventInterface;

public function beforeFilter(EventInterface $event): void
{
    parent::beforeFilter($event);

    $this->Authentication->allowUnauthenticated(['index', 'view']);
}
```

This can be useful when migrating slowly to TinyAuth. Once you move such a code based rule into the INI file, you can safely remove those lines of code in your controller.

### allow() vs deny()

Since 1.11.0 you can also mix it with `deny()` calls. From how the Authentication component works, all `allow()` calls need be done before calling `deny()`. As such TinyAuth injects its list now before `Controller::beforeFilter()` gets called.

**Note:** It is advised to move away from these controller calls when possible.

## Caching

TinyAuth makes heavy use of caching to achieve optimal performance. By default, it will not use caching in debug mode.

To modify the caching behavior set the `autoClearCache` configuration option:

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

public function initialize(): void
{
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

TinyAuth Authentication component supports the following configuration options:

| Option         | Type   | Description                                                                                             |
|:---------------|:-------|:--------------------------------------------------------------------------------------------------------|
| autoClearCache | bool   | True will generate a new ACL cache file every time.                                                     |
| allowFilePath  | string | Full path to the INI file. Can also be an array of paths. <br/>Defaults to `ROOT . DS . 'config' . DS`. |
| allowFile      | string | Name of the INI file. Defaults to `auth_allow.ini`.                                                     |
| allowAdapter   | string | Class name, defaults to `IniAllowAdapter::class`.                                                       |

---

# Advanced Topics

## TinyAuth-Specific Enhancements

TinyAuth provides optional enhanced components you can use instead of the official ones:

### Enhanced Session Authenticator

**`TinyAuth.PrimaryKeySession`** (extends `Authentication.Session`):
- Stores only the user ID in session (not full user data)
- Fetches fresh user data from database on each request
- Keeps user data always up to date without re-login

**Usage in your `getAuthenticationService()` method:**

```php
// Use TinyAuth's enhanced PrimaryKeySession authenticator
// This stores only user ID in session and fetches fresh data from DB
$service->loadAuthenticator('TinyAuth.PrimaryKeySession', [
    'identifier' => [
        'Authentication.Token' => [
            'tokenField' => 'id',
            'dataField' => 'key',
            'resolver' => [
                'className' => 'Authentication.Orm',
                'finder' => 'active',
            ],
        ],
    ],
    'urlChecker' => 'Authentication.CakeRouter',
]);
```

**📖 See:** [Session Authenticator](https://book.cakephp.org/authentication/3/en/authenticators.html#session) in official docs for base functionality

### Session Caching with PrimaryKeySession

When using `PrimaryKeySession` authenticator (which fetches user data from DB on each request), consider adding caching:

```php
// After user updates their profile data
use TinyAuth\Utility\SessionCache;

SessionCache::delete($userId);
```

This invalidates the cache so fresh data is fetched on the next request.

### Enhanced Redirect Handlers

TinyAuth provides redirect handlers with flash message support:

**`TinyAuth.ForbiddenCakeRedirect`** - Works with CakePHP routing:

```php
$service->setConfig([
    'unauthenticatedRedirect' => ['controller' => 'Users', 'action' => 'login'],
    'unauthorizedHandler' => [
        'className' => 'TinyAuth.ForbiddenCakeRedirect',
        'unauthorizedMessage' => 'Please login to continue.',
    ],
]);
```

**`TinyAuth.ForbiddenRedirect`** - Uses direct URLs:

```php
$service->setConfig([
    'unauthenticatedRedirect' => '/users/login',
    'unauthorizedHandler' => [
        'className' => 'TinyAuth.ForbiddenRedirect',
        'unauthorizedMessage' => 'Access denied.',
    ],
]);
```

**Features:**
- Automatically sets flash error messages
- Skips JSON/XML requests (throws exception instead)
- Preserves target URL in query parameter for post-login redirect

You can use the official handlers instead if you prefer.

## Adding LoginLink Authenticator

For one-click token login (e.g., email verification, password reset), you can add the LoginLink authenticator from the [dereuromark/cakephp-tools](https://github.com/dereuromark/cakephp-tools) plugin:

```php
// Add to your getAuthenticationService() method after other authenticators

// This is a one-click token login as optional addition
$service->loadAuthenticator('Tools.LoginLink', [
    'identifier' => [
        'Tools.LoginLink' => [
            'resolver' => [
                'className' => 'Authentication.Orm',
                'finder' => 'active',
            ],
            'preCallback' => function (int $id) {
                // Optional: Perform action on successful login (e.g., confirm email)
                TableRegistry::getTableLocator()->get('Users')->confirmEmail($id);
            },
        ],
    ],
    'urlChecker' => 'Authentication.CakeRouter',
    'loginUrl' => Router::url([
        'prefix' => false,
        'plugin' => false,
        'controller' => 'Users',
        'action' => 'login',
    ]),
]);
```

**Note:** Requires `composer require dereuromark/cakephp-tools`

**📖 See:** [Tools Plugin LoginLink](https://github.com/dereuromark/cakephp-tools/blob/master/docs/Authentication/LoginLink.md)

## Accessing User Identity

Get the authenticated user from the AuthUser component or helper:

```php
// In controller
$user = $this->AuthUser->identity();
$userId = $this->AuthUser->id();

// In template
$user = $this->AuthUser->identity();
```

---

# Additional Resources

- **📖 Official Authentication Docs:** [book.cakephp.org/authentication/3](https://book.cakephp.org/authentication/3/en/index.html)
- **🔐 Authorization:** Add role-based access control - See [Authorization.md](Authorization.md)
- **❓ Troubleshooting:** See [docs/README.md](README.md#troubleshooting)
