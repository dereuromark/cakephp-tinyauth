## Installation & Configuration Multi-role

```bash
composer require dereuromark/cakephp-tinyauth
```

```php
Plugin::load('TinyAuth');
```

#### for Multi-role
```bash
bin/cake migrations migrate -p TinyAuth
```

#### for Multi-role
```php
// in your app.php
'TinyAuth' => [
	'multiRole' => true
]
```

```php
// in your AppController.php
$this->loadComponent('TinyAuth.Auth', [
	'autoClearCache' => true,
	'authorize' => ['TinyAuth.Tiny'],
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

### AuthUser Helper
```php
// in your AppView.php
$this->loadHelper('TinyAuth.AuthUser');
```

```php
// e.g Template - Element - menu
if ($this->AuthUser->hasRole('admin')) {
	<li class="nav-item"><?= $this->Html->link(__('Settings'), ['controller' => 'Settings', 'action' => 'index'], ['class' => 'nav-link']) ?></li>
}
```

```php
echo $this->AuthUser->link('Admin Backend', ['prefix' => 'admin', 'action' => 'index']);
```

### AuthUser Component
```php
// in your Controller
$this->loadComponent('TinyAuth.AuthUser');
```

```php
// e.g ArticlesController - edit
if ($this->AuthUser->hasRole('author')) {
	if (!$this->AuthUser->isMe($article->user_id)){
		$this->Flash->error(__('You are not authorized to access that location.'));
		return $this->redirect(['action' => 'index']);
	}
}
```
