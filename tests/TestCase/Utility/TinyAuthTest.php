<?php

namespace TinyAuth\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use TinyAuth\Utility\TinyAuth;

class TinyAuthTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.TinyAuth.DatabaseRoles',
	];

	/**
	 * @return void
	 */
	public function testGetAvailableRoles() {
		$config = [
			'rolesTable' => 'DatabaseRoles',
		];

		$result = (new TinyAuth($config))->getAvailableRoles();
		$expected = [
			'user' => 11,
			'moderator' => 12,
			'admin' => 13,
		];
		$this->assertEquals($expected, $result);
	}

}
