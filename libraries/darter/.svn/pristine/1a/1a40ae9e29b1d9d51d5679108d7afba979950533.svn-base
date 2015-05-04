<?php

class Darter_Package {
	public static function load($path) {
		if(is_array($path)) {
			foreach($path as $apath) {
				self :: loadPackage($apath);
			}
		} else {
			self :: loadPackage($path);
		}
	}
	
	private static function loadPackage($path) {
		foreach (scandir($path) as $file) {
			if (substr($file, -12) == '.package.php') {
				include_once $path . '/' . $file;
			}
		}
	}
}

?>
