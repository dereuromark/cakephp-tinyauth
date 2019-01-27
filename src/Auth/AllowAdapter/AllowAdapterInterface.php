<?php

namespace TinyAuth\Auth\AllowAdapter;

interface AllowAdapterInterface {

	/**
	 * Generates and returns a TinyAuth authentication allow/deny list.
	 *
	 * @param array $availableRoles A list of available user roles.
	 * @param array $config Current TinyAuth configuration values.
	 *
	 * @return array
	 */
	public function getAllow(array $availableRoles, array $config);

}
