<?php

namespace TestApp\Auth\AclAdapter;

use TinyAuth\Auth\AclAdapter\AclAdapterInterface;

class CustomAclAdapter implements AclAdapterInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getAcl(array $availableRoles, array $config): array {
		return [];
	}

}
