<?php
declare(strict_types=1);

namespace TinyAuth\Middleware\UnauthorizedHandler;

use Authorization\Exception\Exception;
use Authorization\Exception\ForbiddenException;
use Authorization\Exception\MissingIdentityException;
use Authorization\Middleware\UnauthorizedHandler\RedirectHandler as CakeRedirectHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This handler will redirect the response if one of configured exception classes is encountered.
 */
class RedirectHandler extends CakeRedirectHandler {

	/**
	 * Default config:
	 *
	 *  - `exceptions` - A list of exception classes.
	 *  - `url` - Url to redirect to.
	 *  - `queryParam` - Query parameter name for the target url.
	 *  - `statusCode` - Redirection status code.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaultOptions = [
		'exceptions' => [
			MissingIdentityException::class,
			ForbiddenException::class,
		],
		'url' => '/login',
		'queryParam' => 'redirect',
		'statusCode' => 302,
		'unauthorizedMessage' => null,
	];

	/**
	 * @param \Authorization\Exception\Exception $exception
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @param array<string, mixed> $options
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function handle(Exception $exception, ServerRequestInterface $request, array $options = []): ResponseInterface {
		$response = parent::handle($exception, $request, $options);

		$message = $options['unauthorizedMessage'] ?? __('You are not authorized to access that location');
		if ($message) {
			/** @var \Cake\Http\ServerRequest $request */
			$request->getFlash()->error($message);
		}

		return $response;
	}

}
