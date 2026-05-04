# Upgrade Guide

## Upgrading from TinyAuth 3.x (CakePHP 4.x)

**Breaking changes:** TinyAuth has fundamentally changed from a standalone auth solution to a wrapper around the official CakePHP plugins.

### What was removed

- All custom authenticate adapters (`FormAuthenticate`, `MultiColumnAuthenticate`, `BasicAuthenticate`, `DigestAuthenticate`).
- All custom password hashers (`DefaultPasswordHasher`, `FallbackPasswordHasher`, `WeakPasswordHasher`).
- `TinyAuth.Auth` component (replaced by `TinyAuth.Authentication` and `TinyAuth.Authorization`).
- `AuthComponent` and `LegacyAuthComponent`.
- Storage classes (`MemoryStorage`, `SessionStorage`).

### What you need to do

1. Install the official plugins:

   ```bash
   composer require cakephp/authentication cakephp/authorization
   ```

2. Replace component loading:

   ```php
   // OLD (removed):
   $this->loadComponent('TinyAuth.Auth', [...]);

   // NEW:
   $this->loadComponent('TinyAuth.Authentication', [...]);
   $this->loadComponent('TinyAuth.Authorization', [...]);
   ```

3. Set up middleware in your `Application` class — see [Authentication](/authentication/) and [Authorization](/authorization/) for the exact pattern.

4. Your INI files (`auth_allow.ini` and `auth_acl.ini`) continue to work as before.

### What still works

- AuthUser component and helper.
- INI-based configuration files.
- All role-based authorization features.
- DebugKit Auth panel.
