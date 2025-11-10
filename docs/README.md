# TinyAuth Authentication and Authorization

This plugin ships with both
- Authentication: Always comes first - "who is it"?
- Authorization: Afterwards - "What can this person see"?

For the first one usually declares as a whitelist of actions per controller that will not require any authentication.
If an action is not whitelisted, it will trigger the login process.

The second only gets invoked once the person is already logged in.
In that case the role of this logged in user decides if that action is allowed to be accessed.

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
First of all: Isolate the issue. Never mix **authentication** and **authorization** (read the top part again).

If you want to use both, first attach authentication and make sure you can log in and you can log out. By default all actions are now protected unless you make them "public". So make sure the non-public actions are not accessible without being logged in and they are afterwards.
You just verified: authentication is working now fine - it doesn't matter who logged in as long as someone did.

Only if that is working, attach an Auth adapter (which now means authorization comes into play), in this case probably `Tiny`.
By default it will now deny all logged in users any access to any protected action. Only by specifically whitelisting actions/controllers now in the ACL definition, a specific user can access a specific action again.
Make sure that the session contains the correct data structure, also make sure the roles are configured or in the database and can be found as expected. The user with the right role should get access now to the corresponding action (make also sure cache is cleared).
You then verified: authorization is working fine, as well - only with the correct role a user can now access protected actions.

## Required Dependencies

**IMPORTANT:** TinyAuth is a wrapper plugin that extends the official CakePHP plugins.
You must install them first, if you want to use Authentication or Authorization functionality:

```bash
# Required for TinyAuth.Authentication component
composer require cakephp/authentication

# Required for TinyAuth.Authorization component
composer require cakephp/authorization
```

See the docs for setup details:
- [TinyAuth and Authentication plugin](AuthenticationPlugin.md)
- [TinyAuth and Authorization plugin](AuthorizationPlugin.md)

### Why use TinyAuth as a wrapper?

TinyAuth provides a powerful abstraction layer over the official Authentication and Authorization plugins:

**Benefits of using TinyAuth:**
- **Zero-code configuration**: All auth rules in INI files, no controller modifications needed
- **Instant setup**: Working authentication/authorization in under 5 minutes
- **Plugin compatibility**: Works automatically with all plugins without modifications
- **Centralized management**: All rules in one place, not scattered across controllers
- **Performance**: Built-in caching for optimal speed
- **Developer friendly**: DebugKit panel, clear error messages, easy debugging

**When to use TinyAuth wrapper:**
If you only need the basic request policy provided by this plugin (controller-action level permissions),
then TinyAuth provides a much simpler and faster solution.

**When you might need vanilla plugin functionality directly:**
Consider using the official plugins' features directly (alongside TinyAuth) when you need:
- Complex policy-based authorization (ORM policies, custom voters)
- Per-entity authorization rules
- Custom authentication flows beyond what TinyAuth provides

You can seamlessly use both approaches together. TinyAuth's INI files work alongside the official plugins,
and AuthUser component and helper are compatible with the Auth panel.

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
