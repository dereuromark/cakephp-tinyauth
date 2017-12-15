## Installation & Configuration Multi-role

```bash
composer require dereuromark/cakephp-tinyauth
```

```php
Plugin::load('TinyAuth');
```

```bash
bin/cake migrations migrate -p TinyAuth
```

```php
// in your app.php
'TinyAuth' => [
	'multiRole' => true
]
```

```php
// in your AppController.php
$this->loadComponent('TinyAuth.Auth', [
	'autoClearCache' => Configure::read('debug'),
	'authorize' => [
		'TinyAuth.Tiny' => [
			'autoClearCache' => Configure::read('debug')
		]
	],
	...
]);
```

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

### acl.ini
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

### Helper
```php
// in your AppView.php
$this->loadHelper('TinyAuth.AuthUser');

// e.g
if ($this->AuthUser->hasRole('admin') { // Either by alias or id
	// OK, do something now
}
```
