<?php

/**
 * Generating valid XHTML nodes and documents from html strings.
 * 
 * DOCTYPE definitions are not allowed in input strings. (XXE attacks)
 * 
 * Usage:
 * $parser = new Higgs\Epub\XhtmlParser();
 * $xhtml	= $parser->createXhtmlString($html);
 * 
 * Options for saveXML (otpional):
 * LIBXML_NOEMPTYTAG		Expand empty tags (e.g. <br/> to <br></br>)
 * (Option separator: | )
 * 
 * @author Roland Dreger <roland.dreger@a1.net>
 */ 

namespace Higgs\Epub;

use DOMImplementation;
use DOMDocument;


class XhtmlParser {

	public $saveXMLOptions;
	public $errors = [];
	
	private $phpVersionID;
	private $formatOutput = false;

	public function __construct($options = 0) {

		/* PHP Version */
		$this->phpVersionID = $this->getPhpVersionID();

		/* Options */
		$this->saveXMLOptions = $options;
	}

	private function getPhpVersionID() {

		$versionArray = explode('.', PHP_VERSION);
		$phpVersionID = ($versionArray[0] * 10000 + $versionArray[1] * 100 + $versionArray[2]);

		return $phpVersionID;
	}


	protected function createHtmlDocument($html = '') {
		
		if(!is_string($html)) {
			array_push($this->errors, "First argument must be an string: {$html}");
			return null;
		}
		
		/* Check: Doctype */
		if($this->checkDoctype($html)) {
			array_push($this->errors, "Invalid XML: Detected use of illegal DOCTYPE");
			return null;
		}

		/* Defens Against XML Entity Expansion */
		if($this->phpVersionID < 80000) {
			$isEntityLoaderDisabledPrevValue = libxml_disable_entity_loader(true);
		}

		/* 
			Enables libxml errors handling  
			(HTML5 elements – e.g. main, footer, ... – trigger error messages)
		*/
		$internalErrorsOptionOldValue = libxml_use_internal_errors();
		libxml_use_internal_errors(true);
		
		$htmDocument = new DOMDocument();

		$htmDocument->xmlVersion = '1.0';
		$htmDocument->encoding = 'UTF-8';
		$htmDocument->formatOutput = $this->formatOutput;
		$htmDocument->preserveWhiteSpace = true;
		$htmDocument->strictErrorChecking = false;

		try {
			
			$htmDocument->loadHTML('<meta charset=\"UTF-8\"/>' . $html, LIBXML_NONET);
			
			$libxmlErrors = libxml_get_errors();
			$this->errors = array_merge($this->errors, $libxmlErrors);
			libxml_clear_errors();

		} catch(Exception $error) {

			$errorMessage = $error->getMessage();
			array_push($this->errors, "XSL transformation of content failed. Error: {$errorMessage}");

		} finally {

			libxml_use_internal_errors($internalErrorsOptionOldValue);
			if($this->phpVersionID < 80000) {
				libxml_disable_entity_loader($isEntityLoaderDisabledPrevValue);
			}

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
		$xhtmlDocument->formatOutput = $this->formatOutput;
		$xhtmlDocument->preserveWhiteSpace = true;
		$xhtmlDocument->strictErrorChecking = false;
		
		$xhtmlDocument->createAttributeNS('http://www.w3.org/1999/xhtml', 'xmlns');
		$xhtmlDocument->createAttributeNS('http://www.idpf.org/2007/ops', 'epub:type');
		
		/* HTML Element */
		$rootElement = $xhtmlDocument->documentElement;
		$rootElement->setAttribute('xml:lang', esc($lang, 'attr'));
		$rootElement->setAttribute('lang', esc($lang, 'attr'));

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

	protected function checkDoctype($xml, $isStrictCheck = true) {

		/* XML String */
		if(is_string($xml)) {
			$collapsedXml = preg_replace("/\s+/", '', $xml);
			if(preg_match("/<!DOCTYPE/i", $collapsedXml)) {
				if($isStrictCheck) {
					return true;
				} 
				else if(preg_match("/<!ENTITY/i", $collapsedXml)) {
					return true;
				}
			}
		} 
		/* DOM Document */
		else if(property_exists($xml, 'childNodes')) {
			foreach($xml->childNodes as $child) {
				if(!property_exists($child, 'nodeType')) {
					continue;
				}
				if($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
					if($isStrictCheck !== false) {
						return true;
					} 
					else if($child->entities->length > 0) {
						return true;
					}
				}
			}
		}

		return false;
	}
}