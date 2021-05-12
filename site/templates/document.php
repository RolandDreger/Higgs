<?php snippet('html/content-type'); ?>
<html lang="<?= $kirby->language()->code(); ?>">
	<?php snippet('html/head'); ?>
	<body>
		<?php snippet('html/header'); ?>
		<main class="main">
			<section class="level-1">
				<?php snippet('html/section', ['targetPage' => $page, 'level' => 1]); ?>
				<?php foreach($page->children()->published() as $publishedPageLevel2): ?>
					<section class="level-2">
						<?php snippet('html/section', ['targetPage' => $publishedPageLevel2, 'level' => 2]); ?>
						<?php foreach($publishedPageLevel2->children()->published() as $publishedPageLevel3): ?>
							<section class="level-3">
								<?php snippet('html/section', ['targetPage' => $publishedPageLevel3, 'level' => 3]); ?>
							</section>
						<?php endforeach; ?>
					</section>
				<?php endforeach; ?>
			</section>
		</main>
		<?php snippet('html/footer'); ?>
		<?php snippet('html/script'); ?>
	</body>
</html>