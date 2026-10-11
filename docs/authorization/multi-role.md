# Multi-role authorization

**IMPORTANT:** First install the official plugins as described in [Installation Guide](/guide/install#required-dependencies)

```php
// in your app.php
'TinyAuth' => [
    'multiRole' => true,
]
```

```php
// in your AppController.php
public function initialize(): void {
    parent::initialize();

    $this->loadComponent('TinyAuth.Authentication');
    $this->loadComponent('TinyAuth.Authorization', [
        'autoClearCache' => true,
    ]);
}
```

See [Authentication](/authentication/) and [Authorization](/authorization/) for complete middleware setup instructions.

## Role membership

Configure your role aliases through a database Roles table or a Configure role map, as shown in [Authorization](/authorization/). With `multiRole` enabled, TinyAuth reads embedded role data from the identity first, then queries the pivot table when none is present.

The default pivot table alias is `RolesUsers` (database table `roles_users`) with `user_id` and `role_id` fields. Set `pivotTable`, `userColumn`, and `roleColumn` when your schema differs. These tables and memberships must already exist; enabling the option does not create them.

## auth_allow.ini
```ini
; in config folder
; ----------------------------------------------------------
; PagesController
; ----------------------------------------------------------
Pages = display
; ----------------------------------------------------------
; UsersController
; ----------------------------------------------------------
Users = login
```

## auth_acl.ini
```ini
; in config folder
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
