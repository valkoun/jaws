<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>Packages (<?php echo $this->project; ?>)</title>
<link rel="stylesheet" type="text/css" href="stylesheets/screen.css" />
</head>
<body id="packages">

<div class="page">
<p class="title"><?php echo $this->project; ?></p>

<h1>Packages</h1>

<?php foreach($this->packages as $package => $elements): ?>
<div class="section package <?php $this->odd(); ?>">
<h2 class="label" id="package_<?php echo $package; ?>"><?php echo $package; ?></h2>
<ul class="content">
<?php foreach($elements as $element): ?>
	<?php if($element instanceof Darter_InspectionFunction): ?>
	<li><a href="detail.php?function=<?php echo $element->getName(); ?>" title="Function"><?php echo $element->getName(); ?>()</a></li>
	<?php else: ?>
	<li><a href="detail.php?class=<?php echo $element->getName(); ?>" title="<?php echo $element->getType(); ?>"><?php echo $element->getName(); ?></a></li>
	<?php endif; ?>
<?php endforeach; ?>
</ul>
</div>
<?php endforeach; ?>

<div class="section package">
<h2 class="label" id="package">(none)</h2>
<ul class="content">
<?php foreach($this->elements as $element): ?>
	<?php if($element instanceof Darter_InspectionFunction): ?>
	<li><a href="detail.php?function=<?php echo $element->getName(); ?>" title="Function"><?php echo $element->getName(); ?>()</a></li>
	<?php else: ?>
	<li><a href="detail.php?class=<?php echo $element->getName(); ?>" title="<?php echo $element->getType(); ?>"><?php echo $element->getName(); ?></a></li>
	<?php endif; ?>
<?php endforeach; ?>
</ul>
</div>

<hr />

<?php $this->show('menu'); ?>

</div>

</body>
</html>