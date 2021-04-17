<!DOCTYPE html>
<html lang="en" class="no-js">
	<?php snippet('html/head'); ?>
	<body>
		<?php snippet('html/header'); ?>

		<main class="main">
			<h1><?= $site->title(); ?></h1>
			<p><?= $page->title(); ?></p>
		</main>
		
		<?php snippet('html/footer'); ?>
		<?php snippet('html/script'); ?>
	</body>
</html>