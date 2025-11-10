# AuthUser Component and Helper

The AuthUser component and helper provide convenient methods for working with the currently authenticated user in your controllers and views.

**Key Feature:** These work **standalone** and do not require the official Authentication or Authorization plugins. They work with any authentication solution that sets an identity in the request attributes.

## AuthUser Component

The component provides easy access to the current user's data and permission checks in your controllers.

### Setup

Load the component in your AppController:

```php
// src/Controller/AppController.php

public function initialize(): void {
    parent::initialize();

    $this->loadComponent('TinyAuth.AuthUser');
}
```

### Available Methods

#### `id()`
Get the current user's ID:

```php
$userId = $this->AuthUser->id();
```

#### `isMe(int|string $id)`
Check if a given ID belongs to the current user:

```php
if ($this->AuthUser->isMe($post->user_id)) {
    // User owns this post
}
```

#### `hasRole(string|int $role)`
Check if the user has a specific role (by alias or ID):

```php
if ($this->AuthUser->hasRole('admin')) {
    // User is an admin
}

if ($this->AuthUser->hasRole(ROLE_MODERATOR)) {
    // User is a moderator (using constant)
}
```

#### `hasRoles(array $roles)`
Check if the user has any of the specified roles:

```php
if ($this->AuthUser->hasRoles(['admin', 'moderator'])) {
    // User is either admin or moderator
}
```

#### `roles()`
Get all roles for the current user:

```php
$userRoles = $this->AuthUser->roles();
```

#### `hasAccess(array $url)`
Check if the user has access to a specific URL (checks ACL):

```php
if ($this->AuthUser->hasAccess(['action' => 'delete', $id])) {
    return $this->redirect(['action' => 'delete', $id]);
}
// Do something else instead
```

**Note:** By default, `hasAccess()` only checks the `auth_acl.ini` file, not `auth_allow.ini`. Set `includeAuthentication` config to `true` if you need to check public actions as well.

#### `identity()`
Get the full identity object/array:

```php
$identity = $this->AuthUser->identity();
// Returns the original data from Authentication/Authorization identity
```

### Example Usage

```php
// In a controller action
public function edit($id) {
    $post = $this->Posts->get($id);

    // Only allow editing own posts unless admin
    if (!$this->AuthUser->isMe($post->user_id) && !$this->AuthUser->hasRole('admin')) {
        $this->Flash->error(__('You can only edit your own posts.'));
        return $this->redirect(['action' => 'index']);
    }

    // Continue with edit logic...
}
```

## AuthUser Helper

The helper provides the same functionality in your templates, plus additional methods for conditionally displaying links.

### Setup

Load the helper in your AppView:

```php
// src/View/AppView.php

public function initialize(): void {
    parent::initialize();

    $this->loadHelper('TinyAuth.AuthUser');
}
```

**Important:** The helper requires the component to be loaded, as it needs data passed from the controller.

### Available Methods

The helper has all the same methods as the component:
- `id()`
- `isMe($id)`
- `hasRole($role)`
- `hasRoles($roles)`
- `roles()`
- `hasAccess($url)`
- `identity()`

### Additional Helper-Only Methods

#### `link(string $title, array $url, array $options = [])`
Display a link only if the user has access to that URL:

```php
// Only displays if user has access to Admin prefix
echo $this->AuthUser->link('Admin Backend', ['prefix' => 'Admin', 'action' => 'index']);
```

If the user doesn't have access, nothing is displayed.

#### `postLink(string $title, array $url, array $options = [])`
Display a POST link only if the user has access:

```php
// Only displays delete link if user has delete permission
echo $this->AuthUser->postLink('Delete', ['action' => 'delete', $id], [
    'confirm' => 'Are you sure?',
]);
```

### Named Routes Support

Both `link()` and `postLink()` support named routes:

```php
<?= $this->AuthUser->link('Change Password', ['_name' => 'admin:account:password']); ?>
```

### Template Examples

#### Conditional Content Display

```php
<?php if ($this->AuthUser->hasRole('admin')): ?>
    <div class="admin-tools">
        <?= $this->AuthUser->link('Manage Users', ['controller' => 'Users', 'action' => 'index']); ?>
        <?= $this->AuthUser->link('Settings', ['controller' => 'Settings', 'action' => 'index']); ?>
    </div>
<?php endif; ?>
```

#### Show/Hide Based on Ownership

```php
<?php if ($this->AuthUser->isMe($comment->user_id)): ?>
    <?= $this->AuthUser->postLink(
        'Delete Comment',
        ['action' => 'delete', $comment->id],
        ['confirm' => 'Are you sure?']
    ); ?>
<?php endif; ?>
```

#### Complex Access Checks

```php
<?php if ($this->AuthUser->hasAccess(['action' => 'secretArea'])): ?>
    <div class="secret-section">
        <p>Only for you:</p>
        <?= $this->Html->link('Secret Area', ['action' => 'secretArea']); ?>
        <small>(do not tell anyone else!)</small>
    </div>
<?php endif; ?>
```

#### Role-Based Navigation

```php
<nav>
    <ul>
        <li><?= $this->Html->link('Home', ['action' => 'index']); ?></li>

        <?php if ($this->AuthUser->hasRole('user')): ?>
            <li><?= $this->AuthUser->link('My Posts', ['action' => 'myPosts']); ?></li>
        <?php endif; ?>

        <?php if ($this->AuthUser->hasRoles(['admin', 'moderator'])): ?>
            <li><?= $this->AuthUser->link('Moderation', ['action' => 'moderate']); ?></li>
        <?php endif; ?>

        <?php if ($this->AuthUser->hasRole('admin')): ?>
            <li><?= $this->AuthUser->link('Admin', ['prefix' => 'Admin', 'action' => 'dashboard']); ?></li>
        <?php endif; ?>
    </ul>
</nav>
```

## Configuration

### Including Authentication in Access Checks

By default, `hasAccess()` only checks authorization (ACL), not authentication (public actions).

To include public actions in the access check:

```php
$this->loadComponent('TinyAuth.AuthUser', [
    'includeAuthentication' => true,
]);
```

**Note:** This only works with INI-based configuration. Controller-level `allow()` calls cannot be detected.

## Best Practices

### Use Constants for Roles

Instead of magic strings, define role constants:

```php
// In your bootstrap
define('ROLE_USER', 'user');
define('ROLE_MODERATOR', 'moderator');
define('ROLE_ADMIN', 'admin');

// In your code
if ($this->AuthUser->hasRole(ROLE_ADMIN)) {
    // Much better than: hasRole('admin')
}
```

This provides:
- IDE autocompletion
- Easy refactoring
- Prevents typos
- Self-documenting code

### Combine with Form Helper

```php
<?php
echo $this->Form->create($post);
echo $this->Form->control('title');
echo $this->Form->control('body');

// Only admins can change the status
if ($this->AuthUser->hasRole('admin')) {
    echo $this->Form->control('status');
}

echo $this->Form->button(__('Submit'));
echo $this->Form->end();
?>
```

## Standalone Usage

AuthUser works with any authentication solution, not just TinyAuth:

- **CakePHP Authentication plugin** - Works out of the box
- **CakePHP Authorization plugin** - Works out of the box
- **Custom auth** - As long as you set an identity in request attributes

The component looks for the identity in `$request->getAttribute('identity')` and works with any object that implements `getOriginalData()` or is an array.
