<?php

namespace TinyAuth\Auth\AllowAdapter;

use Cake\Core\Exception\CakeException;

class PhpAllowAdapter extends AbstractAllowAdapter {

	/**
	 * @param array<string, mixed> $config Current TinyAuth configuration values.
	 * @throws \Cake\Core\Exception\CakeException
	 * @return array<string, string>
	 */
	protected function parseConfig(array $config): array {
		$paths = $config['filePath'] ?? null;
		if ($paths === null) {
			$paths = ROOT . DS . 'config' . DS;
		}

		$list = [];
		foreach ((array)$paths as $path) {
			$file = $path . $config['file'];
			if (!file_exists($file)) {
				throw new CakeException(sprintf('Missing TinyAuth config file (%s)', $file));
			}

			$data = include $file;
			if (!is_array($data)) {
				throw new CakeException(sprintf('Invalid TinyAuth config file (%s)', $file));
			}

			$list += $data;
		}

		return $list;
	}

}
