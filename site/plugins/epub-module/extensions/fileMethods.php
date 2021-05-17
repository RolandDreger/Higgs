<?php

return [
	/**
	 * Generating a hash string from file ID
	 * 
	 * e.g.: 
	 * <?= $file->hashID(); ?>
	 */
	'hashID' => function() {
		return 'x' . bin2hex(mhash(MHASH_MD5, $this->id()));
	}
];