# Authentication adapters

The default `TinyAuth\Auth\AllowAdapter\IniAllowAdapter` reads `auth_allow.ini`. To use a PHP file instead, configure the adapter and file name together in `config/app.php`:

```php
'TinyAuth' => [
    'allowAdapter' => \TinyAuth\Auth\AllowAdapter\PhpAllowAdapter::class,
    'allowFile' => 'auth_allow.php',
],
```

Create `config/auth_allow.php` with a returned array:

```php
<?php

return [
    'Users' => 'login, register',
    'Admin/Users' => 'login',
];
```

PHP adapters use the same controller identifiers and comma-separated values as INI adapters. They do not take the normalized `allow` / `deny` structure returned by custom adapters.

For a database-backed implementation, see [Custom adapters](/guide/custom-adapters). Clear the [rule cache](/guide/configuration#cache-busting) after changing either file.
