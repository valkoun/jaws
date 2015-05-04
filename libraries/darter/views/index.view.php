<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>Index (<?php echo $this->project; ?>)</title>
<link rel="stylesheet" type="text/css" href="../stylesheets/screen.css" />
</head>
<body id="index">

<div class="page">
<p class="title"><?php echo $this->project; ?></p>

<h1>Index</h1>

<!-- <p><?php foreach($this->index as $letter => $elements): ?>
<a href="#<?php echo $letter; ?>"><?php echo $letter; ?></a>
<?php endforeach; ?></p> --> 

<?php foreach($this->index as $letter => $elements): ?>
<div class="section index <?php $this->odd(); ?>">
<h2 class="label" id="letter_<?php echo strtolower($letter); ?>"><?php echo $letter; ?></h2>
<ul class="content">
<?php foreach($elements as $element): ?>
	<?php switch(true):	case ($element instanceof ReflectionMethod): ?>
		<li>
			<a href="detail.php?class=<?php echo $element->getDeclaringClass()->getName() . '#' . $element->getName(); ?>"><?php echo $element->getName(); ?>()</a> in 
			<a href="detail.php?class=<?php echo $element->getDeclaringClass()->getName(); ?>"><?php echo $element->getDeclaringClass()->getName(); ?></a>
		</li>
		<?php break; case ($element instanceof Darter_InspectionFunction): ?>
		<li><a href="detail.php?function=<?php echo $element->getName(); ?>"><?php echo $element->getName(); ?>()</a></li>
		<?php break; default: ?>
		<li><a href="detail.php?class=<?php echo $element->getName(); ?>"><?php echo $element->getName(); ?></a></li>
	<?php endswitch; ?>
<?php endforeach; ?>
</ul>
</div>
<?php endforeach; ?>

<hr />

<?php $this->show('menu'); ?>

</div>

</body>
</html>