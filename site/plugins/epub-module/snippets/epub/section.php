<section epub:type="bodymatter chapter" role="doc-chapter">
	<header>
		<h1 epub:type="title"><?= $section->title(); ?></h1>
	</header>
	<?php if($section->documentText()->isNotEmpty()): ?>
		<p><?= esc($section->documentText(), 'html'); ?></p>
	<?php endif; ?>
</section>