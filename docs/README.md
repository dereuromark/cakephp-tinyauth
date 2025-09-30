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

There is also an AuthUserComponent and AuthUserHelper to assist you in making role based decisions or displaying role based links in your templates.

See [Authorization](Authorization.md) docs.


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

## Working with new plugins
If you are using [Authentication](https://github.com/cakephp/authentication) or [Authorization](https://github.com/cakephp/authorization) plugin, you will need to use the
Authentication/Authorization components of this plugin instead for them to work with TinyAuth.

See the docs for details:
- [TinyAuth and Authentication plugin](AuthenticationPlugin.md)
- [TinyAuth and Authorization plugin](AuthorizationPlugin.md)

### Why use TinyAuth with the new plugins?

TinyAuth provides a powerful abstraction layer over the official Authentication and Authorization plugins:

**Benefits of using TinyAuth:**
- **Zero-code configuration**: All auth rules in INI files, no controller modifications needed
- **Instant setup**: Working authentication/authorization in under 5 minutes
- **Plugin compatibility**: Works automatically with all plugins without modifications
- **Centralized management**: All rules in one place, not scattered across controllers
- **Performance**: Built-in caching for optimal speed
- **Developer friendly**: DebugKit panel, clear error messages, easy debugging

**When to use vanilla plugins' functionality directly:**
They are super powerful, but they also require a load of config to get them to run.
Consider using them (partially) directly when you need:
- Authentication/authorization on middleware/routing level
- Complex policy-based authorization (ORM policies, custom voters)
- Per-entity authorization rules
- Custom authentication flows

**When to use TinyAuth wrapper:**
If you only need the basic request policy provided by this plugin (controller-action level permissions),
then TinyAuth provides a much simpler and faster solution.

You can seamlessly upgrade to the new plugins while keeping your INI files.
They are also compatible with AuthUser component and helper as well as the Auth panel.

## Upgrade notes
Coming from CakePHP 4.x the following major changes will affect your app:
- Cake\Auth namespace has been removed and is now migrated to TinyAuth\Auth, that includes the
  Authentication and Authorization classes, hashers and alike.

## Contributing
Feel free to fork and pull request.

There are a few guidelines:

- Coding standards passing: `composer cs-check` to check and `composer cs-fix` to fix.
- Tests passing: `composer test` to run them.
