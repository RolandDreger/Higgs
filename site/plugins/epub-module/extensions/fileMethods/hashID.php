<?php

/**
 * Generating a hash string from file ID
 * 
 * e.g.: 
 * <?= $file->hashID(); ?>
 */

return function() {
	return 'x' . bin2hex(mhash(MHASH_MD5, $this->id()));
};