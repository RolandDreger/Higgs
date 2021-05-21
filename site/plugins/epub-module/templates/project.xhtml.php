<?php snippet('xhtml/content-type'); ?>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="en" lang="en">
	<?php snippet('xhtml/head'); ?>
	<body>
		<?php 
			/* Build ePub */
			$options = ['formatOutput' => true];
			$epubBuilder = new Higgs\Epub\EpubBuilder($page, $options);
			$epubBuilder->createEpub();
			foreach($epubBuilder->errors as $error) {
				var_dump($error);
			}
		?>
		<!-- Linear Content -->
		<?php 
			snippet('xhtml/sections', [
				'targetPages' => $listedChildPages, 
				'level' => 1
			]); 
		?>
		<!-- Non-linear Content -->
		<?php 
			snippet('xhtml/sections', [
				'targetPages' => $unlistedChildPages, 
				'level' => 0
			]); 
		?>
	</body>
</html>