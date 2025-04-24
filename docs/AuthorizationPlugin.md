### Authorization plugin support

Support for [Authorization plugin](https://github.com/cakephp/authorization) usage.

Instead of the core Auth component you load the Authorization component:

```php
$this->loadComponent('TinyAuth.Authorization', [
    ...
]);
```

And in your `Application` class you need to load both `Authorization` and TinyAuth specific
`RequestAuthorization` middlewares in that order:

```php
use Authorization\Middleware\AuthorizationMiddleware;
use TinyAuth\Middleware\RequestAuthorizationMiddleware;

// in Application::middleware()
$middlewareQueue->add(new AuthorizationMiddleware($this, [
    'unauthorizedHandler' => [
        'className' => 'Authorization.CakeRedirect',
        'url' => [
            ...
        ],
    ],
]));
$middlewareQueue->add(new RequestAuthorizationMiddleware([
    'unauthorizedHandler' => [
        'className' => 'TinyAuth.ForbiddenCakeRedirect',
        'url' => [
            ...
        ],
        'unauthorizedMessage' => '...',
    ],
])));
```

For all the rest just follow the plugin's documentation.

For your resolver you need to use this map inside `Application::getAuthorizationService()`:
```php
use TinyAuth\Policy\RequestPolicy;

/**
 * @param \Psr\Http\Message\ServerRequestInterface $request
 * @param \Psr\Http\Message\ResponseInterface $response
 *
 * @return \Authorization\AuthorizationServiceInterface
 */
public function getAuthorizationService(ServerRequestInterface $request, ResponseInterface $response) {
    $map = [
        ServerRequest::class => new RequestPolicy(),
    ];
    $resolver = new MapResolver($map);

    return new AuthorizationService($resolver);
}
```

Then you use the [Authorization documentation](Authorization.md) to set up roles and fill your INI config file.

#### Tips

You can add loginUpdate() method to your UsersTable to update the user's data here accordingly:

```php
    /**
     * @param \Authentication\Authenticator\ResultInterface $result
     *
     * @return void
     */
    public function loginUpdate(ResultInterface $result): void
    {
        /** @var \App\Model\Entity\User $user */
        $user = $result->getData();
        $this->updateAll(['last_login' => new DateTime()], ['id' => $user->id]);
    }
```
Then hook it in:
```php
// Inside your AccountController::login() method
    $result = $this->Authentication->getResult();
    // If the user is logged in send them away.
    if ($result && $result->isValid()) {
        $this->Users->loginUpdate($result);
        $target = $this->Authentication->getLoginRedirect() ?? '/';
        $this->Flash->success(__('You are now logged in.'));

        return $this->redirect($target);
    }
```
