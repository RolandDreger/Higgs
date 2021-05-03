<?php snippet('html/content-type'); ?>
<html lang="<?= $kirby->language()->locale(LC_ALL); ?>">
	<?php snippet('html/head'); ?>
	<body>
		<?php snippet('html/header'); ?>
		<main class="main">
			<?php foreach($page->children() as $section): ?>
				<?php snippet('html/section', ['section' => $section]); ?>
			<?php endforeach; ?>
		</main>
		<?php snippet('html/footer'); ?>
		<?php snippet('html/script'); ?>
	</body>
</html>