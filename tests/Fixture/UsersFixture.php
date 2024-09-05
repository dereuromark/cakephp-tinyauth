<?php

namespace TinyAuth\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RoleFixture
 */
class UsersFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public array $fields = [
		'id' => ['type' => 'integer'],
		'username' => ['type' => 'string', 'null' => false, 'length' => 64, 'comment' => '', 'charset' => 'utf8'],
		'email' => ['type' => 'string', 'null' => false, 'length' => 64, 'comment' => '', 'charset' => 'utf8'],
		'password' => ['type' => 'string', 'null' => true, 'length' => 64, 'comment' => '', 'charset' => 'utf8'],
		'role_id' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => 11, 'collate' => null, 'comment' => ''],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public array $records = [
		[
			'id' => '1',
			'username' => 'dereuromark',
			'email' => 'dereuromark@test.de',
			'password' => '$2y$10$syCszS4cf9SJrTbf0p6myukCl812046xqM.JPZItfuySnrmm6LH1y', // 123,
			'role_id' => ROLE_USER,
		],
		[
			'id' => '2',
			'username' => 'bravo-kernel',
			'email' => 'bravo-kernel@test.de',
			'role_id' => ROLE_ADMIN,
		],
		[
			'id' => '3',
			'username' => 'adriana',
			'email' => 'adriana@test.de',
			'role_id' => ROLE_MODERATOR,
		],
	];

}
