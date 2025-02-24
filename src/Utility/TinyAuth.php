<?php

namespace TinyAuth\Utility;

use Cake\Core\InstanceConfigTrait;
use TinyAuth\Auth\AclTrait;
use TinyAuth\Auth\AllowTrait;

class TinyAuth {

	use AclTrait;
	use AllowTrait;
	use InstanceConfigTrait;

	/**
	 * @var array
	 */
	protected array $_defaultConfig = [
	];

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$config += Config::all();

		$this->setConfig($config);
	}

	/**
	 * @return array<int>
	 */
	public function getAvailableRoles() {
		$roles = $this->_getAvailableRoles();

		return $roles;
	}

}
