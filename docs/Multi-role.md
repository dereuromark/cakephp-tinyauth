## Configuration Multi-role

**IMPORTANT:** First install the official plugins as described in [docs/README.md](README.md#required-dependencies)

```php
// in your app.php
'TinyAuth' => [
    'multiRole' => true,
]
```

```php
// in your AppController.php
public function initialize() {
    parent::initialize();

    $this->loadComponent('TinyAuth.Authentication');
    $this->loadComponent('TinyAuth.Authorization', [
        'autoClearCache' => true,
    ]);
}
```

See [Authentication.md](Authentication.md) and [Authorization.md](Authorization.md) for complete middleware setup instructions.

### auth_allow.ini
```ini
// in config folder
; ----------------------------------------------------------
; PagesController
; ----------------------------------------------------------
Pages = display
; ----------------------------------------------------------
; UsersController
; ----------------------------------------------------------
Users = login
```

### auth_acl.ini
```ini
// in config folder
; ----------------------------------------------------------
; RolesController
; ----------------------------------------------------------
[Roles]
* = admin
; ----------------------------------------------------------
; UsersController
; ----------------------------------------------------------
[Users]
edit, index, logout = author
* = admin
; ----------------------------------------------------------
; ArticlesController
; ----------------------------------------------------------
[Articles]
* = author, admin
; ----------------------------------------------------------
; CategoriesController
; ----------------------------------------------------------
[Categories]
* = author, admin
; ----------------------------------------------------------
; TagsController
; ----------------------------------------------------------
[Tags]
* = author, admin
; ----------------------------------------------------------
; ImagesController
; ----------------------------------------------------------
[Images]
* = author, admin
; ----------------------------------------------------------
; MenusController
; ----------------------------------------------------------
[Menus]
* = admin
; ----------------------------------------------------------
; SettingsController
; ----------------------------------------------------------
[Settings]
* = admin
```
