## DebugKit Auth Panel
The TinyAuth plugin ships with a useful DebugKit panel to show quickly if the current action
- is public (allowed in auth_allow.ini) or protected
- if protected what roles have access to it

Also:
- auth status of current user (guest, logged in, ...)
- if logged in your current role(s)

Public action (quick icon):

![public](/img/auth_public.png)

Protected action (quick icon):

![public](/img/auth_restricted.png)

Panel opened as a guest:

![panel](/img/panel_guest.png)

Panel opened with a logged-in user:

![panel](/img/panel.png)

### Enable the panel
Activate the panel in your config:

```php
    'DebugKit' => [
        'panels' => [
            // Other panels can remain here.
            'TinyAuth.Auth' => true,
        ],
    ],
```

Now it should be visible in your DebugKit panel list.

Note: If you only use TinyAuth authentication or authorization (and not both) it will usually detect this and not display the unused part.
Make sure you enabled the documented components and helpers here to have all features enabled.

### Notes
The allow/ACL data only works correctly, if you don't use any controller `allowUnauthenticated()` calls.
You should first migrate away from those before using this panel.
