<?php
namespace TinyAuth\Utility;

use Cake\Core\InstanceConfigTrait;
use TinyAuth\Auth\AclTrait;

class TinyAuth {

	use AclTrait;
	use InstanceConfigTrait;

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
	];

	/**
	 * @param array $config
	 */
	public function __construct(array $config = []) {
		$config += Config::all();

		$this->setConfig($config);
	}

	/**
	 * @return int[]
	 */
	public function getAvailableRoles() {
		$roles = $this->_getAvailableRoles();

		return $roles;
	}

}
