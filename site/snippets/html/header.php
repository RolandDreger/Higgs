<header class="header">
	<?php if($logo = $site->file('logo.svg')): ?>
		<a id="site-logo" href="<?php echo $site->url(); ?>" aria-label="Go to Homepage">
			<?php if($page->isHomePage()): ?>
			<?php echo $logo->read(); ?>
			<?php endif; ?>
		</a>
	<?php endif; ?>
</header>