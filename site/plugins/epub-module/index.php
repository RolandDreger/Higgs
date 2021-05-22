<?php

load([
	'Higgs\\Epub\\XhtmlParser' => 'lib/xhtmlParser.php',
	'Higgs\\Epub\\EpubBuilder' => 'lib/epubBuilder.php'
], __DIR__);


Kirby::plugin('higgs/epub-module', [
	
	'options' => [
		'api-key' => 'default-api-key'
	],

	'snippets' => [
		'xhtml/content-type' => __DIR__ . '/snippets/content-type.php',
		'xhtml/head' => __DIR__ . '/snippets/head.php',
		'xhtml/sections' => __DIR__ . '/snippets/sections.php',
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

	'blueprints' => [
		'tabs/metadata' => __DIR__ . '/blueprints/tabs/metadata.yml',
		'tabs/epub' => __DIR__ . '/blueprints/tabs/epub.yml',
		'files/epub' => __DIR__ . '/blueprints/files/epub.yml',
		'files/css' => __DIR__ . '/blueprints/files/css.yml',
		'files/font' => __DIR__ . '/blueprints/files/font.yml',
		'files/cover' => __DIR__ . '/blueprints/files/cover.yml'
	],

	'fields' => [
		'epubExportButton' => __DIR__ . '/fields/epubExportButton.php'
	],

	/* Extensions */
	'fieldMethods' => include __DIR__ . '/extensions/fieldMethods.php',
	'pageMethods' => include __DIR__ . '/extensions/pageMethods.php',
	'fileMethods' => include __DIR__ . '/extensions/fileMethods.php'

]);