# Installation

## Required dependencies

TinyAuth wraps CakePHP's official Authentication and Authorization plugins. Before using TinyAuth features, you **must** install and configure the official plugins:

| Feature | Required Plugin | Setup Guide |
| --- | --- | --- |
| **Authentication** (login/logout) | [cakephp/authentication](https://github.com/cakephp/authentication) | [/authentication/](/authentication/) + [Official Docs](https://book.cakephp.org/authentication/3/en/index.html) |
| **Authorization** (roles / ACL) | [cakephp/authorization](https://github.com/cakephp/authorization) | [/authorization/](/authorization/) + [Official Docs](https://book.cakephp.org/authorization/3/en/index.html) |

```bash
# For authentication features
composer require cakephp/authentication

# For authorization features
composer require cakephp/authorization
```

> **Standalone exception:** the [AuthUser component and helper](/auth-user) work without the official plugins — they integrate with any authentication solution that sets an identity in the request.

## Install TinyAuth

```bash
composer require dereuromark/cakephp-tinyauth
bin/cake plugin load TinyAuth
```

## Next steps

Pick the topic you actually need:

- [5-min Quick Start](/guide/quick-start) — minimal setup walkthrough.
- [Authentication](/authentication/) — full setup of the Authentication component + INI whitelist.
- [Authorization](/authorization/) — full setup of the Authorization component + role-based ACL.
- [AuthPanel](/auth-panel) — DebugKit panel for inspecting auth decisions.
