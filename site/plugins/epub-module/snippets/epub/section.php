<section epub:type="bodymatter chapter" role="doc-chapter">
	<header>
		<h1 epub:type="title"><?= $section->title(); ?></h1>
	</header>
	<?php if($section->text()->isNotEmpty()): ?>
		<p><?= esc($section->text(), 'html'); ?></p>
	<?php endif; ?>
</section>