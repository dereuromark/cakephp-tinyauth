# CakePHP TinyAuth Plugin

[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-tinyauth.svg?branch=master)](https://travis-ci.org/dereuromark/cakephp-tinyauth)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-tinyauth/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-tinyauth)
[![Coverage Status](https://coveralls.io/repos/dereuromark/cakephp-tinyauth/badge.svg)](https://coveralls.io/r/dereuromark/cakephp-tinyauth)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-tinyauth/license.svg)](https://packagist.org/packages/dereuromark/cakephp-tinyauth)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-tinyauth/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-tinyauth)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP 3.x plugin to handle user authorization the easy way.

## Demo
```ini
[Users]
index = *
add,edit = user, mod
* = admin

[admin/Users]
* = admin
```

See http://sandbox3.dereuromark.de/auth-sandbox

## How to include
Installing the plugin is pretty much as with every other CakePHP Plugin.

```bash
composer require dereuromark/cakephp-tinyauth:dev-master
```

Then, to load the plugin either run the following command:

```sh
bin/cake plugin load TinyAuth
```

or manually add the following line to your app's `config/bootstrap.php` file:

```php
Plugin::load('TinyAuth');
```

That's it. It should be up and running.

## Docs
See [Docs](/docs).

Also note the original [blog post](http://www.dereuromark.de/2011/12/18/tinyauth-the-fastest-and-easiest-authorization-for-cake2/) and how it all started.

### Branching strategy
The master branch is the currently active and maintained one and works with the current 3.x stable version.
Please see the original [Tools plugin](https://github.com/dereuromark/cakephp-tools) if you need TinyAuth for CakePHP 2.x versions.
