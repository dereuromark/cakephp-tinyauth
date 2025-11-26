# TinyAuth Authorization

The fast and easy way for user authorization in CakePHP applications using INI-based role-based access control (RBAC).

## Overview

TinyAuth's Authorization component wraps the official [CakePHP Authorization plugin](https://github.com/cakephp/authorization) and adds INI-based configuration for role-based access control, eliminating the need for writing policy classes for simple controller-action permissions.

**What TinyAuth Adds:**
- **INI-based role permissions** - Define role-based access in `auth_acl.ini` instead of policies
- **Simple RBAC** - Controller-action level permissions based on user roles
- **Quick setup** - Get role-based authorization working in minutes
- **Single or multi-role support** - Flexible user-role relationships

**When to Use:**
- ✅ Controller-action level permissions
- ✅ Simple role-based access control (RBAC)
- ✅ Centralized authorization configuration

**When NOT to Use:**
- ❌ You need row/entity-level authorization
- ❌ You need complex policy-based authorization logic
- ❌ You want to dynamically adjust access rights in code

---

# Part 1: Official Plugin Setup

Before using TinyAuth features, you must set up the official CakePHP Authorization plugin.

## Step 1: Install the Official Plugin

```bash
composer require cakephp/authorization
```

**📖 Official Documentation:** [book.cakephp.org/authorization/3](https://book.cakephp.org/authorization/3/en/index.html)

**GitHub Repository:** [github.com/cakephp/authorization](https://github.com/cakephp/authorization)

**Key Official Resources:**
- [Getting Started](https://book.cakephp.org/authorization/3/en/getting-started.html) - Basic setup tutorial
- [Policies](https://book.cakephp.org/authorization/3/en/policies.html) - Policy-based authorization
- [Middleware](https://book.cakephp.org/authorization/3/en/middleware.html) - Authorization middleware setup
- [Request Authorization](https://book.cakephp.org/authorization/3/en/request-authorization.html) - Controller-level authorization
- [Policy Resolvers](https://book.cakephp.org/authorization/3/en/policy-resolvers.html) - How policies are found

## Step 2: Implement Authorization Interface

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

**Note:** If you're also using Authentication, implement both interfaces:

```php
class Application extends BaseApplication
    implements AuthenticationServiceProviderInterface, AuthorizationServiceProviderInterface
{
    // ...
}
```

**📖 See:** [Getting Started](https://book.cakephp.org/authorization/3/en/getting-started.html) in official docs

## Step 3: Configure Middleware

Add **both** Authorization and TinyAuth-specific middlewares to your `Application` class:

```php
// src/Application.php
use Authorization\Middleware\AuthorizationMiddleware;
use TinyAuth\Middleware\RequestAuthorizationMiddleware;

public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
{
    // ... other middleware (including Authentication)

    // 1. Official Authorization middleware
    $middlewareQueue->add(new AuthorizationMiddleware($this));

    // 2. TinyAuth Request Authorization middleware for INI-based RBAC
    $middlewareQueue->add(new RequestAuthorizationMiddleware([
        'unauthorizedHandler' => [
            'className' => 'TinyAuth.ForbiddenCakeRedirect',
            'url' => ['controller' => 'Users', 'action' => 'login'],
            'unauthorizedMessage' => 'You are not authorized to access that location.',
        ],
    ]));

    // ... other middleware

    return $middlewareQueue;
}
```

**📖 See:** [Middleware Setup](https://book.cakephp.org/authorization/3/en/middleware.html) in official docs

## Step 4: Configure Authorization Service

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

**📖 See:** [Authorization Service](https://book.cakephp.org/authorization/3/en/getting-started.html#authorization-service) in official docs

---

# Part 2: TinyAuth Features

## Step 5: Set Up Roles

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

>**Note:** Make sure to add an "alias" field to your roles table (used as slug identifier in the `auth_acl.ini` file).

Example of a record from a valid roles table:

```php
'id' => '11'
'name' => 'User'
'description' => 'Basic authenticated user'
'alias' => 'user'
'created' => '2010-01-07 03:36:33'
'modified' => '2010-01-07 03:36:33'
```

The `alias` values should be slugged as `lowercase-dashed`. Multi words like `Super Admin` would be `super-admin` etc.

>**Note:** You do NOT need Configure based roles when using database roles. Also make sure to remove (or rename) existing Configure based roles since TinyAuth will always first try to find a matching Configure roles array before falling back to using the database.

### User-Role Relationships

#### Single-Role

When using the single-role-per-user model, TinyAuth expects your Users model to contain a column named `role_id`. If you prefer to use another column name, specify it using the `roleColumn` configuration option. If it is a nested relationship, you can use dot notation to specify the path, e.g. `Role.id`.

#### Multi-Role

When using the multiple-roles-per-user model:

- Your database MUST have a `roles` table
- Your database MUST have a valid join table (e.g. `roles_users`). This can be overridden with the `pivotTable` option.
- The configuration option `multiRole` MUST be set to `true`

Example of a record from a valid join table:

```php
'id' => 1
'user_id' => 1
'role_id' => 1
```

If you want to have default database tables here for multi-role auth, you can use the plugin shipped Migrations file:

```
bin/cake migrations migrate -p TinyAuth
```

Alternatively you can copy and paste this migration file to your `app/Config` folder and adjust the fields and table names and then use that modified version instead.

## Step 6: Load TinyAuth Component

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

## Step 7: Configure auth_acl.ini

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

>**Note:** Prefixes are always `CamelCased`. The route inflects to the final casing if needed.
>Nested prefixes are joined using `/`, e.g. `MyAdmin/Nested`.

### Deny Rules (Use with Caution)

Using only "granting" is recommended for security reasons. Careful with denying, as this can accidentally open up more than desired actions:

```ini
[Users]
* = user, admin
secret = !user
```

Meaning: Grant the user/admin role access to all "Users" controller actions by default, but only allow admins to access "secret" action.

**Note:** Denying always trumps granting, if both are declared for an action.

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
    'protectedPrefix' => ['Admin', 'Management', ...],
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

>**Note:** Prefixes are always `CamelCased` (even if routing makes them to `dashed-ones` in the URL).

## Caching

TinyAuth makes heavy use of caching to achieve optimal performance. By default it will not use caching in debug mode.

To modify the caching behavior set the `autoClearCache` configuration option:

```php
$this->loadComponent('TinyAuth.Authorization', [
    'autoClearCache' => true|false,
]);
```

## Configuration

TinyAuthorize adapter supports the following configuration options:

| Option                | Type          | Description                                                                                                                                                    |
|:----------------------|:--------------|:---------------------------------------------------------------------------------------------------------------------------------------------------------------|
| roleColumn            | string        | Name of column in user table holding role id (used for foreign key in users table in a single role per user setup, or in the pivot table on multi-roles setup) |
| userColumn            | string        | Name of column in pivot table holding role id (only used in pivot table on multi-roles setup)                                                                  |
| aliasColumn           | string        | Name of the column for the alias in the role table                                                                                                             |
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
| autoClearCache        | bool          | True will generate a new ACL cache file every time.                                                                                                            |
| aclFilePath           | string        | Full path to the auth_acl.ini. Can also be an array of multiple paths. Defaults to `ROOT . DS . 'config' . DS`.                                                |
| aclFile               | string        | Name of the INI file. Defaults to `auth_acl.ini`.                                                                                                              |
| aclAdapter            | string        | Class name, defaults to `IniAclAdapter::class`.                                                                                                                |
| includeAuthentication | bool          | Set to true to include public auth access into hasAccess() checks. Note, that this requires Configure configuration.                                           |

---

# Advanced Topics

## TinyAuth-Specific Enhancements

### Enhanced Redirect Handlers

TinyAuth provides redirect handlers with flash message support:

**`TinyAuth.ForbiddenCakeRedirect`** - Works with CakePHP routing arrays:

```php
'unauthorizedHandler' => [
    'className' => 'TinyAuth.ForbiddenCakeRedirect',
    'url' => ['controller' => 'Users', 'action' => 'login'],
    'queryParam' => 'redirect',
    'statusCode' => 302,
    'unauthorizedMessage' => 'You need permission to access that page.',
]
```

**`TinyAuth.ForbiddenRedirect`** - Uses direct URLs:

```php
'unauthorizedHandler' => [
    'className' => 'TinyAuth.ForbiddenRedirect',
    'url' => '/',
    'queryParam' => 'redirect',
    'statusCode' => 302,
    'unauthorizedMessage' => 'Access denied.',
]
```

**Features:**
- Automatically sets flash error messages
- Skips JSON/XML requests (throws exception instead)
- Preserves target URL in query parameter for post-login redirect
- Set `unauthorizedMessage` to `false` to disable flash messages

You can use the official handlers instead if you prefer.

### Controller-Specific Authorization

In some cases, you may want to apply authorization at the controller level instead of globally. Move `RequestAuthorizationMiddleware` from `Application` to your controller:

```php
// src/Controller/AppController.php
use Psr\Http\Message\ResponseInterface;
use Cake\Http\ServerRequest;
use TinyAuth\Middleware\RequestAuthorizationMiddleware;

public function initialize(): void
{
    parent::initialize();

    // Apply authorization middleware at controller level
    $this->middleware(function (ServerRequest $request, $handler): ResponseInterface {
        $config = [
            'unauthorizedHandler' => [
                'className' => 'TinyAuth.ForbiddenCakeRedirect',
                'url' => [
                    'controller' => 'Users',
                    'action' => 'login',
                ],
            ],
        ];
        $middleware = new RequestAuthorizationMiddleware($config);

        return $middleware->process($request, $handler);
    });
}
```

**Note:** If you experience redirect loops, wrap the middleware in `if ($this->AuthUser->id()) {}` since authentication must complete before authorization can run.

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
$isMe = $this->AuthUser->id() == $userEntity->id;
```

## AuthUser Helper

The helper assists with the same in the templates.

Include the helper in your `AppView.php`:

```php
$this->loadHelper('TinyAuth.AuthUser');
```

**Note:** This helper only works if you also enabled the above component, as it needs some data to be passed down.

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

If you need to build a navigation that includes publicly accessible actions, you need to enable `includeAuthentication` config. This will then also include the Authentication data from your allow config. But this only checks/uses the INI config, it can not work on controller authentication. So make sure you transformed everything fully to the INI file here. Any custom `->allow()` call in controllers can not be taken into account.

## CLI Commands

### Sync Command

The plugin offers a convenience CLI command to sync ACL for any new controller. It will automatically skip controllers that are whitelisted as public (non authenticated).

```
bin/cake tiny_auth sync {your default roles, comma separated}
```

This will then add any missing controller with `* = ...` for all actions and you can then manually fine-tune.

**Note:** Use `'*'` as wildcard role if you just want to generate all possible controllers. Use with `-d -v` to just output the changes it would do to your ACL INI file.

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

### Leverage Session

Especially when working with multi-role setup, it can be useful to not every time read the current user's roles from the database. When logging in a user you can write the roles to the session right away. If available here, TinyAuth will use those and will not try to query the roles table (or the `roles_users` pivot table).

For basic single-role setup, the session is expected to be filled like:

```php
'Auth' => [
    'User' => [
        'id' => '1',
        'role_id' => '1',
        ...
    ]
];
```

The expected `'role_id'` session key is configurable via `roleColumn` config key.

For a multi-role setup it can be either the normalized array form:

```php
'Auth' => [
    'User' => [
        'id' => '1',
        ...
        'Roles' => [
            [
                'id' => '1',
                ...
            ],
            ...
        ],
    ]
];
```

or the simplified numeric list form:

```php
'Auth' => [
    'User' => [
        'id' => '1',
        ...
        'Roles' => [
            '1',
            '2',
            ...
        ]
    ]
];
```

The expected `'Roles'` session key is configurable via `rolesTable` config key.

Alternatively, instead of manually adding the Roles into the session, you can also just join in the pivot table (`roles_users` usually), and if those are added to the session in either normalized or numeric list it will also read from those instead of asking the database:

```php
'Auth' => [
    'User' => [
        'id' => '1',
        ...
        'roles_users' => [
            ...
            'role_id' => '1',
            ...
        ],
    ]
];
```

**Note:** In this case the role definitions will have to contain a `role_id`, though (as the pivot table only contains `user_id` and that field).

When logging the user in you can have a custom handler modifying your input accordingly, prior to calling:

```php
// Modify or add roles in $user
$this->Auth->setUser($user);
```

The easiest way here to contain the roles, however, is to have your custom `findAuth()` finder which also fetches those.

## Adapters

By default INI files and the IniAdapter will be used. See [AuthorizationAdapter](AuthorizationAdapter.md) for how to change the strategy and maybe build your own adapter solution.

---

# Additional Resources

- **📖 Official Authorization Docs:** [book.cakephp.org/authorization/3](https://book.cakephp.org/authorization/3/en/index.html)
- **🔐 Authentication:** Set up authentication first - See [Authentication.md](Authentication.md)
- **❓ Troubleshooting:** See [docs/README.md](README.md#troubleshooting)
