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

INI files are parsed once and cached. After deploys, clear the cache:

```bash
bin/cake cache clear_all
```

In **debug mode** the cache is bypassed automatically — every request re-reads the INI files, so iteration is fast.

The cache engine is `_cake_core_` and the prefix is `tiny_auth_`. To clear from code:

```php
\TinyAuth\Utility\Cache::clear();
```

## Custom adapters

You can swap the INI file backends entirely using the `allowAdapter` (authentication) and `aclAdapter` (authorization) configs. This lets you read rules from a database, a remote API, or any other source.

Existing options:

- **[TinyAuthBackend plugin](https://github.com/dereuromark/cakephp-tinyauth-backend)** — admin GUI that stores allow / ACL rules in the database.

For writing your own adapter, see [Authentication Adapter](/authentication/adapter) and [Authorization Adapter](/authorization/adapter).
