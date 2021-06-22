<?php

/* 
	* Translate field values with options in language files
	* site/language/en.php
	* 
	* e.g. for document level in Tabel of Contents field  
	* 
	* @param {Kirby\CMS\Field} $field
	* @returns {}
*/

return function ($field) {
		
	$value = $field->value();

	/* Check: Field value is a string? */
	if(!is_string($value)) {
		return $field;
	}
		
	/* Check: Comma separated string? */
	if(preg_match('/[,;]/', $value, $matches)) {
		$separator = $matches[0];
		$valueArray = explode($separator, $value);
		$resultArray = [];
		foreach($valueArray as $curValue) {
			array_push($resultArray, t(trim($curValue), $value));
		}
		$field->value = implode($separator, $resultArray);
	} else {
		$field->value = t($value, $value);
	}
	
	return $field;
};