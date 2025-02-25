<?php

namespace TinyAuth\Middleware;

use Authorization\Exception\Exception;
use Authorization\Exception\ForbiddenException;
use Authorization\Middleware\RequestAuthorizationMiddleware as PluginRequestAuthorizationMiddleware;
use Authorization\Policy\Result;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
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
	 * @param array<string, mixed> $config Configuration options
	 */
	public function __construct(array $config = []) {
		$config += Config::all();

		parent::__construct($config);
	}

	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @param \Psr\Http\Server\RequestHandlerInterface $handler
	 * @throws \Authorization\Exception\ForbiddenException
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$params = $request->getAttribute('params');
		$rule = $this->_getAllowRule($params);

		$service = $this->getServiceFromRequest($request);
		if ($this->_isActionAllowed($rule, $params['action'])) {
			$service->skipAuthorization();

			return $handler->handle($request);
		}

		$identity = $request->getAttribute($this->getConfig('identityAttribute'));

		$can = $service->can($identity, $this->getConfig('method'), $request);
		try {
			if (!$can) {
				$result = new Result($can, 'Can not ' . $this->getConfig('method') . ' request');

				throw new ForbiddenException($result, [$this->getConfig('method'), $request->getRequestTarget()]);
			}
		} catch (Exception $exception) {
			return $this->handleException($exception, $request, $this->getConfig('unauthorizedHandler'));
		}

		return $handler->handle($request);
	}

}
