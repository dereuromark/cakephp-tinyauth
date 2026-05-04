<?php

namespace TinyAuth\Auth\AclAdapter;

use TinyAuth\Utility\Utility;

class IniAclAdapter extends AbstractAclAdapter {

	/**
	 * @param array<string, mixed> $config Current TinyAuth configuration values.
	 * @return array<string, array<string, string>>
	 */
	protected function parseConfig(array $config): array {
		return Utility::parseFiles($config['filePath'], $config['file']);
	}

}
