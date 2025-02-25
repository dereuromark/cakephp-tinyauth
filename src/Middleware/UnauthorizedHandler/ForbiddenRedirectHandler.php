<?php
declare(strict_types=1);

namespace TinyAuth\Middleware\UnauthorizedHandler;

use Authorization\Exception\Exception;
use Authorization\Exception\ForbiddenException;
use Authorization\Middleware\UnauthorizedHandler\RedirectHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This handler will redirect the response if one of configured exception classes is encountered.
 */
class ForbiddenRedirectHandler extends RedirectHandler {

	/**
	 * Default config:
	 *
	 *  - `exceptions` - A list of exception classes.
	 *  - `url` - URL to redirect to.
	 *  - `queryParam` - Query parameter name for the target URL.
	 *  - `statusCode` - Redirection status code.
	 *  - `unauthorizedMessage` - Error message. Set to false to disable.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaultOptions = [
		'exceptions' => [
			ForbiddenException::class,
		],
		'url' => '/',
		'queryParam' => null,
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

		$message = $options['unauthorizedMessage'] ?? __('You are not authorized to access that location.');
		if ($message) {
			/** @var \Cake\Http\ServerRequest $request */
			$request->getFlash()->error($message);
		}

		return $response;
	}

}
