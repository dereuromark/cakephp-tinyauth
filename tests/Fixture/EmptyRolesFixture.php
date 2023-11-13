<?php

namespace TinyAuth\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * EmptyRolesFixture. Used for existing roles table without roles/records.
 */
class EmptyRolesFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public array $fields = [
		'id' => ['type' => 'integer'],
		'alias' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 20, 'comment' => '', 'charset' => 'utf8'],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public array $records = [];

}
