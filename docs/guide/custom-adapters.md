# Custom Adapters

TinyAuth's two INI-based stores (`auth_allow.ini` for public actions, `auth_acl.ini` for role permissions) are just the default backends. You can swap either one out for a database-driven, API-driven, or any other source by implementing a small interface.

## When you'd want a custom adapter

- **Live editing** — admins editing rules from a UI rather than redeploying a file. The [TinyAuthBackend plugin](https://github.com/dereuromark/cakephp-tinyauth-backend) already provides this.
- **Multi-tenant rules** — different rule sets per host / customer / region.
- **Remote source** — central auth service, central feature flags, etc.

If your rules are stable and per-environment, the INI files are usually fine. Don't overbuild this.

## Authentication: the AllowAdapterInterface

```php
namespace TinyAuth\Auth\AllowAdapter;

interface AllowAdapterInterface {
    public function getAllow(array $config): array;
}
```

`getAllow()` returns the **public-action whitelist**, keyed by section. The reference implementation is [`IniAllowAdapter`](https://github.com/dereuromark/cakephp-tinyauth/blob/master/src/Auth/AllowAdapter/IniAllowAdapter.php).

Each entry should look like:

```php
[
    'Articles' => [
        'controller' => 'Articles',
        'plugin' => null,
        'prefix' => null,
        'allow' => ['index', 'view'],
        'deny' => [],
    ],
    'Admin/Users' => [
        'controller' => 'Users',
        'plugin' => null,
        'prefix' => 'Admin',
        'allow' => ['login'],
        'deny' => [],
    ],
];
```

`controller`, `plugin`, `prefix` come from parsing the section key (e.g. `MyPlugin.Admin/Articles` → `plugin='MyPlugin'`, `prefix='Admin'`, `controller='Articles'`). The `Utility::deconstructIniKey()` helper does this for you.

`allow` is the list of public actions. `deny` is the list of explicitly-denied actions (using the `!action` syntax in INI).

## Authorization: the AclAdapterInterface

```php
namespace TinyAuth\Auth\AclAdapter;

interface AclAdapterInterface {
    public function getAcl(array $availableRoles, array $config): array;
}
```

`getAcl()` returns the **role-permission map**, keyed by section. The reference implementation is [`IniAclAdapter`](https://github.com/dereuromark/cakephp-tinyauth/blob/master/src/Auth/AclAdapter/IniAclAdapter.php).

`$availableRoles` is `['admin' => 1, 'user' => 2, ...]` so you can look up role IDs by name.

Each entry should look like:

```php
[
    'Articles' => [
        'controller' => 'Articles',
        'plugin' => null,
        'prefix' => null,
        'allow' => [
            'index'  => ['admin' => 1, 'user' => 2],
            'edit'   => ['admin' => 1],
        ],
        'deny' => [
            'delete' => ['user' => 2],
        ],
    ],
];
```

`allow` is `[action => [roleName => roleId, ...]]` — a single action can have multiple roles. `deny` follows the same shape and is checked first; an action denied for a role overrides any allow.

## Registering your adapter

Set the relevant config key in `app.php`:

```php
'TinyAuth' => [
    // For authentication (allow whitelist):
    'allowAdapter' => \App\Auth\DatabaseAllowAdapter::class,

    // For authorization (ACL):
    'aclAdapter' => \App\Auth\DatabaseAclAdapter::class,
],
```

That's it — TinyAuth instantiates the adapter and calls `getAllow()` / `getAcl()` once per request (cached, see [Configuration](/guide/configuration#cache-busting)).

## Skeleton example

```php
namespace App\Auth;

use TinyAuth\Auth\AclAdapter\AclAdapterInterface;

class DatabaseAclAdapter implements AclAdapterInterface {

    public function getAcl(array $availableRoles, array $config): array {
        $acl = [];

        $rows = $this->fetchTable('AuthRules')->find()->toArray();

        foreach ($rows as $row) {
            $key = $row->section; // e.g. "Admin/Articles"
            if (!isset($acl[$key])) {
                $acl[$key] = $this->_parseSectionKey($key);
                $acl[$key]['allow'] = [];
                $acl[$key]['deny'] = [];
            }

            $bucket = $row->is_deny ? 'deny' : 'allow';
            $roleId = $availableRoles[$row->role] ?? null;
            if ($roleId === null) {
                continue;
            }
            $acl[$key][$bucket][$row->action][$row->role] = $roleId;
        }

        return $acl;
    }

    protected function _parseSectionKey(string $key): array {
        return \TinyAuth\Utility\Utility::deconstructIniKey($key);
    }
}
```

## See also

- [TinyAuthBackend](https://github.com/dereuromark/cakephp-tinyauth-backend) — a ready-made admin GUI for editing both stores.
- [Configuration: Custom adapters](/guide/configuration#custom-adapters) — the config key reference.
