# TinyAuth Authorization

TinyAuth checks controller actions against role rules in `auth_acl.ini`. Use it for request-level permissions, and keep entity policies for permissions on individual records.

Complete [Installation](/guide/install) first. [Getting started](/guide/quick-start) shows a small role map and ACL. This page covers the official Authorization service and TinyAuth’s component integration.

## Official plugin setup

Set up the [CakePHP Authorization plugin](https://github.com/cakephp/authorization) with its [Getting started guide](https://book.cakephp.org/authorization/4/getting-started.html). Authentication middleware must run before Authorization middleware so the latter can decorate the authenticated identity.

## Implement Authorization Interface

Your `Application` class must implement `AuthorizationServiceProviderInterface`:

```php
// src/Application.php
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\AuthorizationServiceInterface;
use Psr\Http\Message\ServerRequestInterface;

class Application extends BaseApplication implements AuthorizationServiceProviderInterface
{
    // ...
}
```

Note: If you're also using Authentication, implement both interfaces:

```php
class Application extends BaseApplication
    implements AuthenticationServiceProviderInterface, AuthorizationServiceProviderInterface
{
    // ...
}
```

📖 See: [Getting Started](https://book.cakephp.org/authorization/4/getting-started.html) in official docs

## Configure Middleware

Add the official Authorization middleware after Authentication middleware:

```php
use Authorization\Middleware\AuthorizationMiddleware;

$middlewareQueue->add(new AuthorizationMiddleware($this));
```

The TinyAuth Authorization component performs the request check at controller startup. If you prefer to check requests in middleware, follow [Middleware and policy](/authorization/middleware) instead; do not add both checks for the same request.

## Configure Authorization Service

Implement `getAuthorizationService()` in your `Application` class to set up the TinyAuth request policy:

```php
// src/Application.php
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\Policy\MapResolver;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use TinyAuth\Policy\RequestPolicy;

public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
{
    // Map ServerRequest to TinyAuth's RequestPolicy for INI-based RBAC
    $mapResolver = new MapResolver();
    $mapResolver->map(ServerRequest::class, new RequestPolicy());

    return new AuthorizationService($mapResolver);
}
```

This configures the request policy that reads your `auth_acl.ini` file for role-based permissions.

📖 See: [Authorization Service](https://book.cakephp.org/authorization/4/getting-started.html#authorization-service) in official docs

---

## Set Up Roles

TinyAuth requires the presence of roles to function. Choose one of the following options:

### Configure-Based Roles

Define your roles in a Configure array if you want to prevent database role lookups:

```php
// config/app.php

/*
 * Optionally define constants for easy referencing throughout your code
 */
define('ROLE_USER', 1);
define('ROLE_ADMIN', 2);
define('ROLE_SUPER_ADMIN', 9);

return [
    'Roles' => [
        'user' => ROLE_USER,
        'admin' => ROLE_ADMIN,
        'super-admin' => ROLE_SUPER_ADMIN,
    ],
];
```

The key should be a slug of your role. A multi-word like `Super Admin` would be `super-admin` as alias here.

### Database Roles

When you choose to store your roles in the database, TinyAuth expects a table named `roles`. If you prefer to use another table name, specify it using the `rolesTable` configuration option.

>Note: Make sure to add an "alias" field to your roles table (used as slug identifier in the `auth_acl.ini` file).

Example of a record from a valid roles table:

```php
[
    'id' => '11',
    'name' => 'User',
    'description' => 'Basic authenticated user',
    'alias' => 'user',
    'created' => '2010-01-07 03:36:33',
    'modified' => '2010-01-07 03:36:33',
];
```

The `alias` values should be slugged as `lowercase-dashed`. Multi words like `Super Admin` would be `super-admin` etc.

>Note: You do NOT need Configure based roles when using database roles. Also make sure to remove (or rename) existing Configure based roles since TinyAuth will always first try to find a matching Configure roles array before falling back to using the database.

### User-Role Relationships

#### Single-Role

When using the single-role-per-user model, TinyAuth expects your Users model to contain a column named `role_id`. If you prefer to use another column name, specify it using the `roleColumn` configuration option. If it is a nested relationship, you can use dot notation to specify the path, e.g. `Role.id`.

#### Multi-Role

When using the multiple-roles-per-user model:

- Set `multiRole` to `true`.
- Define available role aliases through a Configure map or a roles table.
- Provide membership in the identity data or a join table such as `roles_users`. Set `pivotTable` when its model alias differs from the default.

Example of a record from a valid join table:

```php
[
    'id' => 1,
    'user_id' => 1,
    'role_id' => 1,
];
```

If you want to have default database tables here for multi-role auth, you can use the plugin shipped Migrations file:

```
bin/cake migrations migrate -p TinyAuth
```

Alternatively you can copy and paste this migration file to your `app/Config` folder and adjust the fields and table names and then use that modified version instead.

## Load TinyAuth Component

Load the Authorization component in your controller:

```php
// src/Controller/AppController.php

public function initialize(): void
{
    parent::initialize();

    $this->loadComponent('TinyAuth.Authorization', [
        // Configuration options (see Configuration section below)
    ]);
}
```

That's it for code changes! Now configure your role permissions via INI file.

## Configure auth_acl.ini

TinyAuth expects an `auth_acl.ini` file in your config directory. Use it to specify in detail who gets access to which resources.

### Basic Syntax

The section key syntax follows the CakePHP naming convention:
```
PluginName.MyPrefix/MyController
```

Make sure to create an entry for each action you want to expose and use:

- one or more role names (groups granted access)
- the `*` wildcard to allow access to all authenticated users

### Example Configuration

```ini
; ----------------------------------------------------------
; UsersController
; ----------------------------------------------------------
[Users]
index = user, admin, undefined-role
edit, view = user, admin
* = admin

; ----------------------------------------------------------
; UsersController using /api prefixed route
; ----------------------------------------------------------
[Api/Users]
view = user
* = admin

; ----------------------------------------------------------
; UsersController using /admin prefixed route
; ----------------------------------------------------------
[Admin/Users]
* = admin

; ----------------------------------------------------------
; AccountsController in plugin named Accounts
; ----------------------------------------------------------
[Accounts.Accounts]
view, edit = user
* = admin

; ----------------------------------------------------------
; AccountsController in plugin named Accounts using /admin
; prefixed route
; ----------------------------------------------------------
[Accounts.Admin/Accounts]
* = admin

; ----------------------------------------------------------
; CompaniesController in plugin named Accounts
; ----------------------------------------------------------
[Accounts.Companies]
view, edit = user
* = admin

; ----------------------------------------------------------
; CompaniesController in plugin named Accounts using /my-admin
; prefixed route (assuming you are using recommended DashedRoute class)
; ----------------------------------------------------------
[Accounts.MyAdmin/Companies]
* = admin

[SomeController]
* = * ; All roles can access all actions
```

>Note: Prefixes are always `CamelCased`. The route inflects to the final casing if needed.
>Nested prefixes are joined using `/`, e.g. `MyAdmin/Nested`.

### Deny Rules (Use with Caution)

Using only "granting" is recommended for security reasons. Careful with denying, as this can accidentally open up more than desired actions:

```ini
[Users]
* = user, admin
secret = !user
```

Meaning: Grant the user/admin role access to all "Users" controller actions by default, but only allow admins to access "secret" action.

Note: Denying always trumps granting, if both are declared for an action.

### Multiple Files and Merging

You can specify multiple paths in your config, e.g. when you have plugins and separated the definitions across them. Make sure you are using each section key only once, though. The first definition will be kept and all others for the same section key are ignored.

### Template with Defaults

See the `config/` folder and the default template for popular plugins. You can copy out any default rules you want to use in your project.

## Quick Setups

TinyAuth offers a few quick setup options for common scenarios:

### User vs Admin

If you have basically two roles (or *none* vs. admin) and want to separate frontend and backend actions:

```php
$this->loadComponent('TinyAuth.Authorization', [
    'allowLoggedIn' => true,
]);
```

This way, by default, any logged in user has access to all pages except the ones with a prefix defined in a blacklist called `protectedPrefix`. This list defaults to `Admin` prefix.

If you have another or more prefixes, you can customize the list:

```php
$this->loadComponent('TinyAuth.Authorization', [
    'allowLoggedIn' => true,
    'protectedPrefix' => ['Admin', 'Management'],
]);
```

### Prefix-Based ACL

If your prefixes match the role names, you can use:

```php
$this->loadComponent('TinyAuth.Authorization', [
    'authorizeByPrefix' => true,
]);
```

It will map all available roles to their prefix equivalent and allow access based on this.

If you need more control over the prefix map, or want to customize the roles per prefix:

```php
$this->loadComponent('TinyAuth.Authorization', [
    'authorizeByPrefix' => [
        'Admin' => 'admin',
        'Management' => ['mod', 'super-mod'],
        'PrefixThree' => ...,
    ],
]);
```

Since non-prefixed routes are not caught by this, this is best combined with `'allowLoggedIn' => true` and all prefixes listed in `protectedPrefix`.

>Note: Prefixes are always `CamelCased` (even if routing makes them to `dashed-ones` in the URL).

## Caching

TinyAuth caches parsed rules. When `debug` is enabled, `autoClearCache` defaults to `true` and clears the rule cache before reading it. Set it to `false` to retain cached rules while debugging. In production, changing an INI file requires clearing the configured rule cache.

To modify the caching behavior set the `autoClearCache` configuration option:

```php
$this->loadComponent('TinyAuth.Authorization', [
    'autoClearCache' => false,
]);
```

## Configuration

TinyAuthorize adapter supports the following configuration options:

| Option                | Type          | Description                                                                                                                                                    |
|:----------------------|:--------------|:---------------------------------------------------------------------------------------------------------------------------------------------------------------|
| roleColumn            | string        | Name of column in user table holding role id (used for foreign key in users table in a single role per user setup, or in the pivot table on multi-roles setup) |
| userColumn            | string        | Name of column in pivot table holding role id (only used in pivot table on multi-roles setup)                                                                  |
| aliasColumn           | string        | Name of the column for the alias in the role table                                                                                                             |
| roleIdColumn          | string        | Name of the primary key column in roles table. Defaults to `id`. Set to `uuid` for UUID-based role systems.                                                    |
| idColumn              | string        | Name of the ID Column in users table                                                                                                                           |
| rolesTable            | string        | Name of Configure key holding all available roles OR class name of roles database table                                                                        |
| usersTable            | string        | Class name of the users table.                                                                                                                                 |
| pivotTable            | string        | Name of the pivot table, for a multi-group setup.                                                                                                              |
| rolesTablePlugin      | string        | Name of the plugin for the roles table, if any.                                                                                                                |
| pivotTablePlugin      | string        | Name of the plugin for the pivot table, if any.                                                                                                                |
| multiRole             | bool          | True will enable multi-role/HABTM authorization (requires a valid join table).                                                                                 |
| superAdminRole        | int           | Id of the super admin role. Users with this role will have access to ALL resources.                                                                            |
| superAdmin            | int or string | Id/name of the super admin. Users with this id/name will have access to ALL resources. null/0/'0' disable it.                                                  |
| superAdminColumn      | string        | Column of super admin in user table. Default is idColumn option.                                                                                               |
| authorizeByPrefix     | bool/array    | If prefixed routes should be auto-handled by their matching role name or a prefix=>role map.                                                                   |
| allowLoggedIn         | bool          | True will give authenticated users access to all resources except those using the `protectedPrefix`.                                                           |
| protectedPrefix       | string/array  | Name of the prefix(es) used for admin pages. Defaults to `Admin`.                                                                                              |
| autoClearCache        | bool          | Clears the parsed-rule cache before reading it when both this option and `debug` are true.                                                                                                            |
| aclFilePath           | string        | Directory containing `auth_acl.ini`, with a trailing separator. Can also be an array of directories. Defaults to `ROOT . DS . 'config' . DS`.                                                |
| aclFile               | string        | Name of the INI file. Defaults to `auth_acl.ini`.                                                                                                              |
| aclAdapter            | string        | Class name, defaults to `IniAclAdapter::class`.                                                                                                                |
| includeAuthentication | bool          | Set to true to include public auth access into hasAccess() checks. Note, that this requires Configure configuration.                                           |

---

# Advanced Topics

## TinyAuth-Specific Enhancements

### Enhanced Redirect Handlers

TinyAuth provides redirect handlers with flash message support:

`TinyAuth.ForbiddenCakeRedirect` - Works with CakePHP routing arrays:

```php
'unauthorizedHandler' => [
    'className' => 'TinyAuth.ForbiddenCakeRedirect',
    'url' => ['controller' => 'Users', 'action' => 'login'],
    'queryParam' => 'redirect',
    'statusCode' => 302,
    'unauthorizedMessage' => 'You need permission to access that page.',
]
```

`TinyAuth.ForbiddenRedirect` - Uses direct URLs:

```php
'unauthorizedHandler' => [
    'className' => 'TinyAuth.ForbiddenRedirect',
    'url' => '/',
    'queryParam' => 'redirect',
    'statusCode' => 302,
    'unauthorizedMessage' => 'Access denied.',
]
```

Features:
- Automatically sets flash error messages
- Rethrows exceptions for non-HTML route extensions. The Accept header alone does not select this behavior.
- Preserves target URL in query parameter for post-login redirect
- Set `unauthorizedMessage` to `false` to disable flash messages

You can use the official handlers instead if you prefer.

### Middleware alternative

Use the [request middleware guide](./middleware) if you prefer middleware to the component. Choose one request authorization check to avoid running both.

## AuthUser Component

Add the AuthUserComponent and you can easily check permissions inside your controller scope:

```php
$this->loadComponent('TinyAuth.AuthUser');
```

Maybe you only want to redirect to a certain action if that is accessible for this user (role):

```php
if ($this->AuthUser->hasAccess(['action' => 'forModeratorOnly'])) {
    return $this->redirect(['action' => 'forModeratorOnly']);
}
// Do something else
```

Or if that person is of a certain role in general:

```php
if ($this->AuthUser->hasRole('mod')) { // Either by alias or id
    // OK, do something now
}
```

For any action that gets the user id passed, you can also ask:

```php
$isMe = $this->AuthUser->isMe($userEntity->id);
// This would be equal to
$isMe = $this->AuthUser->id() === $userEntity->id;
```

## AuthUser Helper

The helper assists with the same in the templates.

Include the helper in your `AppView.php`:

```php
$this->loadHelper('TinyAuth.AuthUser');
```

Note: This helper only works if you also enabled the above component, as it needs some data to be passed down.

All the above features are also available in the views and helpers now (`->id()`, `->isMe()`, `->roles()`, `->hasRole()`, `->hasRoles()`). But on top, if you want to display certain content or a link for specific roles, you can do that, too.

Let's say we only want to print an admin backend link if the role can access it:

```php
echo $this->AuthUser->link('Admin Backend', ['prefix' => 'Admin', 'action' => 'index']);
```

It will not show anything for all others.

Let's say we only want to print the delete link if the role is actually allowed to do that:

```php
echo $this->AuthUser->postLink('Delete this', ['action' => 'delete', $id], ['confirm' => 'Sure?']);
```

You can also do more complex things:

```php
if ($this->AuthUser->hasAccess(['action' => 'secretArea'])) {
    echo 'Only for you: ';
    echo $this->Html->link('Secret area', ['action' => 'secretArea']);
    echo ' (do not tell anyone else!)';
}
```

### Named Routes

With 1.12.0+ named routes are also supported now:

```php
<?= $this->AuthUser->link('Change Password', ['_name' => 'admin:account:password']); ?>
```

### Including Authentication

Please note that by default `hasAccess()` only checks the `auth_acl`, not the `auth_allow` adapter. Those links and access checks are meant to be used for logged in users.

If you need to build a navigation that includes publicly accessible actions, you need to enable `includeAuthentication` config. This will then also include the Authentication data from your allow config. But this only checks/uses the INI config, it can not work on controller authentication. So make sure you transformed everything fully to the INI file here. Any custom `allowUnauthenticated()` call in controllers can not be taken into account.

## CLI Commands

### Sync Command

The plugin offers a convenience CLI command to sync ACL for any new controller. It will automatically skip controllers that are whitelisted as public (non authenticated).

```
bin/cake tiny_auth sync {your default roles, comma separated}
```

This will then add any missing controller with `* = ...` for all actions and you can then manually fine-tune.

Note: Use `'*'` as wildcard role if you just want to generate all possible controllers. Use with `-d -v` to just output the changes it would do to your ACL INI file.

### Add Command

Add any role to any command and action:

```
bin/cake tiny_auth add {Controller} {Action} {roles, comma separated}
```

It will skip if the roles are already present for this controller and action.

Use with `-d -v` to just output the changes it would do to your ACL INI file.

## Tips

### Use Constants Instead of Magic Strings

If you are using the `hasRole()` or `hasRoles()` checks with a DB roles table, it is always better to use the aliases than the IDs (as the IDs can change). But even so, it is better not to use magic strings like `'moderator'`, but define constants in your bootstrap for each:

```php
// In your bootstrap
define('ROLE_MOD', 'moderator');

// In your template
if ($this->AuthUser->hasRole(ROLE_MOD)) {
    ...
}
```

This way, if you ever refactor them, it will be easier to adjust all occurrences, it will also be possible to use auto-completion type-hinting.

### Include roles in identity data

TinyAuth reads the authenticated identity’s original data, not a fixed `Auth.User` session path. In single-role mode, include the configured `roleColumn` field:

```php
$userData = [
    'id' => 1,
    'role_id' => 2,
];
```

In multi-role mode, include a field whose key matches `rolesTable` (default `Roles`). It can contain role IDs directly or records with an `id` field:

```php
$userData = [
    'id' => 1,
    'Roles' => [1, 2],
];
```

A CakePHP association named `roles` does not automatically match the default uppercase `Roles` key. Ensure your identifier returns the expected data shape. Without embedded role data, TinyAuth loads membership from the configured pivot table. See [Multi-role setup](/authorization/multi-role).

Configure the Authentication identifier’s ORM finder to load the required user data. Log in through the official Authentication component’s `setIdentity()` method; the removed CakePHP Auth component’s `setUser()` method is not part of this integration.

## Adapters

By default INI files and the IniAdapter will be used. See [AuthorizationAdapter](/authorization/adapter) for how to change the strategy and maybe build your own adapter solution.

---

# Additional Resources

- 📖 Official Authorization Docs: [book.cakephp.org/authorization/3](https://book.cakephp.org/authorization/4/)
- 🔐 Authentication: Set up authentication first - See [Authentication](/authentication/)
- ❓ Troubleshooting: See [Troubleshooting](/guide/troubleshooting)
