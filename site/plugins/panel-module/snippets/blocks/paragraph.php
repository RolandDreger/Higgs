<?php $class = $block->paragraphClass(); ?>
<?php if($block->paragraphText()->isNotEmpty()): ?>
	<p <?php if($class->isNotEmpty()) echo 'class="' . $class . '"'; ?>>
		<?= $block->paragraphText(); ?>
	</p>
<?php endif;?>