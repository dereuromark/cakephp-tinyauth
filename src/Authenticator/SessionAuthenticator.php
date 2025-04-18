<?php
declare(strict_types=1);

namespace TinyAuth\Authenticator;

use ArrayAccess;
use ArrayObject;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\SessionAuthenticator as AuthenticationSessionAuthenticator;
use Authentication\Identifier\IdentifierInterface;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\UnauthorizedException;
use Cake\ORM\TableRegistry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Session Authenticator with only ID
 */
class SessionAuthenticator extends AuthenticationSessionAuthenticator {

	/**
	 * @param \Authentication\Identifier\IdentifierInterface $identifier
	 * @param array<string, mixed> $config
	 */
	public function __construct(IdentifierInterface $identifier, array $config = []) {
		$config += [
			'modelClass' => 'Users',
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
		$user = $session->read($sessionKey);

		if (!$user) {
			return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND);
		}

		if ($this->getConfig('identify') === true) {
			$credentials = [];
			foreach ($this->getConfig('fields') as $key => $field) {
				$credentials[$key] = $user[$field];
			}
			$user = $this->_identifier->identify($credentials);

			if (!$user) {
				return new Result(null, Result::FAILURE_CREDENTIALS_INVALID);
			}
		} else {
			$user = $this->hydrateFromSession($user);
		}

		if (!($user instanceof ArrayAccess)) {
			$user = new ArrayObject($user);
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
			$session->write($sessionKey, $identity['id']);
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
		$session->write($impersonateSessionKey, $impersonator['id']);
		$session->write($sessionKey, $impersonated['id']);
		$this->setConfig('identify', true);

		return [
			'request' => $request,
			'response' => $response,
		];
	}

	/**
	 * Stops impersonation
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request The request
	 * @param \Psr\Http\Message\ResponseInterface $response The response
	 * @return array
	 */
	public function stopImpersonating(ServerRequestInterface $request, ResponseInterface $response): array {
		$sessionKey = $this->getConfig('sessionKey');
		$impersonateSessionKey = $this->getConfig('impersonateSessionKey');
		/** @var \Cake\Http\Session $session */
		$session = $request->getAttribute('session');
		if ($session->check($impersonateSessionKey)) {
			$id = $session->read($impersonateSessionKey);
			$session->delete($impersonateSessionKey);
			$session->write($sessionKey, $id);
			$this->setConfig('identify', true);
		}

		return [
			'request' => $request,
			'response' => $response,
		];
	}

	/**
	 * Returns true if impersonation is being done
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request The request
	 * @return bool
	 */
	public function isImpersonating(ServerRequestInterface $request): bool {
		$impersonateSessionKey = $this->getConfig('impersonateSessionKey');
		/** @var \Cake\Http\Session $session */
		$session = $request->getAttribute('session');

		return $session->check($impersonateSessionKey);
	}

	/**
	 * @param string|int $user
	 * @return \Cake\Datasource\EntityInterface
	 */
	protected function hydrateFromSession(string|int $user): EntityInterface {
		return TableRegistry::getTableLocator()->get($this->getConfig('modelClass'))->get($user);
	}

}
