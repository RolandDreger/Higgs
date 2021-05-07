<section>
	<header>
		<h1><a href="<?= $targetPage->url(); ?>"><?= $targetPage->title(); ?></a></h1>
	</header>
	<?php foreach($targetPage->documentText()->toBlocks() as $block): ?>
		<?= $block; ?>
	<?php endforeach ?>
</section>