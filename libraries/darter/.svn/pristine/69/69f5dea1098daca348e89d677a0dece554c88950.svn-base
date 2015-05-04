<?php
require_once 'lib/packages.package.php';
Darter_Package::load('lib');
Darter_Inspection::load();

$class = isset($_GET['class']) ? $_GET['class'] : '';
$function = isset($_GET['function']) ? $_GET['function'] : '';

if($class != '') {
	$detail = new Darter_View('detail');
	$detail->class = new Darter_InspectionClass($class);
	$detail->display();
} elseif($function != '') {
	$detail = new Darter_View('function');
	$detail->function = new Darter_InspectionFunction($function);
	$detail->display();
} else {
	echo "no parameter given";
}
 
?>
