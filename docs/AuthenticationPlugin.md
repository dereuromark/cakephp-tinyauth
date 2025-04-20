### Authentication plugin support

Support for [Authentication plugin](https://github.com/cakephp/authentication) usage.

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

Then make sure to load your Authenticators, e.g.

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

        // Load identifiers
        $service->loadIdentifier('Authentication.Password', [
            'fields' => $fields,
            'resolver' => [
                'className' => 'Authentication.Orm',
                'finder' => 'active',
            ],
        ]);
        $service->loadIdentifier('Authentication.Token', [
            'tokenField' => 'id',
            'dataField' => 'key',
            'resolver' => [
                'className' => 'Authentication.Orm',
                'finder' => 'active',
            ],
        ]);

        // Load the authenticators. Session should be first.
        $service->loadAuthenticator('TinyAuth.Session', [
            'urlChecker' => 'Authentication.CakeRouter',
        ]);
        $service->loadAuthenticator('Authentication.Form', [
            'urlChecker' => 'Authentication.CakeRouter',
            'fields' => $fields,
            'loginUrl' => [
                'prefix' => false,
                'plugin' => false,
                'controller' => 'Account',
                'action' => 'login',
            ],
        ]);
        $service->loadAuthenticator('Authentication.Cookie', [
            'urlChecker' => 'Authentication.CakeRouter',
            'rememberMeField' => 'remember_me',
            'fields' => [
                'username' => 'email',
                'password' => 'password',
            ],
            'loginUrl' => [
                'prefix' => false,
                'plugin' => false,
                'controller' => 'Account',
                'action' => 'login',
            ],
        ]);

        // This is a one click token login as optional addition
        $service->loadIdentifier('Tools.LoginLink', [
            'resolver' => [
                'className' => 'Authentication.Orm',
                'finder' => 'active',
            ],
            'preCallback' => function (int $id) {
                TableRegistry::getTableLocator()->get('Users')->confirmEmail($id);
            },
        ]);
        $service->loadAuthenticator('Tools.LoginLink', [
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


For all the rest follow the plugin's documentation.

Then you use the [Authentication documentation](Authentication.md) to fill your INI config file.
