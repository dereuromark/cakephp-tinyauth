<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link https://cakephp.org CakePHP(tm) Project
 * @since 3.0.0
 * @license https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TinyAuth\Auth;

use Cake\Core\App;
use RuntimeException;

/**
 * Builds password hashing objects
 */
class PasswordHasherFactory {

	/**
	 * Returns password hasher object out of a hasher name or a configuration array
	 *
	 * @param array<string, mixed>|string $passwordHasher Name of the password hasher or an array with
	 * at least the key `className` set to the name of the class to use
	 * @throws \RuntimeException If password hasher class not found or
	 *   it does not extend {@link \TinyAuth\Auth\AbstractPasswordHasher}
	 * @return \TinyAuth\Auth\AbstractPasswordHasher Password hasher instance
	 */
	public static function build($passwordHasher): AbstractPasswordHasher {
		$config = [];
		if (is_string($passwordHasher)) {
			$class = $passwordHasher;
		} else {
			$class = $passwordHasher['className'];
			$config = $passwordHasher;
			unset($config['className']);
		}

		$className = App::className($class, 'Auth', 'PasswordHasher');
		if ($className === null) {
			throw new RuntimeException(sprintf('Password hasher class "%s" was not found.', $class));
		}

		$hasher = new $className($config);
		if (!($hasher instanceof AbstractPasswordHasher)) {
			throw new RuntimeException('Password hasher must extend AbstractPasswordHasher class.');
		}

		return $hasher;
	}

}
