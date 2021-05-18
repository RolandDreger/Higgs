<?php

/**
 * Usage:
 * $parser = new Higgs\Epub\XhtmlParser(LIBXML_NSCLEAN);
 * $xhtml	= $parser->createXhtmlString($html);
 * 
 * Options for saveXML (otpional):
 * LIBXML_NOEMPTYTAG		Expand empty tags (e.g. <br/> to <br></br>)
 * (Option separator: | )
 */ 

namespace Higgs\Epub;

use DOMImplementation;
use DOMDocument;


class XhtmlParser {

	public $saveXMLOptions;
	public $errors = [];
	
	public function __construct($options = 0) {
		$this->saveXMLOptions = $options;
	}

	protected function getBodyNode($htmDocument) {

		/* Check: Document available? */
		if(empty($htmDocument)) {
			array_push($this->errors, 'First parameter must be a document object');
			return null;
		}

		/* Check: Document element available? */
		if(empty($documentElement = $htmDocument->documentElement)) {
			array_push($this->errors, 'Document element from HTML document not available');
			return null;
		}
	
		/* Check: Body element available? */
		$bodyNode = $documentElement->getElementsByTagName('body')->item(0);
		if(empty($bodyNode)) {
			array_push($this->errors, 'Body element from HTML document not available');
			return null;
		}

		return $bodyNode;
	}

	protected function createHtmlDocument($html = '') {
		
		if(!is_string($html)) {
			array_push($this->errors, 'First argument must be an string: ' . $html);
			return null;
		}
		
		/* 
			Enables libxml errors handling  
			(HTML5 elements – e.g. main, footer, ... – trigger error messages)
		*/
		$internalErrorsOptionValue = libxml_use_internal_errors();
		if($internalErrorsOptionValue === false) {
				libxml_use_internal_errors(true);
		}

		$htmDocument = new DOMDocument();

		$htmDocument->xmlVersion = '1.0';
		$htmDocument->encoding = 'UTF-8';
		$htmDocument->formatOutput = FALSE;
		$htmDocument->preserveWhiteSpace = TRUE;
		$htmDocument->substituteEntities = TRUE;
		$htmDocument->strictErrorChecking = FALSE;

		$htmDocument->loadHTML('<meta charset=\"UTF-8\"/>' . $html);

		$libxmlErrors = libxml_get_errors();
		$this->errors = array_merge($this->errors, $libxmlErrors);

		libxml_clear_errors();
	
		if($internalErrorsOptionValue === false) {
			libxml_use_internal_errors(false);
		}

		return $htmDocument;
	}

	public function createXhtmlDocument($html = '', $lang = 'en') {

		if(!is_string($html)) {
			array_push($this->errors, 'First argument must be an string: ' . $html);
			return null;
		}

		/* Create HTML document (for body content) */
		$htmDocument = $this->createHtmlDocument($html);
		if(empty($htmDocument)) {
			array_push($this->errors, 'HTML document could not be created');
			return null;
		}

		$dom = new DOMImplementation();
		$dom->xmlVersion = '1.0';
		$dom->encoding = 'UTF-8';

		$dtd = $dom->createDocumentType('html', '', '');

		$xhtmlDocument = $dom->createDocument(null, 'html', $dtd);
		$xhtmlDocument->xmlVersion = '1.0';
		$xhtmlDocument->encoding = 'UTF-8';
		$xhtmlDocument->formatOutput = FALSE;
		$xhtmlDocument->preserveWhiteSpace = TRUE;
		$xhtmlDocument->substituteEntities = TRUE;
		$xhtmlDocument->strictErrorChecking = FALSE;
		
		$xhtmlDocument->createAttributeNS('http://www.w3.org/1999/xhtml', 'xmlns');
		$xhtmlDocument->createAttributeNS('http://www.idpf.org/2007/ops', 'epub:type');
		
		/* HTML Element */
		$rootElement = $xhtmlDocument->documentElement;
		$rootElement->setAttribute('xml:lang', $lang);
		$rootElement->setAttribute('lang', $lang);

		/* HEAD Element */
		$headElement = $xhtmlDocument->createElement('head');
		$metaCharset = $xhtmlDocument->createElement('meta');
		$metaCharset->setAttribute('charset','UTF-8');
		$headElement->appendChild($metaCharset);
		$rootElement->appendChild($headElement);
		
		/* BODY Element */
		$bodyElement = $xhtmlDocument->createElement('body');
		$rootElement->appendChild($bodyElement);

		/* Check: Body element from HTML document available? */
		$bodyNode = $this->getBodyNode($htmDocument);
		if(empty($bodyNode)) {
			array_push($this->errors, 'Body element from HTML document not available');
			return $xhtmlDocument;
		}
		
		foreach($bodyNode->childNodes as $node) {
			$rootElement->appendChild(
				$xhtmlDocument->importNode($node, true)
			);
		}

		return $xhtmlDocument;
	}

	public function createXhtmlString($html = '') {
		
		if(!is_string($html)) {
			array_push($this->errors, 'First argument must be an string: ' . $html);
			return '';
		}

		/* Create HTML document (body content is same in XHTML document) */
		$htmDocument = $this->createHtmlDocument($html);

		/* Check: Body element available? */
		$bodyNode = $this->getBodyNode($htmDocument);
		if(empty($bodyNode)) {
			array_push($this->errors, 'Body element from HTML document not available');
			return '';
		}
		
		$xhtml = '';

		foreach($bodyNode->childNodes as $node) {
			$saveXMLResult = $htmDocument->saveXML($node, $this->saveXMLOptions);
			if(!is_string($saveXMLResult)) {
				continue;
			}
			$xhtml .= $saveXMLResult;
		}

		return $xhtml;
	}

	public function createXhtmlDocumentString($html = '') {

		if(!is_string($html)) {
			array_push($this->errors, 'First argument must be an string: ' . $html);
			return '';
		}

		/* Create XHTML document (for body content) */
		$xhtmlDocument = $this->createXhtmlDocument($html);
		$xhtml = $xhtmlDocument->saveXML(null, $this->saveXMLOptions);
		
		return $xhtml;
	}
}