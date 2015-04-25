<?php
namespace TinyAuth\Test\Auth;

use Cake\TestSuite\TestCase;
use TinyAuth\Auth\Auth;

/**
 */
class AuthTest extends TestCase {

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * AuthTest::testHasRole()
	 *
	 * @return void
	 */
	public function testHasRole() {
		$res = Auth::hasRole(1, [2, 3, 6]);
		$this->assertFalse($res);

		$res = Auth::hasRole(3, [2, 3, 6]);
		$this->assertTrue($res);

		$res = Auth::hasRole(3, 1);
		$this->assertFalse($res);

		$res = Auth::hasRole(3, '3');
		$this->assertTrue($res);

		$res = Auth::hasRole(3, '');
		$this->assertFalse($res);
	}

	/**
	 * AuthTest::testHasRoleWithSession()
	 *
	 * @return void
	 */
	public function testHasRoleWithSession() {
		if (!defined('USER_ROLE_KEY')) {
			define('USER_ROLE_KEY', 'Role');
		}

		$roles = [
			['id' => '1', 'name' => 'User', 'alias' => 'user'],
			['id' => '2', 'name' => 'Moderator', 'alias' => 'moderator'],
			['id' => '3', 'name' => 'Admin', 'alias' => 'admin'],
		];

		$res = Auth::hasRole(4, $roles);
		$this->assertFalse($res);

		$res = Auth::hasRole(3, $roles);
		$this->assertTrue($res);
	}

	/**
	 * AuthTest::testHasRoles()
	 *
	 * @return void
	 */
	public function testHasRoles() {
		$res = Auth::hasRoles([1, 3], [2, 3, 6]);
		$this->assertTrue($res);

		$res = Auth::hasRoles([3], [2, 3, 6]);
		$this->assertTrue($res);

		$res = Auth::hasRoles(3, [2, 3, 6]);
		$this->assertTrue($res);

		$res = Auth::hasRoles([], [2, 3, 6]);
		$this->assertFalse($res);

		$res = Auth::hasRoles(null, [2, 3, 6]);
		$this->assertFalse($res);

		$res = Auth::hasRoles([2, 7], [2, 3, 6], false);
		$this->assertFalse($res);

		$res = Auth::hasRoles([2, 6], [2, 3, 6], false);
		$this->assertTrue($res);

		$res = Auth::hasRoles([2, 6], [2, 3, 6]);
		$this->assertTrue($res);

		$res = Auth::hasRoles([9, 11], []);
		$this->assertFalse($res);

		$res = Auth::hasRoles([9, 11], '');
		$this->assertFalse($res);

		$res = Auth::hasRoles([2, 7], [], false);
		$this->assertFalse($res);

		$res = Auth::hasRoles([2, 7], [], false);
		$this->assertFalse($res);
	}

}
