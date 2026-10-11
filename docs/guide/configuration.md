# Configuration

## Shared Configure keys

The Authentication component, Authorization component, and AuthUser helper share several configs. Set them once in `config/app.php` instead of per-component:

```php
'TinyAuth' => [
    'multiRole' => true,
    // ...other shared keys
],
```

Each topic page lists the keys it actually reads (see [Authentication](/authentication/) and [Authorization](/authorization/)).

## Cache busting

Parsed rules are cached with their adapter and configuration context. A component using another rule file or role source cannot reuse permissions cached for a different configuration. Keep shared rule sources in the global configuration to avoid repeatedly replacing cached rules. After deploys, clear the cache:

```bash
bin/cake cache clear_all
```

In debug mode, `autoClearCache` defaults to enabled. Set it to `false` to retain cached rules during development.

The default cache configuration is `_cake_model_` and the prefix is `tiny_auth_`. For compatibility, an already loaded `_cake_core_` cache engine is used instead when present. Set `TinyAuth.cache` explicitly to choose a configured cache. To clear from code:

```php
\TinyAuth\Utility\Cache::clear();
```

## Custom adapters

You can swap the INI file backends entirely using the `allowAdapter` (authentication) and `aclAdapter` (authorization) configs. This lets you read rules from a database, a remote API, or any other source.

Existing options:

- [TinyAuthBackend plugin](https://github.com/dereuromark/cakephp-tinyauth-backend): admin GUI that stores allow / ACL rules in the database.

For writing your own adapter, see [Authentication Adapter](/authentication/adapter) and [Authorization Adapter](/authorization/adapter).
