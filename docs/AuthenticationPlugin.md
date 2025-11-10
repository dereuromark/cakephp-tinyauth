### Authentication plugin support

Support for [Authentication plugin](https://github.com/cakephp/authentication) usage.

## Installation

First, you need to install the official CakePHP Authentication plugin:

```bash
composer require cakephp/authentication
```

See the [official Authentication plugin documentation](https://book.cakephp.org/authentication/2/en/index.html) for more details.

## Setup

Instead of the core Auth component you load the Authentication component:

```php
$this->loadComponent('TinyAuth.Authentication', [
    ...
]);
```

Make sure you load the middleware in your `Application` class:
```php
use Authentication\Middleware\AuthenticationMiddleware;

// in Application::middleware()
$middlewareQueue->add(new AuthenticationMiddleware($this));
```

This plugin ships with optional enhanced components:

### Session Authenticator

- **PrimaryKeySession** (extending `Authentication.Session`): Stores only the ID in session and fetches the rest from DB on each request, keeping data always up to date

### Unauthorized Handlers

TinyAuth provides enhanced redirect handlers for authentication failures:

- **TinyAuth.ForbiddenCakeRedirect**: Works with CakePHP routing and allows setting an `unauthorizedMessage` as flash message
- **TinyAuth.ForbiddenRedirect**: Standard URL-based redirect with flash message support

Both handlers:
- Automatically set flash error messages for unauthorized access
- Skip JSON/XML requests (throw exception instead of redirecting)
- Preserve the target URL in query parameter for redirect after login

You can, of course, stick to the official ones as well.

Now let's set up `getAuthenticationService()` and make sure to load all needed Authenticators, e.g.:

```php
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request Request
     *
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();

        // Define where users should be redirected to when they are not authenticated
        $service->setConfig([
            'unauthenticatedRedirect' => Router::url([
                'prefix' => false,
                'plugin' => false,
                'controller' => 'Account',
                'action' => 'login',
            ]),
            'queryParam' => 'redirect',
        ]);

        $fields = [
            AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
            AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
        ];
        $passwordIdentifier = [
            'Authentication.Password' => [
                'fields' => $fields,
                'resolver' => [
                    'className' => 'Authentication.Orm',
                    'finder' => 'active',
                ],
            ],
        ];

        // Load the authenticators. Session should be first.
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
        $service->loadAuthenticator('Authentication.Form', [
            'identifier' => $passwordIdentifier,
            'fields' => $fields,
            'urlChecker' => 'Authentication.CakeRouter',
            'loginUrl' => [
                'prefix' => false,
                'plugin' => false,
                'controller' => 'Account',
                'action' => 'login',
            ],
        ]);
        $service->loadAuthenticator('Authentication.Cookie', [
            'identifier' => $passwordIdentifier,
            'rememberMeField' => 'remember_me',
            'fields' => $fields,
            'urlChecker' => 'Authentication.CakeRouter',
            'loginUrl' => [
                'prefix' => false,
                'plugin' => false,
                'controller' => 'Account',
                'action' => 'login',
            ],
        ]);

        // This is a one click token login as optional addition
        $service->loadAuthenticator('Tools.LoginLink', [
            'identifier' => [
                'Tools.LoginLink' => [
                    'resolver' => [
                        'className' => 'Authentication.Orm',
                        'finder' => 'active',
                    ],
                    'preCallback' => function (int $id) {
                        TableRegistry::getTableLocator()->get('Users')->confirmEmail($id);
                    },
                ],
            ],
            'urlChecker' => 'Authentication.CakeRouter',
            'loginUrl' => [
                'prefix' => false,
                'plugin' => false,
                'controller' => 'Account',
                'action' => 'login',
            ],
        ]);

        return $service;
    }
```


You can always get the identity result (User entity) from the AuthUser component and helper:
```php
$this->AuthUser->identity();
```


### Caching
Especially when you use the PrimaryKeySession authenticator and always pulling the live data
from DB, you might want to consider adding a short-lived cache in between.
The authenticator supports this directly:

In this case you need to manually invalidate the session cache every time a user modifies some of their
data that is part of the session (e.g. username, email, roles, birthday, ...).
For that you can use the following after the change was successful:
```php
use TinyAuth\Utility\SessionCache;

SessionCache::delete($userId);
```
This will force the session to be pulled (the ID), and the cache refilled with up-to-date data.


---


For all the rest, follow the plugin's documentation.

Then you use the [Authentication documentation](Authentication.md) to fill your INI config file.
