<header class="header">
	<?php if($logo = $site->file('logo.svg')): ?>
		<img id="site-logo" src="<?php echo $logo->url(); ?>" />
	<?php endif; ?>
</header>