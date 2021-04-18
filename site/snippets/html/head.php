<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Page description (displayed on search result pages, ~ 155–160 chracters)">
	
	<title><?= $page->isHomePage() ? $site->title() : $page->title() . ' | ' . $site->title() ?></title>

	<meta property="og:site_name" content="<?= $site->title(); ?>">
	<meta property="og:title" content="<?= $page->title(); ?> | <?= $site->title(); ?>">
	<meta property="og:description" content="<?= $site->description(); ?>">
	<?php if($opengraphImage = $site->image($site->opengraphImage())): ?>
	<meta property="og:image" content="<?= $opengraphImage->url(); ?>">
	<meta property="og:image:alt" content="<?= $opengraphImage->alt(); ?>">
	<?php endif; ?>
	<meta property="og:locale" content="<?= $kirby->language()->locale(LC_ALL); ?>">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?= $page->url(); ?>">
	
	<meta name="twitter:card" content="<?= $site->twittercard(); ?>">
	<meta name="twitter:site" content="<?= $site->twittersite(); ?>">
	<meta name="twitter:creator" content="<?= $site->twittercreator(); ?>">

	<meta name="theme-color" content="#FF00FF">

	<link rel="canonical" href="<?= $page->url(); ?>">
	<link rel="icon" href="<?= url('/assets/icons/favicon.ico'); ?>"> 
	<link rel="icon" href="<?= url('/assets/icons/favicon.svg'); ?>" type="image/svg+xml">
	<link rel="apple-touch-icon" href="<?= url('/assets/icons/apple-touch-icon.png'); ?>">

	<?= css('assets/css/index.css'); ?>
	<?= css('@auto'); ?>
</head>