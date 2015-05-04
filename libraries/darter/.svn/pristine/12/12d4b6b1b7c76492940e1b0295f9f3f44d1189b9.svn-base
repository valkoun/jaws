<?php foreach($parameter->getChildren() as $child): ?>
	<li>
		<?php if($child->getData()->isUserDefined()): ?>
		<a href="detail.php?class=<?php echo $child; ?>"><?php echo $child; ?></a>
		<?php else: ?>
		<?php echo $child; ?>
		<?php endif; ?>
	</li>
	<?php if(count($child->getChildren()) > 0): ?>
	<ul>
	<?php $this->show('tree', $child); ?>
	</ul>
	<?php endif; ?>
<?php endforeach; ?>