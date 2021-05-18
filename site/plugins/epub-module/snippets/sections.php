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
		<?php /* Title */ ?>
		<header>
			<?= $targetPage->title()->toXhtml('text', 'h1', ['epub:type' => 'title']); ?>
		</header>
		<?php /* Content */ ?>
		<?= $targetPage->documentText()->toXhtml('blocks'); ?>
		<?php /* Subpages */ ?>
		<?php 
			snippet('xhtml/sections', [
				'targetPages' => $targetPage->children()->published(), 
				'level' => ($level + 1)
			]); 
		?>
	</section>
<?php endforeach; ?>