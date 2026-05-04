# Impersonation

TinyAuth ships a `PrimaryKeySessionAuthenticator` that enables the classic "admin logs in as another user" pattern. The original (impersonator) identity is stored in a separate session key so you can return to it at any time.

## Setup

Replace the default Cake `SessionAuthenticator` with TinyAuth's variant in your `AuthenticationServiceProvider`:

```php
use TinyAuth\Authenticator\PrimaryKeySessionAuthenticator;

$service->loadAuthenticator(PrimaryKeySessionAuthenticator::class, [
    'sessionKey' => 'Auth',
    'identifierKey' => 'key',          // identifier lookup key
    'idField' => 'id',                  // user PK column
    'impersonateSessionKey' => 'AuthImpersonator', // where the original user's id is stashed
    'cache' => false,                   // optional in-process SessionCache
]);
```

The authenticator only persists the user's primary key in the session — the rest of the identity is reloaded on each request via the configured identifier. That's what makes safe role/permission swaps possible.

### Config keys

| Key | Default | What it does |
| --- | --- | --- |
| `sessionKey` | from parent | Session key holding the active user's primary key. |
| `identifierKey` | `key` | The identifier field used by the loaded `IdentifierInterface` to look up the user. |
| `idField` | `id` | The column on the user record containing the primary key. |
| `impersonateSessionKey` | (none — set this) | Session key that stores the ORIGINAL impersonator's id during an active impersonation. |
| `cache` | `false` | If `true`, results are stored in `TinyAuth\Utility\SessionCache` to avoid re-loading the user every request. Cleared on logout. |

## Start impersonating

In your controller (typically an admin action), call `impersonate()`:

```php
use TinyAuth\Authenticator\PrimaryKeySessionAuthenticator;

public function impersonate($targetUserId) {
    $service = $this->Authentication->getAuthenticationService();
    $authenticator = $service->authenticators()->get('PrimaryKeySession');

    $impersonator = $this->Authentication->getIdentity()->getOriginalData();
    $impersonated = $this->fetchTable('Users')->get($targetUserId);

    $result = $authenticator->impersonate(
        $this->getRequest(),
        $this->getResponse(),
        $impersonator,
        $impersonated,
    );

    $this->setRequest($result['request']);

    return $this->redirect(['controller' => 'Pages', 'action' => 'home']);
}
```

After this, `$this->Authentication->getIdentity()` returns the **impersonated** user, and the original impersonator's id is in `$session->read('AuthImpersonator')`.

## Detect an active impersonation

```php
$impersonatorId = $this->getRequest()->getSession()->read('AuthImpersonator');
if ($impersonatorId) {
    // Show "Stop impersonating" banner
}
```

## Stop impersonating

Reverse the swap by writing the impersonator's id back into the active session key and clearing the impersonate marker:

```php
public function stopImpersonating() {
    $session = $this->getRequest()->getSession();
    $impersonatorId = $session->read('AuthImpersonator');

    if (!$impersonatorId) {
        $this->Flash->error(__('Not currently impersonating.'));

        return $this->redirect($this->referer());
    }

    $session->write('Auth', $impersonatorId);
    $session->delete('AuthImpersonator');

    return $this->redirect(['controller' => 'Pages', 'action' => 'home']);
}
```

## Constraints

- Calling `impersonate()` while already impersonating throws `Cake\Http\Exception\UnauthorizedException` — only one level deep.
- `cache => true` is in-process only (`SessionCache` lives for the duration of the request). It's a micro-optimization, not a persistent cache.
- The authenticator only stores the primary key, not the full user data. If your `IdentifierInterface` is slow, expect the cost on every request unless `cache` is enabled.

## Authorization gate

Don't forget to gate the `impersonate` and `stopImpersonating` actions in your `auth_acl.ini`:

```ini
[Admin/Users]
impersonate = admin
stopImpersonating = *
```
