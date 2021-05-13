<?php snippet('html/content-type'); ?>
<html lang="<?= $kirby->language()->code(); ?>">
	<?php snippet('html/head'); ?>
	<body>
		<?php snippet('html/header'); ?>
		<main class="main">
			<?php 
				snippet('html/section', [
					'targetPages' => [$page], 
					'level' => 1
				]); 
			?>
		</main>
		<?php snippet('html/footer'); ?>
		<?php snippet('html/script'); ?>
	</body>
</html>