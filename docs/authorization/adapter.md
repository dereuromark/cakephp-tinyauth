### Authorization Adapters
For RBAC ACL adapters.

#### Built-in adapters

| Adapter | Class | Default file | Notes |
|---------|-------|--------------|-------|
| INI     | `TinyAuth\Auth\AclAdapter\IniAclAdapter` | `auth_acl.ini` | Default. Zero dependencies. |
| PHP     | `TinyAuth\Auth\AclAdapter\PhpAclAdapter` | `auth_acl.php` | Returns a plain `return [...]` array. Zero dependencies. |

Switch the adapter via the `aclAdapter` configuration key, e.g.:

```php
'TinyAuth' => [
    'aclAdapter' => \TinyAuth\Auth\AclAdapter\PhpAclAdapter::class,
    'aclFile' => 'auth_acl.php',
],
```

The PHP file uses the same section/key/value shape as the INI variant — top-level keys are `Plugin.Prefix/Controller` identifiers and each section maps action names (or comma-separated action lists) to comma-separated role lists.

#### Custom adapters

Implement the AclAdapterInterface and make sure your `getAcl()` method returns an array like so:
```php
    // normal controller
    'Posts' => [
        'plugin' => null,
        'prefix' => null,
        'controller' => 'Posts',
        'allow' => [
            // action to role id mapping
        ],
        'deny => [
            // action to role id mapping
        ],
    ],
    // or plugin with admin prefix
    'Queue.admin/QueuedJobs' => [
        'plugin' => 'Queue',
        'prefix' => 'Admin',
        'controller' => 'QueuedJobs',
        'allow' => [
            'index' => [
                'user' => 1,
            ],
            'view' => [
                'user' => 1,
            ],
            '*' => [
                'admin' => 3,
            ],
        ],
    ],
    ...
```

Unique array keys due to the internal `PluginName.Prefix/ControllerName` syntax.
URL elements and then an array of actions mapped to their role id(s).
The `*` action key means 'any'.

With this you can easily built your own database adapter and manage your ACL via backend.
Make sure you bust the cache with each update/change, though.

Note: Some adapters may contain `map` data which is for debugging only (returns the original data).
