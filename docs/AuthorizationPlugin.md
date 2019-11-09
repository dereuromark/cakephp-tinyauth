### Authorization plugin support

Support for [Authorization plugin](https://github.com/cakephp/authorization) usage.

Instead of the core Auth component you load the Authorization component:

```php
$this->loadComponent('TinyAuth.Authorization', [
    ...
]);
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

Then you use the [Authorization documention](Authorization.md) to set up roles and fill your INI config file.
