<?php

namespace TinyAuth\Auth\AllowAdapter;

use TinyAuth\Utility\Utility;

class IniAllowAdapter extends AbstractAllowAdapter {

	/**
	 * @param array<string, mixed> $config Current TinyAuth configuration values.
	 * @return array<string, string>
	 */
	protected function parseConfig(array $config): array {
		return Utility::parseFiles($config['filePath'], $config['file']);
	}

}
