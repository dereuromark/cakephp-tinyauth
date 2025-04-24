# CakePHP TinyAuth Plugin

[![CI](https://github.com/dereuromark/cakephp-tinyauth/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/dereuromark/cakephp-tinyauth/actions/workflows/ci.yml?query=branch%3Amaster)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-tinyauth/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-tinyauth)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-tinyauth/master.svg)](https://codecov.io/github/dereuromark/cakephp-tinyauth/branch/master)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-tinyauth/license.svg)](LICENSE)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-tinyauth/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-tinyauth)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP plugin to handle authentication and user authorization the easy way.

This branch is for **CakePHP 5.0+**. For details see [version map](https://github.com/dereuromark/cakephp-tinyauth/wiki#cakephp-version-map).

## Features

### Authentication
What are public actions, which ones need login?

- Powerful default configs to get you started right away.
- [Quick Setup](https://github.com/dereuromark/cakephp-tinyauth/blob/master/docs/Authentication.md#quick-setups) for 5 minute integration.

### Authorization
Once you are logged in, what actions can you see with your role(s)?

- Single-role: 1 user has 1 role (users and roles table for example)
- Multi-role: 1 user can have 1...n roles (users, roles and a "roles_users" pivot table for example)
- [Quick Setup](https://github.com/dereuromark/cakephp-tinyauth/blob/master/docs/Authorization.md#quick-setups) for 5 minute integration.

### Useful helpers
- AuthUser Component and Helper for stateful and stateless "auth data" access.
- Authentication Component and Helper for `isPublic()` check on current other other actions.
- Auth DebugKit panel for detailed insights into current URL and auth status, identity content and more.

## What's the idea?
Default CakePHP authentication and authorization depends on code changes in at least each controller, maybe more classes.
This plugin hooks in with a single line of change and manages all that using config files and there is no need to touch all those controllers, including plugin controllers.

It is also possible to manage the config files without the need to code.
And it can with adapters also be moved completely to the DB and managed by CRUD backend.

Ask yourself: Do you need the overhead and complexity involved with a full blown (RBAC DB) ACL or very specific Policy approaches? 
See also my post [acl-access-control-lists-revised/](https://www.dereuromark.de/2015/01/06/acl-access-control-lists-revised/).
If not, then this plugin could very well be your answer and a super quick solution to your auth problem :)

But even if you don't leverage the full authentication or authorization potential, the available AuthUserComponent and AuthUserHelper
can be very useful when dealing with role based decisions in your controller or view level. They also work stand-alone.


## Demo
See https://sandbox.dereuromark.de/auth-sandbox

### auth_allow.ini
Define the public actions (accessible by anyone) per controller:
```ini
Users = index,view
Admin/Maintenance = pingCheck
PluginName.SomeController = *
MyPlugin.Api/V1 = *
```

### auth_acl.ini
Define what actions may be accessed by what logged-in user role:
```ini
[Users]
index = *
add,edit = user,super-user

[Admin/Users]
* = admin

[Translate.Admin/Languages]
* = *
```

### AuthUser component and helper
```php
$currentId = $this->AuthUser->id();

$isMe = $this->AuthUser->isMe($userEntity->id);

if ($this->AuthUser->hasRole('mod')) {
}

if ($this->AuthUser->hasAccess(['action' => 'secretArea'])) {
}

// Helper only
echo $this->AuthUser->link('Admin Backend', ['prefix' => 'Admin', 'action' => 'index']);
echo $this->AuthUser->postLink('Delete', ['action' => 'delete', $id], ['confirm' => 'Sure?']);
```

## Installation
Including the plugin is pretty much as with every other CakePHP plugin:

```bash
composer require dereuromark/cakephp-tinyauth
```

Then, to load the plugin:

```sh
bin/cake plugin load TinyAuth
```

That's it. It should be up and running.

## Docs
For setup and usage see [Docs](/docs).

Also note the original [blog post](https://www.dereuromark.de/2011/12/18/tinyauth-the-fastest-and-easiest-authorization-for-cake2/) and how it all started.
