# TinyAuth with CakePHP Authorization Plugin

This guide shows how to integrate TinyAuth with the official [CakePHP Authorization plugin](https://github.com/cakephp/authorization).

## Overview

TinyAuth's Authorization component wraps the official plugin and adds INI-based configuration for role-based access control (RBAC). This page covers the **official plugin setup** required before using TinyAuth features.

## Step 1: Install the Official Plugin

```bash
composer require cakephp/authorization
```

## Step 2: Learn the Official Plugin

**📖 Official Documentation:** [book.cakephp.org/authorization/2](https://book.cakephp.org/authorization/2/en/index.html)

GitHub Repository: [github.com/cakephp/authorization](https://github.com/cakephp/authorization)

**Key Topics from Official Docs:**
- Authorization service and middleware setup
- Policy-based authorization (ORM policies)
- Request policies for controller actions
- Identity authorization
- Scoping results

## Step 3: Implement Authorization Interface

Your `Application` class must implement `AuthorizationServiceProviderInterface`:

```php
// src/Application.php
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\AuthorizationServiceInterface;
use Psr\Http\Message\ServerRequestInterface;

class Application extends BaseApplication implements AuthorizationServiceProviderInterface
{
    // ...
}
```

**Note:** If you're also using Authentication, implement both interfaces:
```php
class Application extends BaseApplication
    implements AuthenticationServiceProviderInterface, AuthorizationServiceProviderInterface
{
    // ...
}
```

**📖 See:** [Getting Started](https://book.cakephp.org/authorization/2/en/getting-started.html) in official docs

## Step 4: Configure Middleware

Add **both** Authorization and TinyAuth-specific middlewares to your `Application` class:

```php
// src/Application.php
use Authorization\Middleware\AuthorizationMiddleware;
use TinyAuth\Middleware\RequestAuthorizationMiddleware;

public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
{
    // ... other middleware (including Authentication)

    // 1. Official Authorization middleware
    $middlewareQueue->add(new AuthorizationMiddleware($this));

    // 2. TinyAuth Request Authorization middleware for INI-based RBAC
    $middlewareQueue->add(new RequestAuthorizationMiddleware([
        'unauthorizedHandler' => [
            'className' => 'TinyAuth.ForbiddenCakeRedirect',
            'url' => ['controller' => 'Users', 'action' => 'login'],
            'unauthorizedMessage' => 'You are not authorized to access that location.',
        ],
    ]));

    // ... other middleware

    return $middlewareQueue;
}
```

**📖 See:** [Middleware Setup](https://book.cakephp.org/authorization/2/en/middleware.html) in official docs

## Step 5: Configure Authorization Service

Implement `getAuthorizationService()` in your `Application` class to set up the TinyAuth request policy:

```php
// src/Application.php
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\Policy\MapResolver;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TinyAuth\Policy\RequestPolicy;

public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
{
    // Map ServerRequest to TinyAuth's RequestPolicy for INI-based RBAC
    $mapResolver = new MapResolver();
    $mapResolver->map(ServerRequest::class, new RequestPolicy());

    return new AuthorizationService($mapResolver);
}
```

This configures the request policy that reads your `auth_acl.ini` file for role-based permissions.

**📖 See:** [Authorization Service](https://book.cakephp.org/authorization/2/en/getting-started.html#authorization-service) in official docs

## Step 6: Load TinyAuth Component

Load the Authorization component in your controller:

```php
// src/Controller/AppController.php

public function initialize(): void
{
    parent::initialize();

    $this->loadComponent('TinyAuth.Authorization', [
        // Configuration options (see Authorization.md)
    ]);
}
```

## Step 7: Set Up Roles and Configure INI File

1. Set up user roles - See [Authorization.md#roles](Authorization.md#roles)
2. Create `config/auth_acl.ini` to define role permissions - See [Authorization.md](Authorization.md)

## TinyAuth-Specific Enhancements

### Enhanced Redirect Handlers

TinyAuth provides redirect handlers with flash message support:

**`TinyAuth.ForbiddenCakeRedirect`** - Works with CakePHP routing arrays:
```php
'unauthorizedHandler' => [
    'className' => 'TinyAuth.ForbiddenCakeRedirect',
    'url' => ['controller' => 'Users', 'action' => 'login'],
    'queryParam' => 'redirect',
    'statusCode' => 302,
    'unauthorizedMessage' => 'You need permission to access that page.',
]
```

**`TinyAuth.ForbiddenRedirect`** - Uses direct URLs:
```php
'unauthorizedHandler' => [
    'className' => 'TinyAuth.ForbiddenRedirect',
    'url' => '/',
    'queryParam' => 'redirect',
    'statusCode' => 302,
    'unauthorizedMessage' => 'Access denied.',
]
```

**Features:**
- Automatically sets flash error messages
- Skips JSON/XML requests (throws exception instead)
- Preserves target URL in query parameter for post-login redirect
- Set `unauthorizedMessage` to `false` to disable flash messages

You can use the official handlers instead if you prefer.

## Advanced Topics

### Controller-Specific Authorization

In some cases, you may want to apply authorization at the controller level instead of globally. Move `RequestAuthorizationMiddleware` from `Application` to your controller:

```php
// src/Controller/AppController.php

public function initialize(): void
{
    parent::initialize();

    // Apply authorization middleware at controller level
    $this->middleware(function (ServerRequest $request, $handler): ResponseInterface {
        $config = [
            'unauthorizedHandler' => [
                'className' => 'TinyAuth.ForbiddenCakeRedirect',
                'url' => [
                    'controller' => 'Users',
                    'action' => 'login',
                ],
            ],
        ];
        $middleware = new RequestAuthorizationMiddleware($config);

        return $middleware->process($request, $handler);
    });
}
```

**Note:** If you experience redirect loops, wrap the middleware in `if ($this->AuthUser->id()) {}` since authentication must complete before authorization can run.

### Tracking User Login

You can update user data on login (e.g., `last_login` timestamp) by adding a method to your UsersTable:

```php
// src/Model/Table/UsersTable.php
use Authentication\Authenticator\ResultInterface;
use DateTime;

public function loginUpdate(ResultInterface $result): void
{
    $user = $result->getData();
    $this->updateAll(['last_login' => new DateTime()], ['id' => $user->id]);
}
```

Then call it in your login action:

```php
// src/Controller/UsersController.php

public function login()
{
    $result = $this->Authentication->getResult();
    if ($result && $result->isValid()) {
        $this->fetchTable('Users')->loginUpdate($result);
        $this->Flash->success(__('You are now logged in.'));

        return $this->redirect($this->Authentication->getLoginRedirect() ?? '/');
    }
    // ... rest of login logic
}
```

## Next Steps

After completing the setup above:

1. ✅ Official plugin is now configured
2. 👥 Complete Step 7: Set up roles - See [Authorization.md#roles](Authorization.md#roles)
3. 📄 Complete Step 7: Configure `auth_acl.ini` - See [Authorization.md](Authorization.md)
4. 🧪 Test your authorization rules

## Additional Resources

- **📖 Official Authorization Docs:** [book.cakephp.org/authorization/2](https://book.cakephp.org/authorization/2/en/index.html)
- **🔧 TinyAuth Authorization:** [Authorization.md](Authorization.md)
- **❓ Troubleshooting:** See [docs/README.md](README.md#troubleshooting)

