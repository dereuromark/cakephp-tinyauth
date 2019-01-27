<?php

namespace TestApp\Auth\AclAdapter;

use TinyAuth\Auth\AclAdapter\AclAdapterInterface;

class CustomAclAdapter implements AclAdapterInterface {

	/**
	 * {@inheritdoc}
	 *
	 * @return array
	 */
	public function getAcl(array $availableRoles, array $config) {
		return [];
	}

}
