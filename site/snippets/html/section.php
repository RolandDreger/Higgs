<section>
	<header>
		<h1><?= $section->title(); ?></h1>
	</header>
	<?php if($section->text()->isNotEmpty()): ?>
		<p><?= esc($section->text(), 'html'); ?></p>
	<?php endif; ?>
</section>