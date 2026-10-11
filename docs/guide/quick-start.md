# Getting started

Configure TinyAuth for an application that already has working login, an identity with role information, and the official Authentication and Authorization middleware. Complete [Installation](./install) first. The [Authentication](/authentication/) and [Authorization](/authorization/) guides cover those prerequisites.

## 1. Whitelist public actions

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

## 2. Define role permissions

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

## 3. Load TinyAuth components

In `AppController`:

```php
public function initialize(): void {
    parent::initialize();
    $this->loadComponent('TinyAuth.Authentication');
    $this->loadComponent('TinyAuth.Authorization');
}
```

With the underlying services and login redirects configured, non-public actions require authentication. Logged-in users are checked against `auth_acl.ini`. Adapt the controller and action names to the routes in your application.

## 4. Verify in the browser

- Try `/pages/display/home`: works without login (whitelisted).
- Try `/users/index`: redirects to `/users/login`.
- Log in, then try `/users/profile` as a `user` role → works. As something else → forbidden.

## Where to next

- [Authentication](/authentication/): full options for the Authentication component, INI syntax for `auth_allow.ini`.
- [Authorization](/authorization/): full options for the Authorization component, INI syntax for `auth_acl.ini`.
- [Multi-role setup](/authorization/multi-role): when a user has more than one role.
- [AuthUser](/auth-user): controller / view helpers for "is this user an admin?" checks.
- [AuthPanel](/auth-panel): DebugKit panel for inspecting decisions.
- [Troubleshooting](/guide/troubleshooting): when something doesn't behave.
