# Middleware & Policy

The component-based authorization documented in [Authorization Setup](/authorization/) covers most apps. For authorization before controller dispatch, TinyAuth ships its own middleware and policy. Both expect routing parameters containing `controller` and `action`. For endpoints without those parameters, use the official request authorization middleware with a policy suited to that endpoint.

## RequestAuthorizationMiddleware

TinyAuth checks its public-route rules first. Protected routes are authorized through the configured Authorization service. Map `ServerRequest` to TinyAuth’s `RequestPolicy` in that service to apply the role ACL rules. The middleware does not install this mapping automatically.

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
| `identityAttribute` | `identity` | Request attribute name that holds the authenticated identity. |
| `method` | `access` | Policy method invoked to check access. |
| `unauthorizedHandler` | `Authorization.Exception` | Handler used when authorization fails. Use TinyAuth's two flavors below. |

The middleware reads the TinyAuth configuration from `Configure::read('TinyAuth')`. Explicit middleware options take precedence.

## RequestPolicy

Register this policy in `Application::getAuthorizationService()`. Both TinyAuth’s middleware and the official request authorization middleware need a resolver mapping for the request:

```php
use Authorization\AuthorizationService;
use Authorization\Policy\MapResolver;
use Cake\Http\ServerRequest;
use TinyAuth\Policy\RequestPolicy;

$resolver = new MapResolver();
$resolver->map(ServerRequest::class, RequestPolicy::class);

return new AuthorizationService($resolver);
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

Both handlers re-throw the original `ForbiddenException` if the request has a non-HTML extension (`_ext` other than `'html'`). An extension such as `.json` therefore preserves the forbidden response. Requests without an extension still use the configured redirect handler, regardless of their `Accept` header.

## When to use middleware vs component

| Use the **component** | Use the **middleware** |
| --- | --- |
| Standard MVC apps where every request hits a controller | Routed controller actions checked before dispatch |
| You want fine-grained per-controller config | You want a single global rule application |
| Easier to debug (per-controller `beforeFilter`) | Authorization runs after routing, before controller dispatch |

Choose one place to enforce request access. With the middleware approach, you do not need the TinyAuth Authorization component to repeat the same request check. Entity policies can still enforce permissions on individual records.
