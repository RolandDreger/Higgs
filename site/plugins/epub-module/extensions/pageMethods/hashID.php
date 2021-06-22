<?php

/**
 * Generating a hash string from page ID
 * 
 * Useage (in template): 
 * <?= $page->hashID(); ?>
 * 
 * @return {String}
 */

return function() {
	return 'x' . bin2hex(mhash(MHASH_MD5, $this->id()));
};