### Authorization plugin support

Support for [Authorization plugin](https://github.com/cakephp/authorization) usage.

Instead of the Auth component you load the Authorization one:

```php
$this->loadComponent('TinyAuth.Authorization', [
    ...
]);
```

For all the rest just follow the plugin's documentation.

Then you use the [Authorization documention](Authorization.md) to fill your config file.
