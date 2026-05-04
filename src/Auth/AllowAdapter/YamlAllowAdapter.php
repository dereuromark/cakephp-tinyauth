<?php

namespace TinyAuth\Auth\AllowAdapter;

use Cake\Core\Exception\CakeException;
use Symfony\Component\Yaml\Yaml;

class YamlAllowAdapter extends AbstractAllowAdapter {

	/**
	 * @param array<string, mixed> $config Current TinyAuth configuration values.
	 * @throws \Cake\Core\Exception\CakeException
	 * @return array<string, string>
	 */
	protected function parseConfig(array $config): array {
		if (!class_exists(Yaml::class)) {
			throw new CakeException(
				'YamlAllowAdapter requires symfony/yaml. Install via: composer require symfony/yaml',
			);
		}

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

			$data = Yaml::parseFile($file);
			if (!is_array($data)) {
				throw new CakeException(sprintf('Invalid TinyAuth config file (%s)', $file));
			}

			$list += $data;
		}

		return $list;
	}

}
