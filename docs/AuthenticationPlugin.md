### Authentication plugin support

Support for [Authentication](https://github.com/cakephp/authentication) usage.

Instead of the Auth component you load the Authentication one:

```php
$this->loadComponent('TinyAuth.Authentication', [
    ...
]);
```
