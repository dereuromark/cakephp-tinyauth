<?php

namespace TinyAuth\Test\TestCase\Controller\Component;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use TinyAuth\View\Helper\AuthUserHelper;

/**
 * TinyAuth AuthComponent to handle all authentication in a central ini file.
 */
class AuthUserHelperTest extends TestCase {

	/**
	 * @var \TinyAuth\View\Helper\AuthUserHelper
	 */
	protected $AuthUserHelper;

	/**
	 * @var \Cake\View\View
	 */
	protected $View;

	/**
	 * @var array
	 */
	protected $config = [];
	/**
	 * @return void
	 */
	public function setUp() {
		Configure::write('Roles', [
			'user' => 1,
			'moderator' => 2,
			'admin' => 3
		]);
		$this->config = [
			'filePath' => Plugin::path('TinyAuth') . 'tests' . DS . 'test_files' . DS,
			'autoClearCache' => true,
		];
		$this->View = new View();
		$this->AuthUserHelper = new AuthUserHelper($this->View, $this->config);
	}

	/**
	 * @return void
	 */
	public function testIsAuthorizedValid() {
		$user = [
			'id' => 1,
			'role_id' => 1
		];
		$this->View->set('_authUser', $user);

		$request = [
			'controller' => 'Tags',
			'action' => 'edit',
		];
		$result = $this->AuthUserHelper->hasAccess($request);
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 */
	public function testIsAuthorizedInvalid() {
		$user = [
			'id' => 1,
			'role_id' => 1
		];
		$this->View->set('_authUser', $user);

		$request = [
			'controller' => 'Tags',
			'action' => 'delete',
		];
		$result = $this->AuthUserHelper->hasAccess($request);
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testIsAuthorizedNotLoggedIn() {
		$user = [
		];
		$this->View->set('_authUser', $user);

		$request = [
			'controller' => 'Tags',
			'action' => 'edit',
		];
		$result = $this->AuthUserHelper->hasAccess($request);
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testLinkNotLoggedIn() {
		$user = [
		];
		$this->View->set('_authUser', $user);

		$url = [
			'controller' => 'Tags',
			'action' => 'edit',
		];
		$result = $this->AuthUserHelper->link('Edit', $url);
		$this->assertSame('', $result);
	}

	/**
	 * @return void
	 */
	public function testLinkValid() {
		$user = [
			'id' => 1,
			'role_id' => 1
		];
		$this->View->set('_authUser', $user);

		$url = [
			'controller' => 'Tags',
			'action' => 'edit',
		];
		$result = $this->AuthUserHelper->link('Edit', $url);
		$this->assertSame('<a href="/tags/edit">Edit</a>', $result);
	}

	/**
	 * @return void
	 */
	public function testLinkInvalidDefault() {
		$user = [
			'id' => 1,
			'role_id' => 1
		];
		$this->View->set('_authUser', $user);

		$url = [
			'controller' => 'Tags',
			'action' => 'delete',
		];
		$result = $this->AuthUserHelper->link('Edit', $url, ['default' => true]);
		$this->assertSame('Edit', $result);
	}

	/**
	 * @return void
	 */
	public function testPostLinkValid() {
		$user = [
			'id' => 1,
			'role_id' => 3
		];
		$this->View->set('_authUser', $user);

		$url = [
			'controller' => 'Tags',
			'action' => 'delete',
		];
		$result = $this->AuthUserHelper->postLink('Delete <b>Me</b>', $url);
		$this->assertContains('<form name=', $result);
		$this->assertContains('>Delete &lt;b&gt;Me&lt;/b&gt;</a>', $result);
	}

	/**
	 * @return void
	 */
	public function testPostLinkInvalid() {
		$user = [
			'id' => 1,
			'role_id' => 1
		];
		$this->View->set('_authUser', $user);

		$url = [
			'controller' => 'Tags',
			'action' => 'delete',
		];
		$result = $this->AuthUserHelper->postLink('Delete <b>Me</b>', $url, ['default' => 'Foo']);
		$this->assertSame('Foo', $result);
	}

	/**
	 * @return void
	 */
	public function testPostLinkValidNoEscape() {
		$user = [
			'id' => 1,
			'role_id' => 3
		];
		$this->View->set('_authUser', $user);

		$url = [
			'controller' => 'Tags',
			'action' => 'delete',
		];
		$result = $this->AuthUserHelper->postLink('Delete <b>Me</b>', $url, ['escape' => false]);
		$this->assertContains('<form name=', $result);
		$this->assertContains('>Delete <b>Me</b></a>', $result);
	}

	/**
	 * ACL links are basically free, they are as fast as normal links.
	 *
	 * @return void
	 */
	public function testLinkSpeed() {
		$this->skipIf(env('COVERALLS'));

		$user = [
			'id' => 1,
			'role_id' => 1
		];
		$this->View->set('_authUser', $user);

		$url = [
			'controller' => 'Tags',
			'action' => 'edit',
		];

		$before = microtime(true);

		for ($i = 0; $i < 1000; $i++) {
			$this->AuthUserHelper->link('Foo', $url);
		}

		$after = microtime(true);
		$diff = round($after - $before, 4);
		$this->assertWithinRange(0.3, $diff, 0.3, '1000 iterations should be as fast as around 0.1-0.2 sek, but are ' . number_format($diff, 2) . 'sek.');
	}

	/**
	 * @return void
	 */
	public function testIsMe() {
		$user = ['id' => '1'];
		$this->View->set('_authUser', $user);

		$this->assertFalse($this->AuthUserHelper->isMe(0));
		$this->assertTrue($this->AuthUserHelper->isMe(1));
	}

	/**
	 * @return void
	 */
	public function testUser() {
		$user = ['id' => '1', 'username' => 'foo'];
		$this->View->set('_authUser', $user);

		$this->assertSame(['id' => '1', 'username' => 'foo'], $this->AuthUserHelper->user());
		$this->assertSame('foo', $this->AuthUserHelper->user('username'));
		$this->assertNull($this->AuthUserHelper->user('foofoo'));
	}

	/**
	 * @return void
	 */
	public function testRoles() {
		$this->AuthUserHelper->setConfig('multiRole', true);
		$user = ['id' => '1', 'Roles' => ['1', '2']];
		$this->View->set('_authUser', $user);

		$this->assertSame(['user' => '1', 'moderator' => '2'], $this->AuthUserHelper->roles());
	}

}
