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
		<header>
			<?= $targetPage->title()->toXhtml('text', 'h1', ['epub:type' => 'title']); ?>
		</header>
		<?= $targetPage->documentText()->toXhtml('blocks'); ?>
		<?php if($targetPage->hasChildren()): ?>
			<?php 
				snippet('xhtml/section', [
					'targetPages' => $targetPage->children(), 
					'level' => ($level + 1)
				]); 
			?>
		<?php endif; ?>
	</section>
<?php endforeach; ?>