<section>
	<header>
		<h1><a href="<?= $section->url(); ?>"><?= $section->title(); ?></a></h1>
	</header>
	<?php if($section->documentText()->isNotEmpty()): ?>
		<p><?= esc($section->documentText(), 'html'); ?></p>
	<?php endif; ?>
</section>