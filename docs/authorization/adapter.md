# Authorization adapters

The default `TinyAuth\Auth\AclAdapter\IniAclAdapter` reads `auth_acl.ini`. To use a PHP file instead, configure the adapter and file name together in `config/app.php`:

```php
'TinyAuth' => [
    'aclAdapter' => \TinyAuth\Auth\AclAdapter\PhpAclAdapter::class,
    'aclFile' => 'auth_acl.php',
],
```

Create `config/auth_acl.php` with a returned array:

```php
<?php

return [
    'Articles' => [
        'index' => 'user, admin',
        'edit' => 'admin',
    ],
];
```

PHP adapters use the same controller identifiers and comma-separated values as INI adapters. They do not take the normalized `allow` / `deny` structure returned by custom adapters.

For a database-backed implementation, see [Custom adapters](/guide/custom-adapters). Clear the [rule cache](/guide/configuration#cache-busting) after changing either file.
