# Guide

TinyAuth provides simple, INI-based configuration for authentication and authorization in CakePHP applications. It wraps CakePHP's official `cakephp/authentication` and `cakephp/authorization` plugins and replaces controller-level allow/deny calls with two INI files.

## Authentication vs Authorization

Understanding the difference is crucial:

| Concept | Question | When | Purpose |
| --- | --- | --- | --- |
| **Authentication** | "Who are you?" | First | Determines if a user is logged in |
| **Authorization** | "What can you do?" | After authentication | Determines if a user can access a specific action |

- **Authentication** defines public actions via whitelist. Any non-whitelisted action triggers the login process.
- **Authorization** only applies to logged-in users. The user's role(s) determine which actions they can access.

## Where to start

- [Installation](/guide/install) — required dependencies and one-time setup.
- [5-min Quick Start](/guide/quick-start) — the smallest working setup.
- [Configuration](/guide/configuration) — global config keys, cache busting, custom adapters.
- [Troubleshooting](/guide/troubleshooting) — step-by-step debugging when things don't behave.
- [Upgrade Guide](/guide/upgrade) — moving from TinyAuth 3.x (Cake 4) to 5.x (Cake 5).

## Why use TinyAuth

- **Zero-code configuration** — all auth rules in INI files, no controller modifications needed.
- **Instant setup** — working authentication / authorization in under 5 minutes.
- **Plugin compatibility** — works automatically with all plugins without modifications.
- **Centralized management** — all rules in one place, not scattered across controllers.
- **Performance** — built-in caching for optimal speed.
- **Developer friendly** — DebugKit panel, clear error messages, easy debugging.

### When to use TinyAuth

- Controller-action level permissions
- Simple role-based access control (RBAC)
- Quick setup without extensive configuration

### When you might also need official plugin features directly

Consider using the official plugins' advanced features (alongside TinyAuth) when you need:

- Complex policy-based authorization (ORM policies, custom voters)
- Per-entity / row-level authorization rules
- Custom authentication flows beyond what TinyAuth provides

You can use both approaches together. TinyAuth's INI files work alongside the official plugins' advanced features.
