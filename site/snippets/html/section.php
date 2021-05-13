<?php if(!isset($level)) { $level = 0; } ?>
<?php foreach($targetPages as $targetPage): ?>
	<section class="level-<?= $level; ?>">
		<?php /* Title */ ?>
		<header>
			<h1><a href="<?= $targetPage->url(); ?>"><?= $targetPage->title(); ?></a></h1>
		</header>
		<?php /* Content */ ?>
		<?php foreach($targetPage->documentText()->toBlocks() as $block): ?>
			<?= $block; ?>
		<?php endforeach ?>
		<?php /* Subpages */ ?>
		<?php 
			snippet('html/section', [
				'targetPages' => $targetPage->children()->published(), 
				'level' => ($level + 1)
			]); 
		?>
	</section>
<?php endforeach; ?>