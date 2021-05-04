<?php snippet('epub/content-type'); ?>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="en" lang="en">
	<?php snippet('epub/head'); ?>
	<body>
		<!-- Linear Content -->
		<?php foreach($listedChildPages as $listedPage): ?>
			<?php snippet('epub/section', ['targetPage' => $listedPage]); ?>
		<?php endforeach; ?>
		<!-- Non-linear Content-->
		<?php foreach($unlistedChildPages as $unlistedPage): ?>
			<?php snippet('epub/section', ['targetPage' => $unlistedPage]); ?>
		<?php endforeach; ?>
	</body>
</html>