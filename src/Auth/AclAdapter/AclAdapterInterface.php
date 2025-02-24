<?php

namespace TinyAuth\Auth\AclAdapter;

interface AclAdapterInterface {

	/**
	 * Generates and returns a TinyAuth access control list.
	 *
	 * @param array $availableRoles A list of available user roles.
	 * @param array<string, mixed> $config Current TinyAuth configuration values.
	 *
	 * @return array
	 */
	public function getAcl(array $availableRoles, array $config): array;

}
