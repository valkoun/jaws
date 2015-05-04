<?php
require_once 'lib/packages.package.php';
Darter_Package::load('lib');
Darter_Inspection::load();

$overview = new Darter_View('packages');
$overview->elements = Darter::getElementsWithoutPackage();
$overview->packages = Darter::getPackages();
$overview->display();

?>