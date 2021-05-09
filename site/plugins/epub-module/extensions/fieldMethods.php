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
		$xhtml = $parser->loadHTML($html)->getBodyContentAsString();
		
		$field->value = $xhtml;
		
		return $field;
	}
];