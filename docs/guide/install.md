# Installation

The `cake6` branch requires CakePHP 6 and PHP 8.5. Development dependencies require the application to allow development stability, even when TinyAuth declares its own `@dev` constraints. Keep `prefer-stable` enabled for other packages.

## Required dependencies

TinyAuth wraps CakePHP's official Authentication and Authorization plugins. Before using TinyAuth features, you must install and configure the official plugins:

| Feature | Required Plugin | Setup Guide |
| --- | --- | --- |
| Authentication (login/logout) | [cakephp/authentication](https://github.com/cakephp/authentication) | [/authentication/](/authentication/) + [Authentication 5](https://book.cakephp.org/authentication/5/en/index.html) |
| Authorization (roles / ACL) | [cakephp/authorization](https://github.com/cakephp/authorization) | [/authorization/](/authorization/) + [Official Docs](https://book.cakephp.org/authorization/4/en/index.html) |

```bash
composer config minimum-stability dev
composer config prefer-stable true

# For authentication features
composer require 'cakephp/authentication:^5.0@dev'

# For authorization features
composer require 'cakephp/authorization:^4.0@dev'
```

> Standalone exception: the [AuthUser component and helper](/auth-user) work without the official plugins: they integrate with any authentication solution that sets an identity in the request.

## Install TinyAuth

```bash
composer require dereuromark/cakephp-tinyauth:dev-cake6
bin/cake plugin load TinyAuth
```

## Next steps

Pick the topic you actually need:

- [Getting started](/guide/quick-start): configure the INI files after the underlying services are working.
- [Authentication](/authentication/): full setup of the Authentication component + INI whitelist.
- [Authorization](/authorization/): full setup of the Authorization component + role-based ACL.
- [AuthPanel](/auth-panel): DebugKit panel for inspecting auth decisions.
