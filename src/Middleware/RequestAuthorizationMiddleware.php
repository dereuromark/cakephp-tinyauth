<?php

namespace TinyAuth\Middleware;

use Authorization\Exception\ForbiddenException;
use Authorization\Middleware\RequestAuthorizationMiddleware as PluginRequestAuthorizationMiddleware;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;
use TinyAuth\Utility\Config;

/**
 * Request Authorization Middleware
 *
 * This MUST be added after the Authorization, Authentication and
 * RoutingMiddleware in the Middleware Queue!
 *
 * This middleware is useful when you want to authorize your requests, for example
 * each controller and action, against a role based access system or any other
 * kind of authorization process that controls access to certain actions.
 */
class RequestAuthorizationMiddleware extends PluginRequestAuthorizationMiddleware {

	use AclTrait;
	use AllowTrait;

	/**
	 * @param array $config Configuration options
	 */
	public function __construct($config = []) {
		$config += Config::all();

		parent::__construct($config);
	}

	/**
	 * Callable implementation for the middleware stack.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Server request.
	 * @param \Psr\Http\Message\ResponseInterface $response Response.
	 * @param callable $next The next middleware to call.
	 * @throws \Authorization\Exception\ForbiddenException
	 * @return \Psr\Http\Message\ResponseInterface A response.
	 */
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next) {
		$params = $request->getAttribute('params');
		$rule = $this->_getAllowRule($params);

		$service = $this->getServiceFromRequest($request);
		if ($this->_isActionAllowed($rule, $params['action'])) {
			$service->skipAuthorization();

			return $next($request, $response);
		}

		$identity = $request->getAttribute($this->getConfig('identityAttribute'));

		$can = $service->can($identity, $this->getConfig('method'), $request);
		if (!$can) {
			$result = new Result($can, 'Can not ' . $this->getConfig('method') . ' request');
			throw new ForbiddenException($result);
		}

		return $next($request, $response);
	}

}
