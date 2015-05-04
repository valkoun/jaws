<?php

class Darter_Inspection {

	public static function load() {
		$source = Darter_Properties::get('darter.source');
		if(is_file($source)) {
			include_once $source;
		} else {
			self::scan(Darter_Properties::get('darter.source'), Darter_Properties::get('darter.suffix'));
		}
	}

	private static function scan($path, $suffix) {
		$suffixLength = strlen($suffix);
		foreach (scandir($path) as $file) {
			$current = $path . '/' . $file;
			if($file != '.' and $file != '..' and is_dir($current)) {
				self::scan($current, $suffix);
			} else {
				if (substr($file, -$suffixLength) == $suffix) {
					include_once $current;
				}
			}
		}
	}

	public static function parseAnnotations($comment) {
		$annotations = array();

		$annotationClasses = array();
		foreach(get_declared_classes() as $class) {
			$reflection = new ReflectionClass($class);
			if($reflection->implementsInterface('Darter_Annotation')) {
				$annotationClasses[] = $reflection;
			}
		}

		$array = explode( "\n" , $comment );

		$sentence = '';
		foreach($array as $line) {
			foreach($annotationClasses as $reflection) {
				$name = call_user_func(array($reflection->getName(), 'getName'));
				if (preg_match('/\* @' . call_user_func(array($reflection->getName(), 'getName')) . ' (.*)/', $line, $matches)) {
					$class = $reflection->getName();
					$annotation = new $class($matches[1]);
					if(!isset($annotations[$name])) {
						$annotations[$name] = array();
					}
					$annotations[$name][] = $annotation;
				}
			}
		}


		return $annotations;
	}

	public static function parseDescription($comment) {
		$array = explode( "\n" , $comment );

		$sentence = '';
		foreach($array as $line) {
			if (preg_match("/\* ([^@].*)/", $line, $matches)) {
				$sentence .= trim($matches[1]) . ' ';
			}
		}

		return trim($sentence);
	}

	public static function parseType($comment) {
		$array = explode("\n" , $comment);

		$type = '';
		foreach($array as $line) {
			if (preg_match('/\* @var (.*)/', $line, $matches)) {
				$type = $matches[1];
				break;
			}
		}

		return $type;
	}

	public static function isNotExcluded($name) {
		$excludes = Darter_Properties::get('darter.exclude');
		foreach(explode(',', $excludes) as $exclude) {
			if(substr($exclude, 0, 1) == '*') {
				$exclude = substr($exclude, 1);
				if (substr($name, -strlen($exclude)) == $exclude) {
					return false;
				}
			} elseif (substr($exclude, -1) == '*') {
				$exclude = substr($exclude, 0, -1);
				if (substr($name, 0, strlen($exclude)) == $exclude) {
					return false;
				}
			} else {
				if ($name == $exclude) {
					return false;
				}
			}
		}
		return true;
	}
}

class Darter_InspectionProperty extends ReflectionProperty {

	private $modifier;

	private $description;

	private $type;

	public function getModifier() {
		return $this->modifier;
	}

	public function __construct($class, $name) {
		parent::__construct($class, $name);

		$this->modifier = implode(' ', Reflection::getModifierNames($this->getModifiers()));

		$this->description = Darter_Inspection::parseDescription($this->getDocComment());

		$this->type = Darter_Inspection::parseType($this->getDocComment());
	}

	public function getDescription() {
		return $this->description;
	}

	public function getType() {
		return $this->type;
	}
}

class Darter_InspectionClass extends ReflectionClass {

	private $description;

	private $annotations;

	public function __construct($class) {
		parent::__construct($class);

		$this->darter_className = $class;

		$this->description = Darter_Inspection::parseDescription($this->getDocComment());

		$this->annotations = Darter_Inspection::parseAnnotations($this->getDocComment());
	}

	public function getDescription() {
		return $this->description;
	}

	public function getAnnotations() {
		return $this->annotations;
	}

	public function getAnnotationsByName($name) {
		if(isset($this->annotations[$name])) {
			return $this->annotations[$name];
		} else {
			return array();
		}
	}

	public function getMethods() {
		$methods = array();
		foreach(parent::getMethods() as $method) {
			$methods[$method->getName()] = new Darter_InspectionMethod($this->getName(), $method->getName());
		}
		ksort($methods);
		return $methods;
	}

	public function getProperties() {
		$properties = array();
		foreach(parent::getProperties() as $property) {
			$properties[$property->getName()] = new Darter_InspectionProperty($this->getName(), $property->getName());
		}
		ksort($properties);
		return $properties;
	}

	public function isNotExcluded() {
		return Darter_Inspection::isNotExcluded($this->getName());
	}

	public function getDarterFileName() {
		return substr($this->getFileName(), strlen(substr(dirname(__FILE__), 0 ,-4) . '/' . Darter_Properties::get('darter.source') . '/'));
	}

	public function getType() {
		if($this->isInterface()) {
			return "Interface";
		}
		elseif ($this->isAbstract()) {
			return "Abstract Class";
		}
		else {
			return "Class";
		}
	}

	/**
	 * @return Darter_InspectionClass
	 */
	public function getParentClass() {
		if(parent::getParentClass() != null) {
			return new Darter_InspectionClass(parent::getParentClass()->getName());
		} else {
			return null;
		}
	}

}

class Darter_InspectionMethod extends ReflectionMethod {

	private $description;

	private $annotations;

	public function __construct($class, $name) {
		parent::__construct($class, $name);

		$this->description = Darter_Inspection::parseDescription($this->getDocComment());

		$this->annotations = Darter_Inspection::parseAnnotations($this->getDocComment());
	}

	public function getDeclaringClass() {
		return new Darter_InspectionClass(parent::getDeclaringClass()->getName());
	}

	public function getDescription() {
		return $this->description;
	}

	public function getAnnotations() {
		return $this->annotations;
	}

	public function getVisibility() {
		if($this->isPrivate()) {
			return 'private';
		} elseif($this->isProtected()) {
			return 'protected';
		} else {
			return 'public';
		}
	}

	public function getDeclaration() {
		$declaration = $this->getVisibility();
		if($this->isFinal()) {
			$declaration .= ' final';
		}
		if($this->isAbstract()) {
			$declaration .= ' abstract';
		}
		return $declaration;
	}
}

class Darter_InspectionFunction extends ReflectionFunction {

	private $description;

	private $annotations;

	public function __construct($name) {
		parent::__construct($name);

		$this->description = Darter_Inspection::parseDescription($this->getDocComment());

		$this->annotations = Darter_Inspection::parseAnnotations($this->getDocComment());
	}

	public function getDescription() {
		return $this->description;
	}

	public function getAnnotations() {
		return $this->annotations;
	}

	public function getAnnotationsByName($name) {
		if(isset($this->annotations[$name])) {
			return $this->annotations[$name];
		} else {
			return array();
		}
	}

	public function getDarterFileName() {
		return substr($this->getFileName(), strlen(substr(dirname(__FILE__), 0 ,-4) . '/' . Darter_Properties::get('darter.source') . '/'));
	}

	public function isNotExcluded() {
		return Darter_Inspection::isNotExcluded($this->getName());
	}
}

?>
