<?php
	header('Content-type: text/xml; charset="utf-8"');
	echo '<?xml version="1.0" encoding="utf-8" ?>';
?>

<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="en" lang="en">
	<head>
		<meta charset="utf-8" />
		<title><?= $page->title(); ?></title>
		
		<link rel="stylesheet" type="text/css" href="stylesheet.css" id="style-standard" />
	</head>
	<body epub:type="bodymatter chapter">
		<main class="main">
			<h1><?= $site->title(); ?></h1>
			<?= $page->text() ?>
		</main>
	</body>
</html>