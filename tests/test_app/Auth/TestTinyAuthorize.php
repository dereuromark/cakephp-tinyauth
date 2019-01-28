<?php
namespace TestApp\Auth;

use Cake\Core\Plugin;
use Cake\ORM\TableRegistry;
use TinyAuth\Auth\TinyAuthorize;

class TestTinyAuthorize extends TinyAuthorize {

	/**
	 * @return null|\TinyAuth\Auth\AclAdapter\AclAdapterInterface
	 */
	public function getAclAdapter() {
		return $this->_aclAdapter;
	}

	/**
	 * @return array
	 */
	public function getAcl() {
		return $this->_getAcl();
	}

	/**
	 * @param string|null $path
	 *
	 * @return array
	 */
	protected function _getAcl($path = null) {
		$path = Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS;

		return parent::_getAcl($path);
	}

	/**
	 * @return \Cake\ORM\Table The User table
	 */
	public function getTable() {
		$Users = TableRegistry::get($this->getConfig('usersTable'));
		$Users->belongsTo('Roles');

		return $Users;
	}

}
