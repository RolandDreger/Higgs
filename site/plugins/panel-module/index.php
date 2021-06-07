<?php

Kirby::plugin('higgs/panel-module', [

	'blueprints' => [
		'blocks/paragraph' => __DIR__ . '/blueprints/blocks/paragraph.yml'
	],

	'snippets' => [
		'blocks/paragraph' => __DIR__ . '/snippets/blocks/paragraph.php'
	],
	
	'translations' => [
		'en' => include __DIR__ . '/translations/en.php',
		'de' => include __DIR__ . '/translations/de.php'
	]
]);