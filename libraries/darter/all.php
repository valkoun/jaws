<?php
require_once 'lib/packages.package.php';
Darter_Package::load('lib');
Darter_Inspection::load();

$register = new Darter_View('index');
$register->index = Darter::getIndex();
$register->display();

?>