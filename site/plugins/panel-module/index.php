<?php

Kirby::plugin('higgs/panel-module', [
	'blueprints' => [
		'blocks/paragraph' => __DIR__ . '/blueprints/blocks/paragraph.yml'
	],
	'snippets' => [
		'blocks/paragraph' => __DIR__ . '/snippets/blocks/paragraph.php'
	],
	'translations' => [
		'en' => [
			'field.blocks.paragraph.name' => 'Paragraph'
		],
		'de' => [
			'field.blocks.paragraph.name' => 'Absatz'
		]
	]
]);