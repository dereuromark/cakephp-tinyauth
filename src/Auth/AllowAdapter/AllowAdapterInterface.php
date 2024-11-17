<?php

namespace TinyAuth\Auth\AllowAdapter;

interface AllowAdapterInterface {

	/**
	 * Generates and returns a TinyAuth authentication allow/deny list.
	 *
	 * @param array<string, mixed> $config Current TinyAuth configuration values.
	 *
	 * @return array
	 */
	public function getAllow(array $config): array;

}
