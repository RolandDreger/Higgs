<?php

return [
	[
		'pattern' => 'sitemap.xml',
		'action' => function() {

			$pages = site()->pages()->index();
			$ignore = option('higgs.xml-sitemap.ignore');
			$content = snippet('sitemap', compact('pages', 'ignore'), true);

			return new Kirby\Cms\Response($content, 'application/xml');
		}
	],
	[
		'pattern' => 'sitemap',
		'action' => function() {
			return go('sitemap.xml', 301);
		}
	]
];