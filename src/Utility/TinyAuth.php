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
		$config = $this->_prepareConfig($config);

		$this->setConfig($config);
	}

	/**
	 * @return string[]
	 */
	public function getAvailableRoles() {
		$roles = $this->_getAvailableRoles();

		return $roles;
	}

}
