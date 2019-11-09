### Authentication plugin support

Support for [Authentication plugin](https://github.com/cakephp/authentication) usage.

Instead of the core Auth component you load the Authentication component:

```php
$this->loadComponent('TinyAuth.Authentication', [
    ...
]);
```

Make sure you load the middleware:
```php
use Authentication\Middleware\AuthenticationMiddleware;

// in Application::middleware()
$middlewareQueue->add(new AuthenticationMiddleware($this));
```

For all the rest just follow the plugin's documentation.

Then you use the [Authentication documentation](Authentication.md) to fill your INI config file.
