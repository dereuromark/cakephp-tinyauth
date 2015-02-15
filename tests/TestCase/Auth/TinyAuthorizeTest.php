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
; ----------------------------------------------------------
; Userscontroller (no prefixed route, no plugin)
; ----------------------------------------------------------
[Users]
index = user, undefined-role
edit = user
delete = admin
very_long_action_name_action = user
public_action = public
; ----------------------------------------------------------
; UsersController (/admin prefixed route, no plugin)
; ----------------------------------------------------------
[admin/Users]
index = user, undefined-role
edit = user
delete = admin
public_action = public
very_long_action_name_action = user
; ----------------------------------------------------------
; CommentsController (no prefixed route, no plugin)
; ----------------------------------------------------------
[Comments]
index = user, undefined-role
edit,view = user
* = admin
public_action = public
very_long_action_name_action = user
; ----------------------------------------------------------
; TagsController (no prefixed route, no plugin)
; ----------------------------------------------------------
[Tags]
* = *
; ----------------------------------------------------------
; TagsController (plugin Tags, no prefixed route)
; ----------------------------------------------------------
[Tags.Tags]
index = user
edit,view = user
* = admin
public_action = public
very_long_action_name_action = user
; ----------------------------------------------------------
; TagsController (plugin Tags, /admin prefixed route)
; ----------------------------------------------------------
[Tags.admin/Tags]
index = user
edit,view = user
* = admin
public_action = public
very_long_action_name_action = user
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
					'delete' => [3],
					'public_action' => [-1],
					'very_long_action_name_action' => [1]
				]
			],
			'admin/Users' => [
				'plugin' => null,
				'prefix' => 'admin',
				'controller' => 'Users',
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'delete' => [3],
					'public_action' => [-1],
					'very_long_action_name_action' => [1]
				]
			],
			'Comments' => [
				'plugin' => null,
				'prefix' => null,
				'controller' => 'Comments',
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'view' => [1],
					'*' => [3],
					'public_action' => [-1],
					'very_long_action_name_action' => [1]
				]
			],
			'Tags' => [
				'plugin' => null,
				'prefix' => null,
				'controller' => 'Tags',
				'actions' => [
					'*' => [1, 2, 3, -1]
				]
			],
			'Tags.Tags' => [
				'plugin' => 'Tags',
				'prefix' => null,
				'controller' => 'Tags',
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'view' => [1],
					'*' => [3],
					'public_action' => [-1],
					'very_long_action_name_action' => [1]
				]
			],
			'Tags.admin/Tags' => [
				'plugin' => 'Tags',
				'prefix' => 'admin',
				'controller' => 'Tags',
				'actions' => [
					'index' => [1],
					'edit' => [1],
					'view' => [1],
					'*' => [3],
					'public_action' => [-1],
					'very_long_action_name_action' => [1]
				]
			]
		];
		//debug($res);
		$this->assertEquals($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodDisallowed() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);
		$this->assertEquals('Roles', $object->config('aclTable'));
		$this->assertEquals('role_id', $object->config('aclKey'));

		// test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'add';
		$user = ['role_id' => 4]; // invalid non-existing role
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 1]; // valid role without authorization
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test standard controller with /admin prefix
		$this->request->params['prefix'] = 'admin';

		$user = ['role_id' => 4];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test plugin controller without prefix
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['controller'] = 'Tags';
		$this->request->params['action'] = 'add';
		$this->request->params['prefix'] = null;

		$user = ['role_id' => 4];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test plugin controller with /admin prefix
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['controller'] = 'Tags';
		$this->request->params['action'] = 'add';

		$user = ['role_id' => 4];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowed() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'edit';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$this->request->params['action'] = 'delete';
		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// test standard controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$this->request->params['action'] = 'edit';

		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$this->request->params['action'] = 'delete';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// test plugin controller without prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['action'] = 'index';
		$this->request->params['prefix'] = null;

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$this->request->params['action'] = 'delete';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// test plugin controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$this->request->params['action'] = 'index';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 3];
		$this->request->params['action'] = 'delete';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Tests using incorrect casing, enforces strict acl.ini definitions.
	 *
	 * @return void
	 */
	public function testCaseSensitivity() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// test incorrect controller casing
		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'index';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test incorrect controller casing with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test correct controller casing with incorrect prefix casing
		$this->request->params['controller'] = 'Users';
		$this->request->params['prefix'] = 'Admin';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test incorrect plugin controller without prefix
		$this->request->params['controller'] = 'tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = null;
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test correct plugin controller with incorrect plugin casing
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'tags';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test correct plugin controller with correct plugin but incorrect prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = 'Admin';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedWithLongActionNames() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'very_long_action_name_action';

		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test standard controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test plugin controller without prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = null;
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		// test plugin controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = [ 'role_id' => 1 ];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedMultiRole() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'delete';

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
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);
		$user = ['role_id' => 6];

		// test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'public_action';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// test standard controller with /admin prefiex
		$this->request->params['prefix'] = 'admin';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// test plugin controller without prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = null;
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// test plugin controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Tests with configuration setting 'allowUser' set to true, giving user
	 * access to all controller/actions except when prefixed with /admin
	 *
	 * @todo: discuss logic before completing, see code L120
	 *
	 * @return void
	 */
	public function testUserMethodsAllowed() {
		$object = new TestTinyAuthorize($this->Collection, ['allowUser' => true, 'autoClearCache' => true]);

		// test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'unknown_action';
		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		//$this->assertFalse($res);
		$this->assertTrue($res);		// @todo: this now asserts true, might need to be changed depending on logic

		// Test standard controller with /admin prefix
		$this->request->params['prefix'] = 'admin';
		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * Test with enabled configuration settings 'allowAdmin' and 'adminRole'
	 * giving users having the adminRole ID access to all actions that are
	 * prefixed using the 'adminPrefix' configuration setting.
	 *
	 * @return void
	 */
	public function testAdminMethodsAllowed() {
		$config = [
			'allowAdmin' => true,
			'adminRole' => 3,
			'adminPrefix' => 'admin',
			'autoClearCache' => true
		];
		$object = new TestTinyAuthorize($this->Collection, $config);

		// test standard controller with /admin prefix
		$this->request->params['controller'] = 'Users';
		$this->request->params['prefix'] = 'admin';
		$this->request->params['action'] = 'some_action';
		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// test plugin controller with /admin prefix
		$this->request->params['controller'] = 'Tags';
		$this->request->params['plugin'] = 'Tags';
		$this->request->params['prefix'] = null;
		$user = ['role_id' => 1];
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = ['role_id' => 3];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Should only be used in combination with Auth->allow() to mark those
	 * as public in the acl.ini, as well. Not necessary and certainly not
	 * recommended as acl.ini only.
	 *
	 * @todo: discuss, what is this??
	 *
	 * @return void
	 */
	public function testBasicUserMethodAllowedPublically() {
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// test standard controller
		$this->request->params['controller'] = 'Tags';
		$this->request->params['action'] = 'add';
		$user = ['role_id' => 2];
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$this->request->params['controller'] = 'Comments';
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
		$object = new TestTinyAuthorize($this->Collection, ['autoClearCache' => true]);

		// test standard controller
		$this->request->params['controller'] = 'Users';
		$this->request->params['action'] = 'edit';

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
