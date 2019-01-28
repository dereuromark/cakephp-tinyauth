### Authorization plugin support

Support for [Authorization](https://github.com/cakephp/authorization) usage.

Instead of the Auth component you load the Authorization one:

```php
$this->loadComponent('TinyAuth.Authorization', [
    ...
]);
```
