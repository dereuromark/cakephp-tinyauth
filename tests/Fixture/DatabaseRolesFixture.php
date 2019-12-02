<?php

namespace TinyAuth\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DatabaseRolesFixture.
 */
class DatabaseRolesFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'null' => false, 'length' => 64, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'description' => ['type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'alias' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 20, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => '11',
			'name' => 'User',
			'description' => 'Basic authenticated user',
			'alias' => 'user',
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
		],
		[
			'id' => '12',
			'name' => 'Moderator',
			'description' => 'Authenticated user with moderator role',
			'alias' => 'moderator',
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
		],
		[
			'id' => '13',
			'name' => 'Admin',
			'description' => 'Authenticated user with admin role',
			'alias' => 'admin',
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
		],
	];

}
