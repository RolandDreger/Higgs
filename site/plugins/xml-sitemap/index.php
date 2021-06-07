<?php

Kirby::plugin('higgs/xml-sitemap', [

	'options' => include __DIR__ . '/config/options.php',
	
	'routes' => include __DIR__ . '/config/routes.php',

	'snippets' => [
		'sitemap' => __DIR__ . '/snippets/sitemap.php'
	]

]);