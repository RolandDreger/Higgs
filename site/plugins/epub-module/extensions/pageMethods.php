<?php

return [

	/**
	 * Generating a hash string from page ID
	 * 
	 * Useage (in template): 
	 * <?= $page->hashID(); ?>
	 * 
	 * @return {String}
	 */
	'hashID' => function() {
		return 'x' . bin2hex(mhash(MHASH_MD5, $this->id()));
	},
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
	'statusIcon' => function($fileExt) {

		if(!$this->documentStatus()->exists()) {
			return $this->image();
		}

		$status = $this->documentStatus()->value();
		$statusIcon = $this->site()->image($status . '.' . $fileExt);
		
		return $statusIcon;
	}
];