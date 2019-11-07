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
	public $fields = [
		'id' => ['type' => 'integer'],
		'username' => ['type' => 'string', 'null' => false, 'length' => 64, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'role_id' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => 11, 'collate' => null, 'comment' => ''],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => '1',
			'username' => 'dereuromark',
			'role_id' => ROLE_USER
		],
		[
			'id' => '2',
			'username' => 'bravo-kernel',
			'role_id' => ROLE_ADMIN
		],
		[
			'id' => '3',
			'username' => 'adriana',
			'role_id' => ROLE_MODERATOR
		]
	];

}
