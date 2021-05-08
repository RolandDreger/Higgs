<?php

Kirby::plugin('higgs/epub-module', [
	
	'options' => [
		'api-key' => 'default-api-key'
	],

	'snippets' => [
		'epub/content-type' => __DIR__ . '/snippets/epub/content-type.php',
		'epub/head' => __DIR__ . '/snippets/epub/head.php',
		'epub/section' => __DIR__ . '/snippets/epub/section.php'
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