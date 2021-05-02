<?php snippet('epub/content-type'); ?>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="en" lang="en">
	<?php snippet('epub/head'); ?>
	<body>

		<!-- Linear Content -->
		<?php foreach($page->children()->listed() as $linearSection): ?>
			<?php snippet('epub/section', ['section' => $linearSection]); ?>
		<?php endforeach; ?>
		
		<!-- Non-linear Content-->
		<?php foreach($page->children()->unlisted() as $nonLinearSection): ?>
			<?php snippet('epub/section'); ?>
		<?php endforeach; ?>

	</body>
</html>