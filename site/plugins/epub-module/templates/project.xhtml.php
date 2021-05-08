<?php snippet('xhtml/content-type'); ?>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="en" lang="en">
	<?php snippet('xhtml/head'); ?>
	<body>
		<!-- Linear Content -->
		<?php foreach($listedChildPages as $listedPage): ?>
			<?php snippet('xhtml/section', ['targetPage' => $listedPage]); ?>
		<?php endforeach; ?>
		<!-- Non-linear Content-->
		<?php foreach($unlistedChildPages as $unlistedPage): ?>
			<?php snippet('xhtml/section', ['targetPage' => $unlistedPage]); ?>
		<?php endforeach; ?>
	</body>
</html>