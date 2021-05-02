<?php snippet('epub/content-type'); ?>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="en" lang="en">
	<?php snippet('epub/head'); ?>
	<body>
		<?php snippet('epub/section', ['section' => $page]); ?>
	</body>
</html>