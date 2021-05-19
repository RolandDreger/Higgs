<?php

return [

	/**
	 * Generating a hash string from page ID
	 * 
	 * Useage: 
	 * <?= $page->hashID(); ?>
	 */
	'hashID' => function() {
		return 'x' . bin2hex(mhash(MHASH_MD5, $this->id()));
	}
];