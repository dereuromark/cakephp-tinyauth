# Impersonation

TinyAuth ships a `PrimaryKeySessionAuthenticator` that enables the classic "admin logs in as another user" pattern. The original (impersonator) identity is stored in a separate session key so you can return to it at any time.

## Setup

With Authentication 4, replace the default Session authenticator in `getAuthenticationService()` with the following configuration. This example assumes a `Users` table with an `id` primary key:

```php
$service->loadAuthenticator('TinyAuth.PrimaryKeySession', [
    'identifier' => [
        'className' => 'Authentication.Token',
        'tokenField' => 'id',
        'dataField' => 'key',
        'resolver' => ['className' => 'Authentication.Orm'],
    ],
    'sessionKey' => 'Auth',
    'identifierKey' => 'key',          // identifier lookup key
    'idField' => 'id',                  // user PK column
    'impersonateSessionKey' => 'AuthImpersonator', // where the original user's id is stashed
    'cache' => false,                   // optional persistent user cache
]);
```

The authenticator stores only the user’s primary key in the session. With caching disabled, it reloads the identity through the configured identifier on each request. For Authentication 3, configure the Token identifier using its [versioned setup guide](https://book.cakephp.org/authentication/3/en/quick-start.html).

### Config keys

| Key | Default | What it does |
| --- | --- | --- |
| `sessionKey` | from parent | Session key holding the active user's primary key. |
| `identifierKey` | `key` | The identifier field used by the loaded `IdentifierInterface` to look up the user. |
| `idField` | `id` | The column on the user record containing the primary key. |
| `impersonateSessionKey` | `AuthImpersonate` | Session key that stores the ORIGINAL impersonator's id during an active impersonation. |
| `cache` | `false` | If `true`, results are stored in `TinyAuth\Utility\SessionCache` to avoid re-loading the user every request. Cleared on logout. |

## Start impersonating

In your controller (typically an admin action), call `impersonate()`:

```php
use TinyAuth\Authenticator\PrimaryKeySessionAuthenticator;

public function impersonate($targetUserId) {
    $this->getRequest()->allowMethod(['post']);
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

    return $this->redirect('/');
}
```

The session now contains the target user’s ID and the original ID under `AuthImpersonator`. The request’s current identity remains unchanged; the redirected request authenticates as the target user.

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
    $this->getRequest()->allowMethod(['post']);
    $session = $this->getRequest()->getSession();
    $impersonatorId = $session->read('AuthImpersonator');

    if (!$impersonatorId) {
        $this->Flash->error(__('Not currently impersonating.'));

        return $this->redirect($this->referer());
    }

    $service = $this->Authentication->getAuthenticationService();
    $authenticator = $service->authenticators()->get('PrimaryKeySession');
    $result = $authenticator->stopImpersonating($this->getRequest(), $this->getResponse());
    $this->setRequest($result['request']);

    return $this->redirect('/');
}
```

## Constraints

- Calling `impersonate()` while already impersonating throws `Cake\Http\Exception\UnauthorizedException`; only one level deep.
- `cache => true` stores user data in CakePHP’s configured cache engine across requests. `SessionCache` defaults to the `default` cache; `TinyAuth.cache` overrides it. Delete a user’s cache entry after role or profile changes with `SessionCache::delete($userId)`. Logout deletes the active user’s entry.
- `SessionCache::clear()` clears the whole configured cache, or the configured groups when `TinyAuth.groups` is set. It does not clear only keys with the user-cache prefix.
- The authenticator only stores the primary key, not the full user data. If your `IdentifierInterface` is slow, expect the cost on every request unless `cache` is enabled.

## Authorization gate

Don't forget to gate the `impersonate` and `stopImpersonating` actions in your `auth_acl.ini`:

```ini
[Admin/Users]
impersonate = admin
stopImpersonating = *
```
