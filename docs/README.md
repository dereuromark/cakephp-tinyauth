# TinyAuth Documentation

TinyAuth provides simple, INI-based configuration for authentication and authorization in CakePHP applications.

## Core Concepts

### Authentication vs Authorization

Understanding the difference is crucial:

| Concept            | Question           | When                 | Purpose                                           |
|--------------------|--------------------|----------------------|---------------------------------------------------|
| **Authentication** | "Who are you?"     | First                | Determines if a user is logged in                 |
| **Authorization**  | "What can you do?" | After authentication | Determines if a user can access a specific action |

**Authentication** defines public actions via whitelist. Any non-whitelisted action triggers the login process.

**Authorization** only applies to logged-in users. The user's role(s) determine which actions they can access.

## Prerequisites

**IMPORTANT:** TinyAuth wraps CakePHP's official Authentication and Authorization plugins. Before using TinyAuth features, you **must** install and configure the official plugins:

| Feature                           | Required Plugin                                                     | Setup Guide                                                                                                             |
|-----------------------------------|---------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------|
| **Authentication** (login/logout) | [cakephp/authentication](https://github.com/cakephp/authentication) | [Authentication.md](AuthenticationPlugin.md) + [Official Docs](https://book.cakephp.org/authentication/3/en/index.html) |
| **Authorization** (roles/ACL)     | [cakephp/authorization](https://github.com/cakephp/authorization)   | [Authorization.md](AuthorizationPlugin.md) + [Official Docs](https://book.cakephp.org/authorization/2/en/index.html)    |

```bash
# For authentication features
composer require cakephp/authentication

# For authorization features
composer require cakephp/authorization
```

**Special Case:** The AuthUser component and helper work standalone without the official plugins - they work with any authentication solution that sets an identity in the request.

## Getting Started

Each guide includes complete setup instructions for both the official plugin AND TinyAuth features:

1. **[Authentication](Authentication.md)** - Complete guide from official plugin setup to INI-based public action whitelisting
2. **[Authorization](Authorization.md)** - Complete guide from official plugin setup to INI-based role permissions (ACL)
3. **[AuthUser](AuthUser.md)** - Component/helper for role checks (works standalone)

## DebugKit Panel
You can activate an "Auth" DebugKit panel to have useful insights per URL.

See [AuthPanel](AuthPanel.md) docs.

## Authentication
This is done via TinyAuth Authentication component.

The component plays well together with the authorization part (see below).
If you do not have any roles and either all are logged in or not logged in you can also use this stand-alone to make certain pages public.

See [Authentication](Authentication.md) docs.

## Authorization
The TinyAuthorize adapter takes care of authorization.

The adapter plays well together with the component above.
But if you prefer to control the action whitelisting for authentication via code and `$this->Authentication->allowUnauthenticated()` calls, you can
also just use this adapter stand-alone for the ACL part of your application.

See [Authorization](Authorization.md) docs.

## AuthUser Component and Helper
The AuthUser component and helper work **standalone** without requiring the official plugins.
They provide convenient methods for working with the currently authenticated user in your controllers and views.

These are useful for making role-based decisions or displaying role-based links in your templates.

See [AuthUser](AuthUser.md) docs.


## Configuration
Those classes most likely share quite a few configs, in that case you definitely should use Configure to define those centrally:
```php
// in your app.php
    'TinyAuth' => [
        'multiRole' => true,
        ...
    ],
```
This way you keep it DRY.

## Cache busting
In general, it is advised to clear the cache after each deploy, e.g. using
```
bin/cake clear cache
```
In debug mode this happens automatically for each request.

By default, the cache engine used is `_cake_core_`, the prefix is `tiny_auth_`.
You can also clear the cache from code using `TinyAuth\Utility\Cache::clear()` method for specifically this.

## Custom Allow or ACL adapters
You can easily switch out the INI file adapters for both using `allowAdapter` and `aclAdapter` config.
This way you can also read from DB or provide any other API driven backend to read the data from for your authentication or authorization.

Current customizations:
- [TinyAuthBackend plugin](https://github.com/dereuromark/cakephp-tinyauth-backend) as backend GUI for "allow" and "ACL".

## Troubleshooting

### Step-by-Step Debugging

**Never mix authentication and authorization when troubleshooting!** Test each separately:

#### 1. Test Authentication First

Set up **only** authentication (see [Authentication.md](Authentication.md)):
- Load the `TinyAuth.Authentication` component
- Configure `auth_allow.ini` for public actions
- Verify you can log in and log out
- Verify non-public actions require login
- Verify public actions are accessible without login

✅ **Once this works**, authentication is configured correctly.

#### 2. Then Test Authorization

Add authorization **only after** authentication works (see [Authorization.md](Authorization.md)):
- Load the `TinyAuth.Authorization` component
- Set up roles (database or Configure)
- Configure `auth_acl.ini` for role permissions
- Clear cache (`bin/cake cache clear_all`)
- Verify role-based access works correctly

**Common Issues:**
- Session structure doesn't match expected format (check `role_id` column)
- Roles not found in database or Configure
- Cache not cleared after INI changes
- ACL rules syntax errors

✅ **Once this works**, both authentication and authorization are configured correctly.

## Why Use TinyAuth?

TinyAuth provides a powerful abstraction layer over the official Authentication and Authorization plugins:

### Benefits
- **Zero-code configuration**: All auth rules in INI files, no controller modifications needed
- **Instant setup**: Working authentication/authorization in under 5 minutes
- **Plugin compatibility**: Works automatically with all plugins without modifications
- **Centralized management**: All rules in one place, not scattered across controllers
- **Performance**: Built-in caching for optimal speed
- **Developer friendly**: DebugKit panel, clear error messages, easy debugging

### When to Use TinyAuth
✅ Controller-action level permissions
✅ Simple role-based access control (RBAC)
✅ Quick setup without extensive configuration

### When You Might Also Need Official Plugin Features Directly
Consider using the official plugins' advanced features (alongside TinyAuth) when you need:
- Complex policy-based authorization (ORM policies, custom voters)
- Per-entity/row-level authorization rules
- Custom authentication flows beyond what TinyAuth provides

**Note:** You can seamlessly use both approaches together. TinyAuth's INI files work alongside the official plugins' advanced features.

## Upgrade notes

### Upgrading from TinyAuth 3.x (CakePHP 4.x)

**BREAKING CHANGES:** TinyAuth has fundamentally changed from a standalone auth solution to a wrapper around the official CakePHP plugins.

**What was removed:**
- All custom authenticate adapters (`FormAuthenticate`, `MultiColumnAuthenticate`, `BasicAuthenticate`, `DigestAuthenticate`)
- All custom password hashers (`DefaultPasswordHasher`, `FallbackPasswordHasher`, `WeakPasswordHasher`)
- `TinyAuth.Auth` component (replaced by `TinyAuth.Authentication` and `TinyAuth.Authorization`)
- `AuthComponent` and `LegacyAuthComponent`
- Storage classes (`MemoryStorage`, `SessionStorage`)

**What you need to do:**
1. Install the official plugins:
   ```bash
   composer require cakephp/authentication cakephp/authorization
   ```

2. Replace component loading:
   ```php
   // OLD (removed):
   $this->loadComponent('TinyAuth.Auth', [...]);

   // NEW:
   $this->loadComponent('TinyAuth.Authentication', [...]);
   $this->loadComponent('TinyAuth.Authorization', [...]);
   ```

3. Set up middleware in your `Application` class (see [AuthenticationPlugin.md](AuthenticationPlugin.md) and [AuthorizationPlugin.md](AuthorizationPlugin.md))

4. Your INI files (`auth_allow.ini` and `auth_acl.ini`) continue to work as before

**What still works:**
- AuthUser component and helper
- INI-based configuration files
- All role-based authorization features
- DebugKit Auth panel

## Contributing
Feel free to fork and pull request.

There are a few guidelines:

- Coding standards passing: `composer cs-check` to check and `composer cs-fix` to fix.
- Tests passing: `composer test` to run them.
