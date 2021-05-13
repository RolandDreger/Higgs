<?php if(!isset($level)) { $level = 0; } ?>
<?php foreach($targetPages as $targetPage): ?>
	<?php
		$docPart = trim($targetPage->documentPart());
		$docRole = trim($targetPage->documentRole());
		$docType = trim($targetPage->documentType());
		$epubTypeArray = array_filter([$docPart, $docRole], function($item) { 
			return !empty($item); 
		});
		$epupType = implode(' ', $epubTypeArray);
		$docRole = trim($targetPage->documentRole());
		$sectionAttributes = Xml::attr([
			'class' => "level-{$level}", 
			'epub:type' => $epupType, 
			'role' => $docRole
		]);
	?>
	<section <?= $sectionAttributes; ?>>
		<?php /* Title */ ?>
		<header>
			<?= $targetPage->title()->toXhtml('text', 'h1', ['epub:type' => 'title']); ?>
		</header>
		<?php /* Content */ ?>
		<?= $targetPage->documentText()->toXhtml('blocks'); ?>
		<?php /* Subpages */ ?>
		<?php 
			snippet('xhtml/section', [
				'targetPages' => $targetPage->children()->published(), 
				'level' => ($level + 1)
			]); 
		?>
	</section>
<?php endforeach; ?>