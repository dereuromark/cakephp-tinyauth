# Middleware & Policy

The component-based authorization documented in [Authorization Setup](/authorization/) covers most apps. For middleware-based authorization (or routes that bypass controllers entirely), TinyAuth ships its own middleware and policy.

## RequestAuthorizationMiddleware

A drop-in replacement for `Authorization\Middleware\RequestAuthorizationMiddleware` that applies the TinyAuth allow / ACL rules instead of policies. Works with any controller / route (no per-controller wiring needed).

> **Order matters:** add this AFTER `AuthorizationMiddleware`, `AuthenticationMiddleware`, and `RoutingMiddleware`.

### Wiring

```php
use TinyAuth\Middleware\RequestAuthorizationMiddleware;

// in Application::middleware()
$middlewareQueue
    ->add(new \Authentication\Middleware\AuthenticationMiddleware($this))
    ->add(new \Authorization\Middleware\AuthorizationMiddleware($this))
    ->add(new RequestAuthorizationMiddleware([
        'identityAttribute' => 'identity',
        'method' => 'access',
        'unauthorizedHandler' => 'TinyAuth.ForbiddenCakeRedirect',
    ]));
```

### Config keys

| Key | Default | What it does |
| --- | --- | --- |
| `identityAttribute` | inherits parent | Request attribute name that holds the authenticated identity. |
| `method` | inherits parent | Policy method invoked to check access (defaults to the parent middleware's). |
| `unauthorizedHandler` | inherits parent | Handler used when authorization fails. Use TinyAuth's two flavors below. |

The middleware picks up TinyAuth's full Configure block (`Configure::read('TinyAuth')`) automatically — no need to repeat keys here.

## RequestPolicy

Used internally by `RequestAuthorizationMiddleware` to evaluate the allow / ACL rules against the current request. You generally don't instantiate it directly, but if you wire `Authorization\Middleware\RequestAuthorizationMiddleware` yourself (instead of TinyAuth's subclass), point its `RequestAuthorizationMiddleware` at TinyAuth's policy:

```php
use Authorization\Policy\MapResolver;
use Cake\Http\ServerRequest;
use TinyAuth\Policy\RequestPolicy;

$resolver = new MapResolver();
$resolver->map(ServerRequest::class, RequestPolicy::class);
```

`RequestPolicy::canAccess(?IdentityInterface $identity, ServerRequest $request): bool` returns `true` if the current user (or guest) may access the route.

## Unauthorized handlers

When authorization fails, the middleware throws `Authorization\Exception\ForbiddenException`. TinyAuth ships two handlers for converting that into a redirect.

### `TinyAuth.ForbiddenRedirect`

Redirects to a fixed URL string. Useful for landing on a generic "/" or a public error page.

| Option | Default | Notes |
| --- | --- | --- |
| `exceptions` | `[ForbiddenException::class]` | Exception classes this handler responds to. |
| `url` | `/` | Target URL (string). |
| `queryParam` | `redirect` | Query parameter that captures the original URL (so you can come back after login). |
| `statusCode` | `302` | Redirect status. |
| `unauthorizedMessage` | localized "You are not authorized to access that location." | Flash message. Set to `false` to suppress. |

```php
'unauthorizedHandler' => [
    'className' => 'TinyAuth.ForbiddenRedirect',
    'url' => '/',
    'queryParam' => 'redirect',
    'unauthorizedMessage' => __('Please log in.'),
],
```

### `TinyAuth.ForbiddenCakeRedirect`

Redirects to a CakePHP URL array — typically the login action.

| Option | Default | Notes |
| --- | --- | --- |
| `exceptions` | `[ForbiddenException::class]` | |
| `url` | `['controller' => 'Users', 'action' => 'login']` | Cake URL array. |
| `queryParam` | `redirect` | |
| `statusCode` | `302` | |
| `unauthorizedMessage` | same default | |

```php
'unauthorizedHandler' => [
    'className' => 'TinyAuth.ForbiddenCakeRedirect',
    'url' => ['plugin' => null, 'prefix' => false, 'controller' => 'Users', 'action' => 'login'],
],
```

### Non-HTML requests

Both handlers re-throw the original `ForbiddenException` if the request has a non-HTML extension (`_ext` other than `'html'`). API consumers get a proper 403 instead of a 302 to a login page.

## When to use middleware vs component

| Use the **component** | Use the **middleware** |
| --- | --- |
| Standard MVC apps where every request hits a controller | Routes that bypass controllers (custom routing, JSON-RPC, etc.) |
| You want fine-grained per-controller config | You want a single global rule application |
| Easier to debug (per-controller `beforeFilter`) | Cleaner stack — auth runs before routing dispatches |

The two approaches are not mutually exclusive but typically you pick one. The component is the simpler choice; the middleware is the right choice when you need authorization to run regardless of the controller.
