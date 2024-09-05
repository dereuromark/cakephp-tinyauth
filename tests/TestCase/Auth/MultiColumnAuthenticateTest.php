<?php

namespace TinyAuth\Test\TestCase\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TinyAuth\Auth\MultiColumnAuthenticate;

class MultiColumnAuthenticateTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.TinyAuth.Users',
	];

	/**
	 * @return void
	 */
	public function testAuthenticate() {
		$request = new ServerRequest();
		$request = $request->withData('login', 'dereuromark');
		$request = $request->withData('password', '123');

		$object = new MultiColumnAuthenticate(new ComponentRegistry(new Controller($request)), [
			'fields' => [
				'username' => 'login',
				'password' => 'password',
			],
			'columns' => ['username', 'email'],
		]);
		$result = $object->authenticate($request, new Response());
		$this->assertTrue((bool)$result);
	}

}
