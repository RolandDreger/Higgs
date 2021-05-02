<?php

/* 
	load([
		'higgs\\epub-module\epub-class' => 'scr/EPubClass.php' muss dann auch in composer.json eingtragen werden
	]);
*/

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
	]

]);