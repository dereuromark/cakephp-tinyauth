# Troubleshooting

## Step-by-step debugging

**Never mix authentication and authorization when troubleshooting.** Test each separately:

### 1. Test Authentication first

Set up **only** authentication ([full guide](/authentication/)):

- Load the `TinyAuth.Authentication` component.
- Configure `config/auth_allow.ini` for public actions.
- Verify you can log in and log out.
- Verify non-public actions require login.
- Verify public actions are accessible without login.

Once this works, authentication is configured correctly.

### 2. Then test Authorization

Add authorization **only after** authentication works ([full guide](/authorization/)):

- Load the `TinyAuth.Authorization` component.
- Set up roles (database or `Configure`).
- Configure `config/auth_acl.ini` for role permissions.
- Clear cache: `bin/cake cache clear_all`.
- Verify role-based access works correctly.

Once this works, both authentication and authorization are configured.

## Common issues

| Symptom | Likely cause |
| --- | --- |
| Always redirected to login, even on whitelisted actions | INI file path wrong, or cache stale (run `cache clear_all`). |
| "Roles not found" | Session structure doesn't match the expected format — check the `role_id` column or the role-loading config. |
| Changed INI file but nothing happened | Cache not cleared after the edit. In debug mode this is automatic; in production you must run `cache clear_all` after deploy. |
| ACL rule appears to be ignored | Syntax error in `auth_acl.ini` — check the format `actions = roles` per line. |
| Multi-role user denied access | See [Multi-role setup](/authorization/multi-role). |

## Use the DebugKit Auth panel

If you have DebugKit enabled in development, the [AuthPanel](/auth-panel) shows per-URL: which rule matched, why a request was allowed or denied, and what role the current user has. This is usually the fastest way to find a misconfigured rule.
