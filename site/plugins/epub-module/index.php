<?php

Kirby::plugin('higgs/epub-module', [
	
	'options' => [
		'api-key' => 'default-api-key'
	],

	'snippets' => [
		'xhtml/content-type' => __DIR__ . '/snippets/content-type.php',
		'xhtml/head' => __DIR__ . '/snippets/head.php',
		'xhtml/section' => __DIR__ . '/snippets/section.php'
	],
	
	'templates' => [
		'projects' => kirby()->root('templates') . '/default.php',
		'project' => kirby()->root('templates') . '/default.php',
		'document' => kirby()->root('templates') . '/default.php',
		'projects.xhtml' => __DIR__ . '/templates/projects.xhtml.php',
		'project.xhtml' => __DIR__ . '/templates/project.xhtml.php',
		'document.xhtml' => __DIR__ . '/templates/document.xhtml.php'
	],

	'controllers' => [
		'project' => include __DIR__ . '/controllers/project.php'
	],

	/* Extensions */
	'fieldMethods' => include __DIR__ . '/extensions/fieldMethods.php'

]);