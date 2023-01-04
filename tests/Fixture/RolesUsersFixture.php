<?php

namespace TinyAuth\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class RolesUsersFixture extends TestFixture {

	/**
	* Fields
	*
	* @var array
	*/
	public array $fields = [
		'id' => ['type' => 'integer'],
		'user_id' => ['type' => 'integer'],
		'role_id' => ['type' => 'integer'],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id']],
		],
	];

	/**
	* Records
	*
	* @var array
	*/
	public array $records = [
		[
			'id' => 1,
			'user_id' => 1,
			'role_id' => ROLE_USER, // user
		],
		[
			'id' => 2,
			'user_id' => 1,
			'role_id' => ROLE_MODERATOR, // moderator
		],
		[
			'id' => 3,
			'user_id' => 2,
			'role_id' => ROLE_USER, // user
		],
		[
			'id' => 4,
			'user_id' => 2,
			'role_id' => ROLE_ADMIN, // admin
		],
	];

}
