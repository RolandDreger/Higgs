<?php

Kirby::plugin('higgs/panel-module', [

	
	'fieldMethods' => [

		/* 
			Translate field values with options in language files
			e.g. site/language/en.php
		*/
		'translate' => function ($field) {
			
			$value = $field->value();

			if(is_string($value)) {
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
			} 
			
      return $field;
		}
	]

]);