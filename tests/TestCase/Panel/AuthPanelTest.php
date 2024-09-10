<?php

namespace TinyAuth\Test\TestCase\Panel;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TinyAuth\Panel\AuthPanel;
use TinyAuth\Utility\Config;

class AuthPanelTest extends TestCase {

	/**
	 * @var \TinyAuth\Panel\AuthPanel
	 */
	protected $panel;

	/**
	 * @var array
	 */
	protected array $config = [];
	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Config::drop();

		Configure::write('Roles', [
			'user' => 1,
			'moderator' => 2,
			'admin' => 3,
		]);
		$config = [
			'allowFilePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'aclFilePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'autoClearCache' => true,
		];
		Configure::write('TinyAuth', $config);

		$this->panel = new AuthPanel();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		Configure::delete('TinyAuth');
	}

	/**
	 * @return void
	 */
	public function testPanelRestrictedAction() {
		$controller = new Controller(new ServerRequest());
		$controller->loadComponent('TinyAuth.AuthUser');
		$event = new Event('event', $controller);

		$this->panel->shutdown($event);

		$result = $this->panel->data();
		$summary = $this->panel->summary();

		//$this->assertSame($expected, $result);
		$this->assertSame(AuthPanel::ICON_RESTRICTED, $summary);
	}

	/**
	 * @return void
	 */
	public function testPanelPublicAction() {
		$url = [
			'plugin' => null,
			'prefix' => null,
			'controller' => 'Users',
			'action' => 'index',
		];

		$request = new ServerRequest(['url' => '/users']);
		$request = $request->withAttribute('params', $url);
		$controller = new Controller($request);
		$controller->loadComponent('TinyAuth.AuthUser');
		$event = new Event('event', $controller);

		$this->panel->shutdown($event);

		$result = $this->panel->data();
		$summary = $this->panel->summary();

		$this->assertNotEmpty($result['params']);
		$this->assertNotEmpty($result['availableRoles']);
		$this->assertTrue($result['isPublic']);

		//$this->assertSame($expected, $result);
		$this->assertSame(AuthPanel::ICON_PUBLIC, $summary);
	}

	/**
	 * @return void
	 */
	public function testPanelAclRestricted() {
		$url = [
			'plugin' => 'Tags',
			'prefix' => null,
			'controller' => 'Tags',
			'action' => 'index',
		];

		$request = new ServerRequest(['url' => '/tags']);
		$request = $request->withAttribute('params', $url);
		$controller = new Controller($request);
		$controller->loadComponent('TinyAuth.AuthUser');
		$event = new Event('event', $controller);

		$this->panel->shutdown($event);

		$result = $this->panel->data();
		$summary = $this->panel->summary();

		$this->assertNotEmpty($result['params']);
		$this->assertNotEmpty($result['availableRoles']);
		$this->assertFalse($result['isPublic']);

		$this->assertSame(AuthPanel::ICON_RESTRICTED, $summary);
	}

	/**
	 * @return void
	 */
	public function testPanelAclAllowed() {
		$url = [
			'plugin' => 'Tags',
			'prefix' => null,
			'controller' => 'Tags',
			'action' => 'index',
		];

		$request = new ServerRequest(['url' => '/tags']);
		$request = $request->withAttribute('params', $url);
		$request->getSession()->write('Auth.User', [
			'id' => 1,
			'role_id' => 1,
		]);
		$controller = new Controller($request);
		$controller->loadComponent('TinyAuth.AuthUser');
		$event = new Event('event', $controller);

		$this->panel->shutdown($event);

		$result = $this->panel->data();

		$this->assertNotEmpty($result['params']);
		$this->assertNotEmpty($result['availableRoles']);
		$this->assertFalse($result['isPublic']);

		$this->assertNotEmpty($result['user']);
		$this->assertSame(['user' => 1], $result['roles']);
		$this->assertTrue($result['access']['user']);
		$this->assertFalse($result['access']['moderator']);
		$this->assertFalse($result['access']['admin']);

		$this->assertSame('Tags.Tags', $result['path']);
	}

}
