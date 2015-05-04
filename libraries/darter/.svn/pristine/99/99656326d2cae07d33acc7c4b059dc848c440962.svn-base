<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>Function <?php echo $this->function->getName(); ?> (<?php echo $this->project; ?>)</title>
<link rel="stylesheet" type="text/css" href="stylesheets/screen.css" />
</head>
<body>

<div class="page">
<p class="title"><?php echo $this->project; ?></p>

<h1>Function <?php echo $this->function->getName(); ?></h1>

<?php if(count($this->function->getParameters()) > 0): ?>
<div class="section description <?php $this->odd(); ?>">
<h2 class="label">Signature</h2>

<p class="content"><?php echo $this->function->getName(); ?> 
(<?php
				$first = true;
				foreach($this->function->getParameters() as $param) {
					if($first) {
						$first = false;
					}
					else {
						echo ", ";
					}
					echo "$".$param->name."";
				}
				?>)
</p>
</div>
<?php endif; ?>

<?php if(count($this->function->getAnnotations()) > 0): ?>
<div class="section class <?php $this->odd(); ?>">
<h2 class="label">Information</h2>

<dl class="content">
	<?php foreach($this->function->getAnnotations() as $annotations): ?>
	<?php $i = 0; ?>
	<?php foreach($annotations as $annotation): ?>
		<?php if($i++ == 0): ?>
		<dt><?php echo $annotation->getTitle() ?><?php if(count($annotations) > 1): ?>s<?php endif; ?></dt>
		<?php endif; ?>
		<dd><?php echo $annotation->getBody() ?></dd>
	<?php endforeach; ?>
	<?php endforeach; ?>
</dl>
</div>
<?php endif; ?>

<?php if($this->function->getDescription() != ''): ?>
<div class="section description <?php $this->odd(); ?>">
<h2 class="label">Description</h2>

<p class="content"><?php echo $this->function->getDescription(); ?></p>
</div>
<?php endif; ?>

<div class="section file <?php $this->odd(); ?>">
<h2 class="label">Location</h2>

<p class="content"><?php if($this->function->isUserDefined()): ?>
Line <?php echo $this->function->getStartLine(); ?> of file <?php echo $this->function->getDarterFileName(); ?>
<?php else: ?>
Internal class
<?php if($this->function->getExtensionName() != ''): ?> (Extension <?php echo $this->function->getExtensionName(); ?>)<?php endif; ?>
<?php endif; ?></p>
</div>

<hr />

<?php $this->show('menu'); ?>
</div>

</body>
</html>
