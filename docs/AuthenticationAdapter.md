### Authentication Adapters

Implement the AllowAdapterInterface and make sure your getAllow() method returns an array like so:
```php
  // normal controller
  "Posts" => [
    "plugin" => null,
    "prefix" => null,
    "controller" => "Posts",
    "all" => false, // or true
    "allow" => [
    	// action to role id mapping
    ],
    "deny => [
    	// action to role id mapping
    ]
  ],
  // or plugin with admin prefix
  "Queue.admin/QueuedJobs" => [
    "plugin" => "Queue",
    "prefix" => "admin",
    "controller" => "QueuedJobs",
    "allow" => [
      "index" => [
        1
      ],
      "view" => [
        1
      ],
      "*" => [
        3
      ],
    ],
  ],
```

Unique array keys due to the internal `PluginName.prefix/ControllerName` syntax.
URL elements and then an array of actions mapped to their role id(s).
The `*` action key means "any".

With this you can easily built your own database adapter and manage your ACL via backend.
Make sure you bust the cache with each update/change, though.
