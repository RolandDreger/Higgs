<?php if(!isset($level)) { $level = 0; } ?>
<?php foreach($targetPages as $targetPage): ?>
	<?php
		/* Section Attributes */
		$sectionAttrArray = [
			'id' => $targetPage->hashID(),
			'class' => "level-{$level}"
		];
		if($targetPage->documentType()->isNotEmpty()) {
			$docTypeArray = $targetPage->documentType()->split();
			$sectionAttrArray['epub:type'] = implode(' ', $docTypeArray);
		}
		if($targetPage->documentRole()->isNotEmpty()) {
			$sectionAttrArray['role'] = $targetPage->documentRole();
		}
	?>
	<section <?= Xml::attr($sectionAttrArray); ?>>
		<?php if($targetPage->documentIsTitleVisible()->value() !== 'false'): ?>
			<header>
				<?= $targetPage->title()->toXhtml('text', 'h1', ['epub:type' => 'title']); ?>
			</header>
		<?php endif; ?>
		<?= $targetPage->documentContent()->toXhtml('blocks'); ?>
	</section>
<?php endforeach; ?>