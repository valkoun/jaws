<?php

class Darter_TreeItem extends Darter_TreeItemContainer  {
	private $id;
	private $data;

	public function __construct($id, $data = null) {
		$this->id = $id;
		$this->data = $data;
	}
	
	public function getData() {
		return $this->data;
	}

	public function __toString() {
		return $this->id;
	}
}

class Darter_TreeItemContainer {
	private $children = array();

	public function getChildren() {
		return $this->children;
	}

	public function add(Darter_TreeItem $child) {
		$this->children[] = $child;
	}
}

?>