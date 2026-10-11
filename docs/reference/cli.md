# CLI Commands

TinyAuth ships two console commands for editing `auth_acl.ini` from the shell. Both modify the file in place at the path configured by `TinyAuth.aclFilePath` (default `config/`) + `TinyAuth.aclFile` (default `auth_acl.ini`).

## `tiny_auth add`

Add or update a single controller / action / roles entry in the ACL file.

```bash
bin/cake tiny_auth add <controller> [action] [roles] [options]
```

### Arguments

| Argument | Required | Default | Notes |
| --- | --- | --- | --- |
| `controller` | no | prompted | Controller name without the `Controller` suffix. Use the full `Plugin.Prefix/Name` form for plugins / prefixed controllers (e.g. `MyPlugin.Admin/Articles`). |
| `action` | no | `*` | Action name (camelCased or under_scored). `*` means all actions. |
| `roles` | no | `*` | Comma-separated role names (e.g. `user,admin`). `*` means all roles. |

### Options

| Option | Short | Notes |
| --- | --- | --- |
| `--plugin` | `-p` | Scope for interactive controller discovery. Explicit controller names are used as supplied. Use `all` to consider loaded plugins. |
| `--dry-run` | `-d` | Show what would change without writing the INI file. |

### Interactive mode

With only a controller argument, the action and roles both default to `*`; there are no action or role prompts. Supply those arguments explicitly to restrict the entry.

If you call it with no arguments at all, it lists the discovered controllers and asks you to pick one.

### Examples

```bash
# Allow user and admin roles on Articles::index
bin/cake tiny_auth add Articles index user,admin
# → adds [Articles] index = user, admin

# Allow admin on every Articles action
bin/cake tiny_auth add Articles "*" admin
# → adds [Articles] * = admin

# Allow admin on a plugin's admin-prefixed Articles::edit
bin/cake tiny_auth add MyPlugin.Admin/Articles edit admin
# → adds [MyPlugin.Admin/Articles] edit = admin

# Preview without writing
bin/cake tiny_auth add Articles index user --dry-run
```

## `tiny_auth sync`

Scan all discovered controllers and add any that don't yet have an ACL entry. Existing entries are **never** modified ; sync only adds missing rows with the wildcard action.

```bash
bin/cake tiny_auth sync <roles> [options]
```

### Arguments

| Argument | Required | Notes |
| --- | --- | --- |
| `roles` | yes | Comma-separated role names. `*` means all roles. |

### Options

| Option | Short | Notes |
| --- | --- | --- |
| `--plugin` | `-p` | Plugin scope. Use `all` to include controllers from every loaded plugin. |
| `--dry-run` | `-d` | Show what would change without writing the INI file. |

### Examples

```bash
# Add every missing controller with: * = user, admin
bin/cake tiny_auth sync user,admin

# Same, but include all plugins, and grant access to all roles
bin/cake tiny_auth sync "*" --plugin all

# Preview only
bin/cake tiny_auth sync admin --dry-run
```

## Workflow

Create `config/auth_allow.ini` and `config/auth_acl.ini` before running either command. Configure your role source as described in [Authorization Setup](/authorization/).

Preview new entries before writing them:

```bash
bin/cake tiny_auth sync admin --dry-run --verbose
```

Review the controller names and role choices, then run the command without `--dry-run` when those entries match your access rules. Use `add` to set individual actions. `sync` preserves any controller section that already exists, including sections with only some actions configured.

## Notes

- Both commands respect `TinyAuth.aclFilePath` and `TinyAuth.aclFile` overrides for non-default INI locations.
- Both commands list discovered roles in their `--help` output (pulled from `TinyAuth\Utility\TinyAuth::getAvailableRoles()`).
- Successful runs return exit code `0`. There's no separate "no changes made" exit code ; `--dry-run` is the way to detect drift.
