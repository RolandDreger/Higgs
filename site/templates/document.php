<?php snippet('html/content-type'); ?>
<html lang="<?= $kirby->language()->locale(LC_ALL); ?>">
	<?php snippet('html/head'); ?>
	<body>
		<?php snippet('html/header'); ?>
		<main class="main">
			<?php snippet('html/section', ['targetPage' => $page]); ?>
		</main>
		<?php snippet('html/footer'); ?>
		<?php snippet('html/script'); ?>
	</body>
</html>