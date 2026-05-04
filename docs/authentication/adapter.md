### Authentication Adapters
For adapters to define allow/deny (public/protected) per controller action.

#### Built-in adapters

| Adapter | Class | Default file | Notes |
|---------|-------|--------------|-------|
| INI     | `TinyAuth\Auth\AllowAdapter\IniAllowAdapter` | `auth_allow.ini` | Default. Zero dependencies. |
| PHP     | `TinyAuth\Auth\AllowAdapter\PhpAllowAdapter` | `auth_allow.php` | Returns a plain `return [...]` array. Zero dependencies. |

Switch the adapter via the `allowAdapter` configuration key, e.g.:

```php
'TinyAuth' => [
    'allowAdapter' => \TinyAuth\Auth\AllowAdapter\PhpAllowAdapter::class,
    'allowFile' => 'auth_allow.php',
],
```

The PHP file uses the same section/value shape as the INI variant — top-level keys are `Plugin.Prefix/Controller` identifiers and values are comma-separated action lists.

#### Custom adapters

Implement the AllowAdapterInterface and make sure your `getAllow()` method returns an array like so:
```php
    // normal controller
    'Users' => [
        'plugin' => null,
        'prefix' => null,
        'controller' => 'Users',
        'deny' => [],
        'allow' => [
            'index',
            'view',
        ]
    ],
    // or with admin prefix
    'admin/Users' => [
        'plugin' => null,
        'prefix' => 'Admin',
        'controller' => 'Users',
        'deny' => [],
        'allow' => [
            'index',
        ],
    ],
    // plugin controller
    'Extras.Offers' => [
        'plugin' => 'Extras',
        'prefix' => null,
        'controller' => 'Offers',
        'deny' => [
            'superPrivate',
        ],
        'allow' => [
            '*',
        ],
    ],
    ...
```

Unique array keys due to the internal `PluginName.Prefix/ControllerName` syntax.
URL elements and then an array of actions mapped to their role id(s).
The `*` action key means "any" action.

With this you can easily built your own database adapter and manage your ACL via backend.
Make sure you bust the cache with each update/change, though.

Note: Some adapters may contain `map` data which is for debugging only (returns the original data).
