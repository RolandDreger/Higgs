<?php if($block->paragraphText()->isNotEmpty()): ?>
	<p class="<?= $block->paragraphClass(); ?>">
		<?= $block->paragraphText(); ?>
	</p>
<?php endif;?>