<?php

load([
	'Higgs\\Epub\\XhtmlParser' => 'lib/xhtmlParser.php',
	'Higgs\\Epub\\EpubBuilder' => 'lib/epubBuilder.php'
], __DIR__);


Kirby::plugin('higgs/epub-module', [
	
	'options' => include __DIR__ . '/config/options.php',

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
		'tabs/epub/metadata' => __DIR__ . '/blueprints/tabs/metadata.yml',
		'tabs/epub/settings' => __DIR__ . '/blueprints/tabs/settings.yml',
		'sections/epub/tableOfContents' => __DIR__ . '/blueprints/sections/tableOfContents.yml',
		'sections/epub/metadata/required' => __DIR__ . '/blueprints/sections/metadata/required.yml',
		'sections/epub/metadata/optional' => __DIR__ . '/blueprints/sections/metadata/optional.yml',
		'sections/epub/settings/document' => __DIR__ . '/blueprints/sections/settings/document.yml',
		'sections/epub/settings/general' => __DIR__ . '/blueprints/sections/settings/general.yml',
		'sections/epub/settings/cover' => __DIR__ . '/blueprints/sections/settings/cover.yml',
		'sections/epub/settings/images' => __DIR__ . '/blueprints/sections/settings/images.yml',
		'sections/epub/settings/additionalFiles' => __DIR__ . '/blueprints/sections/settings/additionalFiles.yml',
		'files/epub' => __DIR__ . '/blueprints/files/epub.yml',
		'files/css' => __DIR__ . '/blueprints/files/css.yml',
		'files/font' => __DIR__ . '/blueprints/files/font.yml',
		'files/cover' => __DIR__ . '/blueprints/files/cover.yml'
	],

	'fields' => [
		'epubExport' => __DIR__ . '/fields/epubExportField.php',
		'epubFileName' => __DIR__ . '/fields/epubFileNameField.php'
	],

	'fieldMethods' => include __DIR__ . '/extensions/fieldMethods.php',
	'pageMethods' => include __DIR__ . '/extensions/pageMethods.php',
	'fileMethods' => include __DIR__ . '/extensions/fileMethods.php'

]);