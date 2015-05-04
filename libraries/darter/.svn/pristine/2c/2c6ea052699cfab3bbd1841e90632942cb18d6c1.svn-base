<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title><?php echo $this->class->getType() ?> <?php echo $this->class->getName(); ?> (<?php echo $this->project; ?>)</title>
<link rel="stylesheet" type="text/css" href="stylesheets/screen.css" />
</head>
<body>

<div class="page">
<p class="title"><?php echo $this->project; ?></p>

<h1><?php echo $this->class->getType() ?> <?php echo $this->class->getName(); ?></h1>

<?php if(count($this->class->getAnnotations()) > 0): ?>
<div class="section class <?php $this->odd(); ?>">
<h2 class="label">Information</h2>

<dl class="content">
	<?php foreach($this->class->getAnnotations() as $annotations): ?>
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

<?php if($parentClass = $this->class->getParentClass()): ?>
<div class="section inheritance <?php $this->odd(); ?>">
<h2 class="label">Inheritance</h2>

<ul class="content">
		<li>
			<a href="detail.php?class=<?php echo urlencode($parentClass->getName()); ?>"><?php echo $parentClass->getName(); ?></a>
		</li>
</ul>
</div>
<?php endif; ?>
	

<?php if($this->class->getInterfaces()): ?>
<div class="section interfaces <?php $this->odd(); ?>">
<h2 class="label">Interfaces</h2>

<ul class="content">

	<?php foreach($this->class->getInterfaces() as $interface): ?>
		<li>
			<a href="detail.php?class=<?php echo $interface->getName(); ?>"><?php echo $interface->getName(); ?></a>
		</li>
	<?php endforeach; ?>
	
</ul>
</div>
<?php endif; ?>

<?php if($this->class->getDescription() != ''): ?>
<div class="section description <?php $this->odd(); ?>">
<h2 class="label">Description</h2>

<p class="content"><?php echo $this->class->getDescription(); ?></p>
</div>
<?php endif; ?>

<?php if(count($this->class->getProperties()) > 0): ?>
<div class="section fields <?php $this->odd(); ?>">
<h2 class="label">Fields</h2>
<dl class="content">
<?php foreach($this->class->getProperties() as $property): ?>
		<dt><code><?php echo $property->getModifier(); ?> <?php echo $property->getType(); ?> $<?php echo $property->getName() ?>
		</code></dt>
		<dd><?php echo $property->getDescription(); ?></dd>
<?php endforeach; ?>
	<!--<dt><code>const DEFAULT = 42</code></dt>
	<dd>The default value for the integer.</dd>
	<dt><code>public $name = ''</code></dt>
	<dd>The name of the object or the collection.</dd>
	-->
</dl>
</div>
<?php endif; ?>

<?php if(count($this->class->getMethods()) > 0): ?>
<div class="section methods <?php $this->odd(); ?>">
<h2 class="label">Methods</h2>

<div class="content">

<ul>

<?php foreach($this->class->getMethods() as $method): ?>
	
	<li>
		<code><a href="#<?php echo $method->getName(); ?>"><?php echo $method->getName(); ?></a></code>
	</li>
	
<?php endforeach; ?>
	<!--
	<li><code><a href="">ArrayObject::getFoo</a>(string $value,
	array $access)</code></li>
	<li title="Returns the name of the object or the collection."><code><a
		href="">getName</a>(string $value)</code></li>-->
</ul>
<?php foreach($this->class->getMethods() as $method): ?>
<h3 id="<?= $method->getName(); ?>">
<?php echo $method->getDeclaration(); ?> <?= $method->getName(); ?>
				(<?php
				$first = true;
				foreach($method->getParameters() as $param) {
					if($first) {
						$first = false;
					}
					else {
						echo ", ";
					}
					echo "$".$param->name."";
				}
				?>)
</h3>

<div class="method">
	<!--<p><code>public string getName(string $value)</code></p>-->
	
	<!--<?= var_dump($method) ?>-->
	
	<p><?php echo $method->getDescription(); ?></p>
	
	<dl>
	<?php foreach($method->getAnnotations() as $annotations): ?>
	<?php $i = 0; ?>
	<?php foreach($annotations as $annotation): ?>
		<?php if($i++ == 0): ?>
		<dt><?php echo $annotation->getTitle() ?><?php if(count($annotations) > 1): ?>s<?php endif; ?></dt>
		<?php endif; ?>
		<dd><?php echo $annotation->getBody() ?></dd>
	<?php endforeach; ?>
	<?php endforeach; ?>
		<?php if($method->getDeclaringClass()->getName() != $this->class->getName()): ?>
		<dt>Declaration</dt>
		<dd><?php echo $method->getDeclaringClass()->getType(); ?> 
			<a href="?class=<?php echo $method->getDeclaringClass()->getName(); ?>">
			<?php echo $method->getDeclaringClass()->getName(); ?></a>
		</dd>
		<?php endif; ?>
	</dl>
</div>
<?php endforeach; ?>

</div>
</div>
<?php endif; ?>

<div class="section file <?php $this->odd(); ?>">
<h2 class="label">Location</h2>

<p class="content"><?php if($this->class->isUserDefined()): ?>
Line <?php echo $this->class->getStartLine(); ?> of file <?php echo $this->class->getDarterFileName(); ?>
<?php else: ?>
Internal class
<?php if($this->class->getExtensionName() != ''): ?> (Extension <?php echo $this->class->getExtensionName(); ?>)<?php endif; ?>
<?php endif; ?></p>
</div>

<hr />

<?php $this->show('menu'); ?>
</div>

</body>
</html>
