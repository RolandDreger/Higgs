<?php

return function($kirby, $site, $page) {

	$childPages = $page->children();
	
	return [
		'listedChildPages'		=> $childPages->listed(),
		'unlistedChildPages'	=> $childPages->unlisted()
	];
};