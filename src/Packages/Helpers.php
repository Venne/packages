<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Packages;

use Nette\InvalidArgumentException;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class Helpers
{

	/**
	 * Get relative path.
	 *
	 * @static
	 * @param $from
	 * @param $to
	 * @param $directorySeparator
	 * @return string
	 */
	public static function getRelativePath($from, $to, $directorySeparator = NULL)
	{
		$directorySeparator = $directorySeparator ? : ((substr(PHP_OS, 0, 3) === 'WIN') ? '\\' : '/');

		if ($directorySeparator !== '/') {
			$from = str_replace($directorySeparator, '/', $from);
			$to = str_replace($directorySeparator, '/', $to);
		}

		$from = substr($from, -1) !== '/' ? $from . '/' : $from;
		$to = substr($to, -1) !== '/' ? $to . '/' : $to;
		$from = explode('/', $from);
		$to = explode('/', $to);
		$relPath = $to;

		foreach ($from as $depth => $dir) {
			if ($dir === $to[$depth]) {
				array_shift($relPath);
			} else {
				$remaining = count($from) - $depth;
				if ($remaining > 1) {
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
		$relPath = implode('/', $relPath);
		$relPath = substr($relPath, -1) === '/' ? substr($relPath, 0, -1) : $relPath;

		if ($directorySeparator !== '/') {
			$relPath = str_replace('/', $directorySeparator, $relPath);
		}

		return $relPath;
	}



	/**
	 * @param $file
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public static function getClassesFromFile($file)
	{
		if (!file_exists($file)) {
			throw new InvalidArgumentException("File '{$file}' does not exist.");
		}

		$classes = array();

		$namespace = NULL;
		$tokens = token_get_all(file_get_contents($file));
		$count = count($tokens);
		$dlm = FALSE;
		for ($i = 2; $i < $count; $i++) {
			if ((isset($tokens[$i - 2][1]) && ($tokens[$i - 2][1] == "phpnamespace" || $tokens[$i - 2][1] == "namespace")) ||
				($dlm && $tokens[$i - 1][0] == T_NS_SEPARATOR && $tokens[$i][0] == T_STRING)
			) {
				if (!$dlm) $namespace = NULL;
				if (isset($tokens[$i][1])) {
					$namespace = $namespace ? $namespace . "\\" . $tokens[$i][1] : $tokens[$i][1];
					$dlm = TRUE;
				}
			} elseif ($dlm && ($tokens[$i][0] != T_NS_SEPARATOR) && ($tokens[$i][0] != T_STRING)) {
				$dlm = FALSE;
			}
			if (($tokens[$i - 2][0] == T_CLASS || (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] == "phpclass"))
				&& $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING
			) {
				$class_name = $tokens[$i][1];
				$classes[] = ($namespace ? $namespace . '\\' : '') . $class_name;
			}
		}
		return $classes;
	}

}

