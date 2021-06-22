<?php

/**
 * Get status icon for page 
 * 
 * Usage (in blueprint):
 * ...
 * image: 
 *		query: page.statusIcon("svg")
	* ...
	* 
	* @param {String} File extension for status icon
	* @return {Kirby\Cms\File}
	*/

return function($fileExt) {

	if(!$this->documentStatus()->exists()) {
		return $this->image();
	}

	$status = $this->documentStatus()->value();
	$statusIcon = $this->site()->image($status . '.' . $fileExt);
	
	return $statusIcon;
};