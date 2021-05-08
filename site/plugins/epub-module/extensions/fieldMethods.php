<?php

return [
	/**
	 * Converts field value to valid XHTML
	 * 
	 * e.g.: 
	 * <?= $docText->toXhtml('blocks'); ?>
	 * <?= $docTitle->toXhtml('text', 'h1', ['epub:type' => 'title']); ?>
	 * 
	 * @param {Kirby\CMS\Field} $field
	 * If the text field has only plain text content, the enclosing tag and attributes can be specified:
	 * @param {String} $type Values: 'blocks', 'text', 'kirbytext'
	 * @param {String} $tag
	 * @param {Array} $attrArray
	 * @return {Kirby\CMS\Field}
	 */
	'toXhtml' => function ($field, $type = '', $tag = 'p', $attrArray = []) {
		
		$value = '<?xml encoding="utf-8" ?>';
		
		if(!empty($tag)) {
			$value .= '<' . $tag;
				if(!empty($attrArray)) {
					$value .= ' ' . Xml::attr($attrArray); 
				}
				$value .= '>';
		}
	
		switch($type) {
			case 'blocks':
				$value .= $field->toBlocks()->toHtml();
				break;
			case 'kirbytext':
				$value .= $field->kirbytext();
				break;
			default:
				$value .= $field->html();
		}
	
		if(!empty($tag)) {
			$value .= '</' . $tag . '>';
		}
	
		$xmlDoc = new DOMDocument();
		$xmlDoc->xmlVersion = '1.0';
		$xmlDoc->encoding = 'utf-8';
		$xmlDoc->formatOutput = FALSE;
		$xmlDoc->preserveWhiteSpace = TRUE;
		$xmlDoc->substituteEntities = TRUE;
		$xmlDoc->strictErrorChecking = FALSE;
	
		/* 
			Enables libxml errors handling  
			(HTML5 elements – e.g. main, footer, ... – trigger error messages)
		*/
		$internalErrorsOptionValue = libxml_use_internal_errors();
		if($internalErrorsOptionValue === false) {
				libxml_use_internal_errors(true);
		}
		
		$xmlDoc->loadHTML($value);
	
		$errors = libxml_get_errors();
		libxml_clear_errors();
	
		if ($internalErrorsOptionValue === false) {
			libxml_use_internal_errors(false);
		}
	
		/* Check: Body element available? */
		if(empty($xmlDoc->documentElement)) {
			return $field;
		}
	
		$bodyNode = $xmlDoc->documentElement->lastChild;
		if(empty($bodyNode)) {
			return $field;
		}
		
		$saveXMLValue = '';
		
		foreach($bodyNode->childNodes as $node) {
			/* 
				Options: 
				LIBXML_NOEMPTYTAG		Expand empty tags (e.g. <br/> to <br></br>)
				LIBXML_NSCLEAN			Remove excess namespace declarations
				LIBXML_NOXMLDECL		Drop the XML declaration when saving a document
				(Options separator is a »|«.)
			*/
			$saveXMLResult = $xmlDoc->saveXML($node, LIBXML_NSCLEAN);
			if(!is_string($saveXMLResult)) {
				continue;
			}
			$saveXMLValue .= $saveXMLResult;
		}

		$field->value = $saveXMLValue;
		
		return $field;
	}
];