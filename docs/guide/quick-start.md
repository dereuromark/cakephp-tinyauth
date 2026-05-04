# 5-min Quick Start

The smallest working setup. Two INI files, two components, you're done.

## 1. Install plugins

```bash
composer require cakephp/authentication cakephp/authorization dereuromark/cakephp-tinyauth
bin/cake plugin load TinyAuth
```

The [official plugins](https://book.cakephp.org/authentication/3/en/index.html) need their own initial setup (Application class middleware, an `AuthenticationServiceProviderInterface` implementation, a Users table). See [Authentication](/authentication/) and [Authorization](/authorization/) for full details.

## 2. Whitelist public actions

Create `config/auth_allow.ini`:

```ini
[Pages]
display = *

[Users]
login = *
register = *
forgotPassword = *

; everything not listed here will require authentication
```

`*` means "any role", which for unauthenticated routes really means "everyone".

## 3. Define role permissions

Create `config/auth_acl.ini` (only needed if you also use authorization):

```ini
[Pages]
* = *

[Users]
* = admin
profile = user, admin
edit = user, admin

[Admin/Users]
* = admin
```

Each section is a controller (or `Prefix/Controller`). Each line maps `actions = roles`.

## 4. Load TinyAuth components

In `AppController`:

```php
public function initialize(): void {
    parent::initialize();
    $this->loadComponent('TinyAuth.Authentication');
    $this->loadComponent('TinyAuth.Authorization');
}
```

That's it. Visiting any non-whitelisted action now redirects to login; logged-in users are checked against `auth_acl.ini`.

## 5. Verify in the browser

- Try `/pages/display/home` — works without login (whitelisted).
- Try `/users/index` — redirects to `/users/login`.
- Log in, then try `/users/profile` as a `user` role → works. As something else → forbidden.

## Where to next

- [Authentication](/authentication/) — full options for the Authentication component, INI syntax for `auth_allow.ini`.
- [Authorization](/authorization/) — full options for the Authorization component, INI syntax for `auth_acl.ini`.
- [Multi-role setup](/authorization/multi-role) — when a user has more than one role.
- [AuthUser](/auth-user) — controller / view helpers for "is this user an admin?" checks.
- [AuthPanel](/auth-panel) — DebugKit panel for inspecting decisions.
- [Troubleshooting](/guide/troubleshooting) — when something doesn't behave.
