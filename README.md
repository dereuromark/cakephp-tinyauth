# CakePHP TinyAuth Plugin

This branch targets CakePHP 6 and PHP 8.5 or later. Use the `cake6` branches of companion CakePHP plugins during development.

[![CI](https://github.com/dereuromark/cakephp-tinyauth/actions/workflows/ci.yml/badge.svg?branch=cake6)](https://github.com/dereuromark/cakephp-tinyauth/actions/workflows/ci.yml?query=branch%3Amaster)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-tinyauth/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-tinyauth)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-tinyauth/master.svg)](https://codecov.io/github/dereuromark/cakephp-tinyauth/branch/master)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-tinyauth/license.svg)](LICENSE)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-tinyauth/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-tinyauth)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-purple.svg?style=flat-square)](https://github.com/php-fig-rectified/fig-rectified-standards)

TinyAuth adds public-action rules and role permissions to CakePHP’s official Authentication and Authorization plugins. Store those rules in `auth_allow.ini` and `auth_acl.ini` instead of repeating them in controllers.

Your application still provides login actions, authentication services, identities and role data. TinyAuth checks access to controller actions; use entity policies for permissions on individual records. The AuthUser component and helper expose user data and role checks, and can also work with a custom identity provider.

## Documentation

- [Installation](https://dereuromark.github.io/cakephp-tinyauth/guide/install): install and load the plugins you need.
- [Getting started](https://dereuromark.github.io/cakephp-tinyauth/guide/quick-start): configure a small public-action list and role ACL.
- [Authentication](https://dereuromark.github.io/cakephp-tinyauth/authentication/) and [Authorization](https://dereuromark.github.io/cakephp-tinyauth/authorization/): service integration and rule syntax.
- [Configuration](https://dereuromark.github.io/cakephp-tinyauth/guide/configuration): shared options and cache invalidation.
- [Upgrade guide](https://dereuromark.github.io/cakephp-tinyauth/guide/upgrade): migration notes and current behavior changes.

The searchable [documentation website](https://dereuromark.github.io/cakephp-tinyauth/) also covers adapters, impersonation, helpers, CLI commands and the DebugKit panel.

The [original CakePHP 2 blog post](https://www.dereuromark.de/2011/12/18/tinyauth-the-fastest-and-easiest-authorization-for-cake2/) describes the project’s beginnings; use the website above for current setup instructions.
