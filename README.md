# CakePHP TinyAuth Plugin

[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-tinyauth.svg?branch=master)](https://travis-ci.org/dereuromark/cakephp-tinyauth)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-tinyauth/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-tinyauth)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-tinyauth/master.svg)](https://codecov.io/github/dereuromark/cakephp-tinyauth?branch=master)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-tinyauth/license.svg)](https://packagist.org/packages/dereuromark/cakephp-tinyauth)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-tinyauth/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-tinyauth)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP plugin to handle authentication and user authorization the easy way.

This branch is for **CakePHP 3.7+**. For details see [version map](https://github.com/dereuromark/cakephp-tinyauth/wiki#cakephp-version-map).

## Features

### Authentication
What are public actions, which ones need login?

### Authorization
Once you are logged in, what actions can you see with your role(s)?

- Single-role: 1 user has 1 role (users and roles table for example)
- Multi-role: 1 user can have 1...n roles (users, roles and a "roles_users" pivat table for example)

### Useful helpers
AuthUser Component and Helper for stateful and stateless "auth data" access.

## What's the idea?
Default CakePHP authentication and authorization depends on code changes in at least each controller, maybe more classes.
This plugin hooks in with a single line of change and manages all that using config files and there is no need to touch all those controllers, including plugin controllers.

It is also possible to manage the config files without the need of coding skills. And it could with some effort also be moved completely to the DB and managed by CRUD backend.

Ask yourself: Do you need the overhead and complexity involved with the core CakePHP ACL? See also my post [acl-access-control-lists-revised/](https://www.dereuromark.de/2015/01/06/acl-access-control-lists-revised/).
If not, then this plugin could very well be your answer and a super quick solution to your auth problem :)

But even if you don't leverage the authentication or authorization, the available AuthUserComponent and AuthUserHelper
can be very useful when dealing with role based decisions in your controller or view level. They also work stand-alone.


## Demo
See https://sandbox.dereuromark.de/auth-sandbox

### auth_allow.ini
Define the public actions (accessable by anyone) per controller:
```ini
Users = index,view
admin/Maintenance = pingCheck
PluginName.SomeController = *
MyPlugin.api/V1 = *
```

### acl.ini
Define what actions may be accessed by what logged-in user role:
```ini
[Users]
index = *
add,edit = user,mod

[admin/Users]
* = admin

[Translate.admin/Languages]]
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
echo $this->AuthUser->link('Admin Backend', ['prefix' => 'admin', 'action' => 'index']);
echo $this->AuthUser->postLink('Delete', ['action' => 'delete', $id], ['confirm' => 'Sure?']);
```

## Installation
Including the plugin is pretty much as with every other CakePHP plugin:

```bash
composer require dereuromark/cakephp-tinyauth
```

Then, to load the plugin either run the following command:

```sh
bin/cake plugin load TinyAuth
```

or manually add the following line to your app's `src/Application.php` file's `bootstrap()` function:

```php
$this->addPlugin('TinyAuth');
```

That's it. It should be up and running.

## Docs
For setup and usage see [Docs](/docs).

Also note the original [blog post](https://www.dereuromark.de/2011/12/18/tinyauth-the-fastest-and-easiest-authorization-for-cake2/) and how it all started.

### Branching strategy
The master branch is the currently active and maintained one and works with the current 3.x stable version.
Please see the original [Tools plugin](https://github.com/dereuromark/cakephp-tools) if you need TinyAuth for CakePHP 2.x versions.
