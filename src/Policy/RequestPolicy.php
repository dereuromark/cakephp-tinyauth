<?php

namespace TinyAuth\Policy;

use ArrayAccess;
use Authorization\IdentityInterface;
use Authorization\Policy\RequestPolicyInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequest;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;
use TinyAuth\Utility\Config;

/**
 * Used for middleware approach.
 */
class RequestPolicy implements RequestPolicyInterface {

	use AclTrait;
	use AllowTrait;
	use InstanceConfigTrait;

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [];

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$config += Config::all();

		$this->setConfig($config);
	}

	/**
	 * Method to check if the request can be accessed
	 *
	 * @param \Authorization\IdentityInterface|null $identity Identity
	 * @param \Cake\Http\ServerRequest $request Request
	 * @return bool
	 */
	public function canAccess(?IdentityInterface $identity, ServerRequest $request): bool {
		$params = $request->getAttribute('params');
		$user = [];
		if ($identity) {
			$data = $identity->getOriginalData();
			$user = ($data instanceof ArrayAccess && method_exists($data, 'toArray')) ? $data->toArray() : (array)$data;
		}

		return $this->_checkUser($user, $params);
	}

}
