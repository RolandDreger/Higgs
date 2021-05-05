<section 
	epub:type="<?= trim($targetPage->documentPart() . ' ' . $targetPage->documentRole()); ?>" 
	role="<?= $targetPage->documentRole(); ?>"
>
	<header>
		<h1 epub:type="title"><?= $targetPage->title(); ?></h1>
	</header>
	<?php if($targetPage->documentText()->isNotEmpty()): ?>
		<p><?= esc($targetPage->documentText(), 'html'); ?></p>
	<?php endif; ?>
</section>