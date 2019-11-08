### Authorization plugin support

Support for [Authorization plugin](https://github.com/cakephp/authorization) usage.

Instead of the core Auth component you load the Authorization component:

```php
$this->loadComponent('TinyAuth.Authorization', [
    ...
]);
```

For all the rest just follow the plugin's documentation.

Then you use the [Authorization documention](Authorization.md) to set up roles and fill your INI config file.
