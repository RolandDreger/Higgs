<?php
	$docPart = trim($targetPage->documentPart());
	$docRole = trim($targetPage->documentRole());
	$docType = trim($targetPage->documentType());
	$epubTypeArray = array_filter([$docPart, $docRole], function($item) { 
		return !empty($item); 
	});
	$epupType = implode(' ', $epubTypeArray);
	$docRole = trim($targetPage->documentRole());
	$docTitle = $targetPage->title();
	$docText = $targetPage->documentText();
?>

<section <?= Xml::attr(['epub:type' => $epupType, 'role' => $docRole]); ?>>
	<header>
		<?= $docTitle->toXhtml('text', 'h1', ['epub:type' => 'title']); ?>
	</header>
	<?= $docText->toXhtml('blocks'); ?>
</section>