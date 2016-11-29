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

This is done via TinyAuth AuthComponent.

The component plays well together with the adapter (see below).
If you do not have any roles and either all are logged in or not logged in you can also use this stand-alone to make certain pages public.

See [Authentication](Authentication.md) docs.

## Authorization
For this we have a TinyAuthorize adapter.

The adapter plays well together with the component above.
But if you prefer to control the action whitelisting for authentication via code and `$this->Auth->allow()` calls, you can
also just use this adapter stand-alone for the ACL part of your application.

There is also an AuthUserComponent and AuthUserHelper to assist you in making role based decisions or displaying role based links in your templates.

See [Authorization](Authorization.md) docs.


## Configuration
Those classes most likely share quite a few configs, in that case you definitely should use Configure to define those centrally:
````php
// in your app.php
	'TinyAuth' => [
		'multiRole' => true,
		...
	],
```
This way you keep it DRY.

## Troubleshooting
First of all: Isolate the issue. Never mix **authentication** and **authorization** (read the top part again).

If you want to use both, first attach authentication and make sure you can log in and you can log out. Also make sure the non-public action is not accessible without being logged in and it is afterwards.
You just verified: authentication is working now fine - it doesn't matter who logged in as long as someone did.

Only if that is working, attach an Auth adapter (which now means authorization comes into play), in this case probably `Tiny`. Make sure that the session contains the correct data structure, also make sure the roles are configured or in the database and can be found as expected.

## Contributing
Feel free to fork and pull request.

There are a few guidelines:

- Coding standards passing: `./sniff` to check and `./sniff -f` to fix.
- Tests passing: `php phpunit.phar` to run them.
