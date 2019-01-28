### Authentication plugin support

Support for [Authentication plugin](https://github.com/cakephp/authentication) usage.

Instead of the Auth component you load the Authentication one:

```php
$this->loadComponent('TinyAuth.Authentication', [
    ...
]);
```

For all the rest just follow the plugin's documentation.

Then you use the [Authentication documention](Authentication.md) to fill your config file.
