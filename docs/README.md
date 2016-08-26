# TinyAuth Authentication and Authorization

This plugin ships with both
- Authentication: Always comes first - "who is it"?
- Authorization: Afterwards - "What can this person see"?

For the first one usually declares as a whitelist of actions per controller that will not require any authentication.
If an action is not whitelisted, it will trigger the login process.

The second only gets invoked once the person is already logged in.
In that case the role of this logged in user decides if that action is allowed to be accessed.

## Authentication
NEW since version 1.4.0

This is done via TinyAuth Auth Component.

The component plays well together with the adapter (see below).
If you do not have any roles and either all are logged in or not logged in you can also use this stand-alone to make certain pages public.

See [Authentication](/docs/Authentication) docs.

## Authorization
For this we have a TinyAuth Authorize adapter.

The adapter plays well together with the component above.
But if you prefer to control the action whitelisting for authentication via code and `$this->Auth->allow()` calls, you can
also just use this adapter stand-alone for the ACL part of your application.

See [Authorization](/docs/Authorization) docs.
