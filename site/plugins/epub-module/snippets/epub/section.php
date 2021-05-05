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
		<h1 epub:type="title"><?= $docTitle; ?></h1>
	</header>
	<?php if($docText->isNotEmpty()): ?>
		<?= markdown($docText, 'html'); ?>
	<?php endif; ?>
</section>