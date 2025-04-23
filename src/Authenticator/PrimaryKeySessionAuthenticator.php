<?php
declare(strict_types=1);

namespace TinyAuth\Authenticator;

use ArrayAccess;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\SessionAuthenticator as AuthenticationSessionAuthenticator;
use Authentication\Identifier\IdentifierInterface;
use Cake\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TinyAuth\Utility\SessionCache;

/**
 * Session Authenticator with only ID
 */
class PrimaryKeySessionAuthenticator extends AuthenticationSessionAuthenticator {

	/**
	 * @param \Authentication\Identifier\IdentifierInterface $identifier
	 * @param array<string, mixed> $config
	 */
	public function __construct(IdentifierInterface $identifier, array $config = []) {
		$config += [
			'identifierKey' => 'key',
			'idField' => 'id',
			'cache' => false, // `true` to activate caching layer
		];

		parent::__construct($identifier, $config);
	}

	/**
	 * Authenticate a user using session data.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request The request to authenticate with.
	 * @return \Authentication\Authenticator\ResultInterface
	 */
	public function authenticate(ServerRequestInterface $request): ResultInterface {
		$sessionKey = $this->getConfig('sessionKey');
		/** @var \Cake\Http\Session $session */
		$session = $request->getAttribute('session');

		$userId = $session->read($sessionKey);
		if (!$userId) {
			return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND);
		}

		if ($this->getConfig('cache')) {
			$user = SessionCache::read($userId);
			if ($user) {
				return new Result($user, Result::SUCCESS);
			}
		}

		$user = $this->_identifier->identify([$this->getConfig('identifierKey') => $userId]);
		if (!$user) {
			return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND);
		}

		if ($this->getConfig('cache')) {
			SessionCache::write($userId, $user);
		}

		return new Result($user, Result::SUCCESS);
	}

	/**
	 * @inheritDoc
	 */
	public function persistIdentity(ServerRequestInterface $request, ResponseInterface $response, $identity): array {
		$sessionKey = $this->getConfig('sessionKey');
		/** @var \Cake\Http\Session $session */
		$session = $request->getAttribute('session');

		if (!$session->check($sessionKey)) {
			$session->renew();
			$session->write($sessionKey, $identity[$this->getConfig('idField')]);
		}

		return [
			'request' => $request,
			'response' => $response,
		];
	}

	/**
	 * Impersonates a user
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request The request
	 * @param \Psr\Http\Message\ResponseInterface $response The response
	 * @param \ArrayAccess $impersonator User who impersonates
	 * @param \ArrayAccess $impersonated User impersonated
	 * @return array
	 */
	public function impersonate(
		ServerRequestInterface $request,
		ResponseInterface $response,
		ArrayAccess $impersonator,
		ArrayAccess $impersonated,
	): array {
		$sessionKey = $this->getConfig('sessionKey');
		$impersonateSessionKey = $this->getConfig('impersonateSessionKey');
		/** @var \Cake\Http\Session $session */
		$session = $request->getAttribute('session');
		if ($session->check($impersonateSessionKey)) {
			throw new UnauthorizedException(
				'You are impersonating a user already. ' .
				'Stop the current impersonation before impersonating another user.',
			);
		}
		$session->write($impersonateSessionKey, $impersonator[$this->getConfig('idField')]);
		$session->write($sessionKey, $impersonated[$this->getConfig('idField')]);
		$this->setConfig('identify', true);

		return [
			'request' => $request,
			'response' => $response,
		];
	}

}
