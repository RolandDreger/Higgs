<?php

return function($kirby, $site, $page) {

	$tableOfContentPages = $page->projecttableofcontent()->toPages();
	$childPages = $page->children();
	$listedChildPages = $childPages->listed();
	$unlistedChildPages = $childPages->unlisted();

	return [
		'tableOfContentPages'			=> $tableOfContentPages,
		'listedChildPages'		=> $listedChildPages,
		'unlistedChildPages'	=> $unlistedChildPages
	];
};