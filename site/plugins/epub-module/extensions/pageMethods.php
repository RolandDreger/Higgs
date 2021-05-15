<?php

return [
	/**
	 * Generating a hash string from page ID
	 * 
	 * e.g.: 
	 * <?= $page->hashID(); ?>
	 */
	'hashID' => function() {
		return bin2hex(mhash(MHASH_MD5, $this->id()));
	}
];