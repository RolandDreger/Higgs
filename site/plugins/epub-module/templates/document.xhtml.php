<?php snippet('xhtml/content-type'); ?>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="en" lang="en">
	<?php snippet('xhtml/head'); ?>
	<body>
		<?php 
			snippet('xhtml/section', [
				'targetPages' => [$page], 
				'level' => 1
			]); 
		?>
	</body>
</html>