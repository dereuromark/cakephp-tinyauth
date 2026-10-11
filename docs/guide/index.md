# Guide

TinyAuth stores public-action rules and role permissions in two files. It extends CakePHP’s official Authentication and Authorization plugins; those plugins still provide login, identities, middleware, and entity policies.

| File | Purpose |
| --- | --- |
| `auth_allow.ini` | Actions available without an authenticated identity |
| `auth_acl.ini` | Controller actions available to each role |

Start with [Installation](/guide/install), then [Getting started](/guide/quick-start). The [Authentication](/authentication/) and [Authorization](/authorization/) guides explain the integration and rule syntax. See [Configuration](/guide/configuration) for shared options and cache invalidation, or [Troubleshooting](/guide/troubleshooting) when a request behaves unexpectedly.

TinyAuth checks access to an action. Use entity policies or equivalent server-side checks to decide whether a user may read or change a particular record. The [AuthUser helper](/auth-user) can hide links based on those action permissions; hiding a link does not protect its endpoint.
