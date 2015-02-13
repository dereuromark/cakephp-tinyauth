<?php
namespace TinyAuth\Test\Auth;

use TinyAuth\Auth\TinyAuthorize;
use Cake\TestSuite\TestCase;
use Cake\Controller\Controller;
use Cake\Controller\ComponentRegistry;
use Cake\Network\Request;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Test case for DirectAuthentication
 *
 */
class TinyAuthorizeTest extends TestCase {

	public $fixtures = ['core.users', 'core.auth_users', 'plugin.tiny_auth.roles'];

	public $Collection;

	public $request;

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		//$config = \Cake\Datasource\ConnectionManager::config('test');
		//$this->assertNotEmpty($config, 'No test connection set up.');

		$this->Collection = new ComponentRegistry();

		$this->request = new Request();

		$aclData = <<<INI
[Users]
; add = public
;edit = user
;* = admin
[users]
index = user, non-configured-role
edit,view = user
* = admin
[admin/Users]
* = admin
[Comments]
; index is public
add,edit,delete = user
* = admin
[Tags]
add = *
very_long_action_name_action = user
public_action = public
INI;
		file_put_contents(TMP . 'acl.ini', $aclData);
		$this->assertTrue(file_exists(TMP . 'acl.ini'));

		Configure::write('Roles', [
			'user' => 1,
			'moderator' => 2,
			'admin' => 3,
			'public' => -1
		]);
	}

	public function tearDown() {
		unlink(TMP . 'acl.ini');

		parent::tearDown();
	}

	/**
	 * Test applying config in the constructor
	 *
	 * @return void
	 */
	public function testConstructor() {
		$object = new TestTinyAuthorize($this->Collection, [
			'aclTable' => 'AuthRole',
			'aclKey' => 'auth_role_id',
			'autoClearCache' => true,
		]);
		$this->assertEquals('AuthRole', $object->config('aclTable'));
		$this->assertEquals('auth_role_id', $object->config('aclKey'));
	}

	/**
	 * @return void
	 */
	public function testGetAcl() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);
		$res = $object->getAcl();

		$expected = [
			'Users' => [
				'plugin' => null,
				'prefix' => null,
				'controller' => 'Users',
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'view' => [1],
					'*' => [3]
				]
			],
			'admin/Users' => [
				'plugin' => null,
				'prefix' => 'admin',
				'controller' => 'Users',
				'actions' => [
					'*' => [3]
				]
			],
			'Comments' => [
				'plugin' => null,
				'prefix' => null,
				'controller' => 'Comments',
				'actions' => [
					'add' => [1],
					'edit' => [1],
					'delete' => [1],
					'*' => [3]
				]
			],
			'Tags' => [
				'plugin' => null,
				'prefix' => null,
				'controller' => 'Tags',
				'actions' => [
					'add' => [1, 2, 3, -1],
					'very_long_action_name_action' => [1],
					'public_action' => [-1]
				]
			]
		];
		$this->assertEquals($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodDisallowed() {
		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'add';

		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);
		$this->assertEquals('Roles', $object->config('aclTable'));
		$this->assertEquals('role_id', $object->config('aclKey'));

		$user = [
			'role_id' => 4
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = [
			'role_id' => 1
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowed() {
		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'edit';

		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// single role_id field in users table
		$user = [
			'role_id' => 1,
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$this->request->params['action'] = 'admin_index';

		$user = [
			'role_id' => 3,
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedWithLongActionNames() {
		$this->request->params['controller'] = 'tags';
		$this->request->params['action'] = 'very_long_action_name_action';

		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// single role_id field in users table
		$user = [
			'role_id' => 1
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = [
			'role_id' => 3
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedMultiRole() {
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'admin_index';

		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// flat list of roles
		$user = [
			'Roles' => [1, 3]
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// verbose role defition using the new 2.x contain param for Auth
		$user = [
			'Roles' => [
				['id' => 1, 'RoleUsers' => []],
				['id' => 3, 'RoleUsers' => []]
			],
		];


		$user = [
			'Roles' => [
				['id' => 1, 'RoleUsers' => []],
				['id' => 3, 'RoleUsers' => []]
			]
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedWildcard() {
		$this->request->params['controller'] = 'Tags';
		$this->request->params['action'] = 'public_action';

		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		$user = [
			'role_id' => 6
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * @return void
	 */
	public function testUserMethodsAllowed() {
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'some_action';

		$object = new TestTinyAuthorize($this->Collection, ['allowUser' => true, 'autoClearCache' => true]);

		$user = [
			'role_id' => 1
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$this->request->params['controller'] = 'Users';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['action'] = 'index';

		$object = new TestTinyAuthorize($this->Collection, ['allowUser' => true, 'autoClearCache' => true]);

		$user = [
			'role_id' => 1
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = [
			'role_id' => 3
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * @return void
	 */
	public function testAdminMethodsAllowed() {
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'some_action';
		$config = ['allowAdmin' => true, 'adminRole' => 3, 'autoClearCache' => true];

		$object = new TestTinyAuthorize($this->Collection, $config);

		$user = [
			'role_id' => 1
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'admin_index';

		$object = new TestTinyAuthorize($this->Collection, $config);

		$user = [
			'role_id' => 1
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = [
			'role_id' => 3
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Should only be used in combination with Auth->allow() to mark those as public in the acl.ini, as well.
	 * Not necessary and certainly not recommended as acl.ini only.
	 *
	 * @return void
	 */
	public function testBasicUserMethodAllowedPublically() {
		$this->request->params['controller'] = 'tags';
		$this->request->params['action'] = 'add';

		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		$user = [
			'role_id' => 2
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$this->request->params['controller'] = 'comments';
		$this->request->params['action'] = 'foo';

		$user = [
			'role_id' => 3
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * TinyAuthorizeTest::testWithRoleTable()
	 *
	 * @return void
	 */
	public function testWithRoleTable() {
		$Users = TableRegistry::get('Users');
		$Users->belongsTo('Roles');

		// We want the session to be used.
		Configure::delete('Roles');

		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'edit';

		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// User role is 4 here, though. Also contains left joined Role date here just to check that it works, too.
		$user = [
			'Roles' => [
				'id' => '4',
				'alias' => 'user'
			],
			'role_id' => 4,
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		Configure::delete('Roles');
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		$user = [
			'role_id' => 6
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$this->assertTrue((bool)(Configure::read('Roles')));

		// Multi-role test - failure
		Configure::delete('Roles');
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		$user = [
			'Roles' => [
				['id' => 7, 'alias' => 'user'],
				['id' => 8, 'alias' => 'partner']
			]
		];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$this->assertTrue((bool)(Configure::read('Roles')));

		Configure::delete('Roles');
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// Multi-role test
		$user = [
			'Roles' => [
				['id' => 4, 'alias' => 'user'],
				['id' => 6, 'alias' => 'partner'],
			]
		];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Tests superAdmin role, allowed to all actions
	 *
	 * @return void
	 */
	public function testSuperAdminRole() {
		$object = new TestTinyAuthorize($this->Collection, [
			'autoClearCache' => true,
			'superAdminRole' => 9
		]);
		$res = $object->getAcl();
		$user = [
			'role_id' => 9
		];

		foreach ($object->getAcl() as $resource) {
			foreach ($resource['actions'] as $action => $allowed) {
				$this->request->params['controller'] = $resource['controller'];
				$this->request->params['prefix'] = $resource['prefix'];
				$this->request->params['action'] = $action;
				$res = $object->authorize($user, $this->request);
				$this->assertTrue($res);
			}
		}
	}

}

class TestTinyAuthorize extends TinyAuthorize {

	public function matchArray() {
		return $this->_matchArray;
	}

	public function getAcl() {
		return $this->_getAcl();
	}

	protected function _getAcl($path = TMP) {
		return parent::_getAcl($path);
	}

	/**
	 * @return Cake\ORM\Table The User table
	 */
	public function getTable() {
		$Users = TableRegistry::get(CLASS_USER);
		$Users->belongsTo('Roles');

		return $Users;
	}

}
