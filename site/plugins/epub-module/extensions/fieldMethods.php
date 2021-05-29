<?php

return [
	/**
	 * Converts field value to valid XHTML
	 * 
	 * e.g.: 
	 * <?= $docText->toXhtml('blocks'); ?>
	 * <?= $docTitle->toXhtml('text', 'h1', ['epub:type' => 'title']); ?>
	 * 
	 * @param {Kirby\Cms\Field} $field
	 * If the text field has only plain text content, the enclosing tag and attributes can be specified:
	 * @param {String} $type Values: 'blocks', 'text', 'kirbytext'
	 * @param {String} $tag
	 * @param {Array} $attrArray
	 * @return {Kirby\Cms\Field}
	 */
	'toXhtml' => function ($field, $type = '', $tag = '', $attrArray = []) {
		
		$attr = empty($attrArray) ? "" : " " . Xml::attr($attrArray);
		$startTag = empty($tag) ? "" : "<{$tag}{$attr}>";
		$endTag = empty($tag) ? "" : "</{$tag}>";

		$html = "";

		switch($type) {
			case 'blocks':
				$html = $field->toBlocks()->toHtml();
				break;
			case 'kirbytext':
				$html = $field->kirbytext();
				break;
			default:
				$html = $field->html();
		}
	
		$html = "{$startTag}{$html}{$endTag}";

		$parser = new Higgs\Epub\XhtmlParser();
		$xhtml = $parser->createXhtmlString($html);
		
		$field->value = $xhtml;
		
		return $field;
	},
	/* 
		* Translate field values with options in language files
		* site/language/en.php
		* 
		* e.g. for document level in Tabel of Contents field  
		* 
		* @param {Kirby\CMS\Field} $field
		* @returns {}
	*/
	'translate' => function ($field) {
		
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
	}
];