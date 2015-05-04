<?php

class Darter_Properties {

	const PATH = 'config';

	const SUFFIX = '.ini';

	private function __construct() {
		// no instance of this class
	}

	private static $cache = array ();

	public static function load($key) {
		$file = self :: PATH . '/' . $key . self :: SUFFIX;

		if (!isset (self :: $cache[$key])) {
			if (is_readable($file)) {
				self :: $cache[$key] = parse_ini_file($file);
			} else {
				self :: $cache[$key] = array ();
			}
		}

		return self :: $cache[$key];
	}

	public static function get($key) {
		$pos = strpos($key, '.');
		$keys = self :: load(substr($key, 0, $pos));
		$realKey = substr($key, $pos +1);

		if (isset ($keys[$realKey])) {
			return $keys[$realKey];
		}

		return '';
	}
}
?>