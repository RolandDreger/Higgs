<section>
	<header>
		<h1><a href="<?= $targetPage->url(); ?>"><?= $targetPage->title(); ?></a></h1>
	</header>
	<?php if($targetPage->documentText()->isNotEmpty()): ?>
		<p><?= esc($targetPage->documentText(), 'html'); ?></p>
	<?php endif; ?>
</section>