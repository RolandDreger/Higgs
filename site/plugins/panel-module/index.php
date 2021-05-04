<?php

Kirby::plugin('higgs/panel-module', [

	/* 
		Translate field values with options in language files
		e.g. site/language/en.php
	*/
	'fieldMethods' => [
		'translate' => function ($field) {
			$field->value = t($field->value);
      return $field;
		}
	]

]);