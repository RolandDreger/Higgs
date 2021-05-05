<?php

Kirby::plugin('higgs/panel-module', [

	/* 
		Translate field values with options in language files
		e.g. site/language/en.php
	*/
	'fieldMethods' => [
		'translate' => function ($field) {
			
			$value = $field->value;
			if(is_string($value)) {
				$field->value = t($value);
			} 
			
      return $field;
		}
	]

]);