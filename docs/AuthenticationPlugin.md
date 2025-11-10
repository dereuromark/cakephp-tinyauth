# TinyAuth with CakePHP Authentication Plugin

This guide shows how to integrate TinyAuth with the official [CakePHP Authentication plugin](https://github.com/cakephp/authentication).

## Overview

TinyAuth's Authentication component wraps the official plugin and adds INI-based configuration for public actions. This page covers the **official plugin setup** required before using TinyAuth features.

## Step 1: Install the Official Plugin

```bash
composer require cakephp/authentication
```

## Step 2: Learn the Official Plugin

**📖 Official Documentation:** [book.cakephp.org/authentication/3](https://book.cakephp.org/authentication/3/en/index.html)

GitHub Repository: [github.com/cakephp/authentication](https://github.com/cakephp/authentication)

## Step 3: Implement Authentication Interface

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

## Step 4: Configure Middleware

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

## Step 5: Configure Authentication Service

Implement `getAuthenticationService()` in your `Application` class to configure how users authenticate:

```php
// src/Application.php
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Psr\Http\Message\ServerRequestInterface;

public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
{
    $service = new AuthenticationService();

    // Configure redirect for unauthenticated users
    $service->setConfig([
        'unauthenticatedRedirect' => '/users/login',
        'queryParam' => 'redirect',
    ]);

    // Load authenticators - Session should be first
    $service->loadAuthenticator('Authentication.Session');
    $service->loadAuthenticator('Authentication.Form', [
        'fields' => [
            'username' => 'email',
            'password' => 'password',
        ],
        'loginUrl' => '/users/login',
    ]);

    // Load identifiers
    $service->loadIdentifier('Authentication.Password', [
        'fields' => [
            'username' => 'email',
            'password' => 'password',
        ],
    ]);

    return $service;
}
```

**📖 See:** [Authentication Service](https://book.cakephp.org/authentication/3/en/authentication-service.html) in official docs

## Step 6: Load TinyAuth Component

Now load the TinyAuth Authentication component in your controller:

```php
// src/Controller/AppController.php

public function initialize(): void
{
    parent::initialize();

    $this->loadComponent('TinyAuth.Authentication', [
        // TinyAuth-specific options (see Authentication.md)
    ]);
}
```

## Step 7: Configure INI File

Create `config/auth_allow.ini` to define public actions. See [Authentication.md](Authentication.md) for details.

## TinyAuth-Specific Enhancements

TinyAuth provides optional enhanced components you can use instead of the official ones:

### Enhanced Session Authenticator

**`TinyAuth.PrimaryKeySession`** (extends `Authentication.Session`):
- Stores only the user ID in session (not full user data)
- Fetches fresh user data from database on each request
- Keeps user data always up to date without re-login

**Usage:**
```php
$service->loadAuthenticator('TinyAuth.PrimaryKeySession');
```

**📖 See:** [Session Authenticator](https://book.cakephp.org/authentication/3/en/authenticators.html#session) in official docs for base functionality

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

## Complete Example with TinyAuth Enhancements

Here's a complete example using TinyAuth's enhanced authenticator and handlers:

```php
// src/Application.php
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Psr\Http\Message\ServerRequestInterface;
use Cake\Routing\Router;

public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
{
    $service = new AuthenticationService();

    // Configure redirects with TinyAuth enhanced handler
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
        'username' => 'email',
        'password' => 'password',
    ];

    // Use TinyAuth's enhanced PrimaryKeySession authenticator
    $service->loadAuthenticator('TinyAuth.PrimaryKeySession', [
        'identify' => true,
        'fields' => $fields,
    ]);

    // Standard Form authenticator for login
    $service->loadAuthenticator('Authentication.Form', [
        'fields' => $fields,
        'loginUrl' => Router::url([
            'controller' => 'Users',
            'action' => 'login',
        ]),
    ]);

    // Optional: Cookie for "remember me" functionality
    $service->loadAuthenticator('Authentication.Cookie', [
        'fields' => $fields,
        'rememberMeField' => 'remember_me',
    ]);

    // Load identifier
    $service->loadIdentifier('Authentication.Password', [
        'fields' => $fields,
        'resolver' => [
            'className' => 'Authentication.Orm',
            // Optional: Use custom finder
            // 'finder' => 'active',
        ],
    ]);

    return $service;
}
```

## Advanced Topics

### Session Caching with PrimaryKeySession

When using `PrimaryKeySession` authenticator (which fetches user data from DB on each request), consider adding caching:

```php
// After user updates their profile data
use TinyAuth\Utility\SessionCache;

SessionCache::delete($userId);
```

This invalidates the cache so fresh data is fetched on the next request.

### Accessing User Identity

Get the authenticated user from the AuthUser component or helper:

```php
// In controller
$user = $this->AuthUser->identity();
$userId = $this->AuthUser->id();

// In template
$user = $this->AuthUser->identity();
```

## Next Steps

After completing the setup above:

1. ✅ Official plugin is now configured (Steps 1-5 complete)
2. 📄 Complete Step 7: Configure `auth_allow.ini` - See [Authentication.md](Authentication.md)
3. 🔐 Optionally add authorization - See [AuthorizationPlugin.md](AuthorizationPlugin.md)

## Additional Resources

- **📖 Official Authentication Docs:** [book.cakephp.org/authentication/3](https://book.cakephp.org/authentication/3/en/index.html)
- **🔧 TinyAuth Authentication:** [Authentication.md](Authentication.md)
- **❓ Troubleshooting:** See [docs/README.md](README.md#troubleshooting)
