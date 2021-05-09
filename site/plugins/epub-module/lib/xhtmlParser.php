<?php

/**
 * Options for saveXML:
 * LIBXML_NOEMPTYTAG		Expand empty tags (e.g. <br/> to <br></br>)
 * LIBXML_NSCLEAN			Remove excess namespace declarations
 * LIBXML_NOXMLDECL		Drop the XML declaration when saving a document
 * (Option separator: | )
 */ 

namespace Higgs\Epub;

use DOMImplementation;
use DOMDocument;

class XhtmlParser {

	private $source;

	public $htmlDoc;
	public $xhtmlDoc;
	public $errors;
	
	public function __construct() {
		$this->errors = [];
	}

	public function loadHTML($source) {
		
		$this->source = $source;

		if(!is_string($this->source)) {
			array_push($this->errors, 'First argument of loadHTML methode must be an string');
			$this->source = '';
		}
		
		/* 
			Enables libxml errors handling  
			(HTML5 elements – e.g. main, footer, ... – trigger error messages)
		*/
		$internalErrorsOptionValue = libxml_use_internal_errors();
		if($internalErrorsOptionValue === false) {
				libxml_use_internal_errors(true);
		}

		$this->htmDocument = new DOMDocument();

		$this->htmDocument->xmlVersion = '1.0';
		$this->htmDocument->encoding = 'utf-8';
		$this->htmDocument->formatOutput = FALSE;
		$this->htmDocument->preserveWhiteSpace = TRUE;
		$this->htmDocument->substituteEntities = TRUE;
		$this->htmDocument->strictErrorChecking = FALSE;

		$this->htmDocument->loadHTML("<meta charset=\"utf-8\"/>" . $this->source);

		$libxmlErrors = libxml_get_errors();
		$this->errors = array_merge($this->errors, $libxmlErrors);

		libxml_clear_errors();
	
		if($internalErrorsOptionValue === false) {
			libxml_use_internal_errors(false);
		}

		return $this;
	}

	public function getHtmlBodyNode() {

		/* Check: Document available? */
		if(!isset($this->htmDocument) || empty($this->htmDocument)) {
			array_push($this->errors, 'HTML document not available');
			return null;
		}

		/* Check: Document element available? */
		if(empty($documentElement = $this->htmDocument->documentElement)) {
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

	public function getBodyContentAsString() {
	
		/* Check: Body element available? */
		$bodyNode = $this->getHtmlBodyNode();
		if(empty($bodyNode)) {
			array_push($this->errors, 'Body element from HTML document not available');
			return '';
		}
		
		$xhtml = '';

		foreach($bodyNode->childNodes as $node) {
			$saveXMLResult = $this->htmDocument->saveXML($node, LIBXML_NSCLEAN);
			if(!is_string($saveXMLResult)) {
				continue;
			}
			$xhtml .= $saveXMLResult;
		}

		return $xhtml;
	}

	public function getDocumentAsString() {

		$dom = new DOMImplementation();
		$dom->encoding = 'utf-8';
		$dom->standalone = false;

		$dtd = $dom->createDocumentType('html', '', '');

		$this->xhtmlDocument = $dom->createDocument(null, 'html', $dtd);
		$this->xhtmlDocument->xmlVersion = '1.0';
		$this->xhtmlDocument->encoding = 'utf-8';
		$this->xhtmlDocument->formatOutput = FALSE;
		$this->xhtmlDocument->preserveWhiteSpace = TRUE;
		$this->xhtmlDocument->substituteEntities = TRUE;
		$this->xhtmlDocument->strictErrorChecking = FALSE;
		
		/* HTML Element */
		$root = $this->xhtmlDocument->documentElement;
		$root->setAttributeNS('', 'xmlns','http://www.w3.org/1999/xhtml');
		
		/* HEAD Element */
		$head = $this->xhtmlDocument->createElement('head');
		$metaCharset = $this->xhtmlDocument->createElement('meta');
		$metaCharset->setAttribute('charset','utf-8');
		$head->appendChild($metaCharset);
		$root->appendChild($head);
		
		/* BODY Element */
		$body = $this->xhtmlDocument->createElement('body');
		$root->appendChild($body);

		/* Check: Body element from HTML document available? */
		$bodyNode = $this->getHtmlBodyNode();
		if(empty($bodyNode)) {
			array_push($this->errors, 'Body element from HTML document not available');
			return $this->xhtmlDocument;
		}
		
		foreach($bodyNode->childNodes as $node) {
			$root->appendChild(
				$this->xhtmlDocument->importNode($node, true)
			);
		}

		$xhtml = $this->xhtmlDocument->saveXML(null, LIBXML_NSCLEAN);
		
		return $xhtml;
	}
}