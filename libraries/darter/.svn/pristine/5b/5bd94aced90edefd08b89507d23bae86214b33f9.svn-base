<?php

class Darter_ClassTree {
	private $classes = array();
	private $root;

	public function __construct() {
		$this->root = new Darter_TreeItemContainer();
	}

	public function getRoot() {
		return $this->root;
	}

	public function getClasses() {
		return $this->classes;
	}

	public function add(Darter_InspectionClass $inspection) {
		if(!key_exists($inspection->getName(), $this->classes)) {
			$this->classes[$inspection->getName()] = new Darter_TreeItem($inspection->getName(), $inspection);
			if($inspection->getParentClass() != null) {
				$this->add($inspection->getParentClass()); // make sure parent class is in list
				$this->classes[$inspection->getParentClass()->getName()]->add($this->classes[$inspection->getName()]);
			} else {
				$this->root->add($this->classes[$inspection->getName()]);
			}
		}
	}
}

class Darter {
	public static function getClasses() {
		$classes = array();

		foreach(get_declared_classes() as $class) {
			$inspection = new Darter_InspectionClass($class);
			if($inspection->isUserDefined() && $inspection->isNotExcluded()) {
				$classes[$inspection->getName()] = $inspection;
			}
		}

		uksort($classes, 'strnatcasecmp');

		return $classes;
	}

	public static function getElementsWithoutPackage() {
		$elements = array();

		foreach(self::getClasses() as $class) {
			if(count($class->getAnnotationsByName('package')) == 0) {
				$elements[$class->getName()] = $class;
			}
		}

		foreach(self::getInterfaces() as $interface) {
			if(count($interface->getAnnotationsByName('package')) == 0) {
				$elements[$interface->getName()] = $interface;
			}
		}

		foreach(self::getFunctions() as $function) {
			if(count($function->getAnnotationsByName('package')) == 0) {
				$elements[$function->getName()] = $function;
			}
		}

		uksort($elements, 'strnatcasecmp');

		return $elements;
	}

	public static function getPackages() {
		$packages = array();

		foreach(self::getClasses() as $class) {
			foreach($class->getAnnotationsByName('package') as $annotation) {
				if(!isset($packages[$annotation->getPackage()])) {
					$packages[$annotation->getPackage()] = array();
				}

				$packages[$annotation->getPackage()][] = $class;
			}
		}

		foreach(self::getInterfaces() as $interface) {
			foreach($interface->getAnnotationsByName('package') as $annotation) {
				if(!isset($packages[$annotation->getPackage()])) {
					$packages[$annotation->getPackage()] = array();
				}

				$packages[$annotation->getPackage()][] = $interface;
			}
		}

		foreach(self::getFunctions() as $function) {
			foreach($function->getAnnotationsByName('package') as $annotation) {
				if(!isset($packages[$annotation->getPackage()])) {
					$packages[$annotation->getPackage()] = array();
				}

				$packages[$annotation->getPackage()][] = $function;
			}
		}

		uksort($packages, 'strnatcasecmp');

		return $packages;
	}

	public static function getIndex() {
		$index = array();

		// add classes to index
		foreach(self::getClasses() as $class) {
			$letter = strtoupper(substr($class->getName(), 0, 1));
			if(isset($index[$letter])) {
				$index[$letter][$class->getName()] = $class;
			} else {
				$index[$letter] = array($class->getName() => $class);
			}

			// add class methods to index
			foreach($class->getMethods() as $method) {
				if($method->getDeclaringClass()->getName() == $class->getName()) { // Don't add inherit methods
					$letter = strtoupper(substr($method->getName(), 0, 1));
					if(isset($index[$letter])) {
						$index[$letter][$method->getName() . '::' . $method->getDeclaringClass()->getName()] = $method;
					} else {
						$index[$letter] = array($method->getName() . '::' . $method->getDeclaringClass()->getName() => $method);
					}
				}
			}
		}

		// add interfaces to index
		foreach(self::getInterfaces() as $interface) {
			$letter = strtoupper(substr($interface->getName(), 0, 1));
			if(isset($index[$letter])) {
				$index[$letter][$interface->getName()] = $interface;
			} else {
				$index[$letter] = array($interface->getName() => $interface);
			}

			// add interface methods to index
			foreach($interface->getMethods() as $method) {
				if($method->getDeclaringClass()->getName() == $interface->getName()) { // Don't add inherit methods
					$letter = strtoupper(substr($method->getName(), 0, 1));
					if(isset($index[$letter])) {
						$index[$letter][$method->getName() . '::' . $method->getDeclaringClass()->getName()] = $method;
					} else {
						$index[$letter] = array($method->getName() . '::' . $method->getDeclaringClass()->getName() => $method);
					}
				}
			}
		}

		// add functions to index
		foreach(self::getFunctions() as $function) {
			$letter = strtoupper(substr($function->getName(), 0, 1));
			if(isset($index[$letter])) {
				$index[$letter][$function->getName()] = $function;
			} else {
				$index[$letter] = array($function->getName() => $function);
			}
		}

		// sort index members
		foreach($index as $letter => $array) {
			uksort($array, 'strnatcasecmp');
			$index[$letter] = $array;
		}

		// sort index
		ksort($index);

		return $index;
	}

	public static function getClassTree() {
		$tree = new Darter_ClassTree();

		foreach(self::getClasses() as $class) {
			$tree->add($class);
		}

		return $tree->getRoot();
	}

	public static function getInterfaces() {
		$interfaces = array();

		foreach(get_declared_interfaces() as $interface) {
			$inspection = new Darter_InspectionClass($interface);
			if($inspection->isUserDefined() && $inspection->isNotExcluded()) {
				$interfaces[$inspection->getName()] = $inspection;
			}
		}

		uksort($interfaces, 'strnatcasecmp');

		return $interfaces;
	}

	public static function getFunctions() {
		$functions = array();

		$functionArray = get_defined_functions();
		foreach($functionArray['user'] as $function) {
			$inspection = new Darter_InspectionFunction($function);
			if($inspection->isNotExcluded()) {
				$functions[$inspection->getName()] = $inspection;
			}
		}

		uksort($functions, 'strnatcasecmp');

		return $functions;
	}
}

?>