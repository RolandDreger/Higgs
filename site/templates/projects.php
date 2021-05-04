<?php snippet('html/content-type'); ?>
<html lang="<?= $kirby->language()->locale(LC_ALL); ?>">
	<?php snippet('html/head'); ?>
	<body>
		<?php snippet('html/header'); ?>
		<main class="main">
			<h1><?= $site->title(); ?></h1>
			<h2>Projects</h2>
			<?php foreach($page->children() as $child): ?>
				<p><a href="<?= $child->url(); ?>"><?= $child->title(); ?></a></p>
			<?php endforeach; ?>
		</main>
		<?php snippet('html/footer'); ?>
		<?php snippet('html/script'); ?>
	</body>
</html>