<?php

namespace Higgs\Epub;

use DOMImplementation;
use DOMDocument;
use DOMXPath;
use ZipArchive;
use File;
use Remote;
use Xml;

/**
 * Usage:
 * $options = ["version" => '3.0', 'cover' => true];
 * $epubBuilder = new Higgs\Epub\EpubBuilder($page, $options);
 * $epubBuilder->createEpub();
 * 
 * Options:
 * 'parent': Export folder page (default projectPage)
 * 'name': ePub export name (default projectPage slug)
 * 'language': epub language
 * 'version': epub version
 * 'cover': Include cover page
 */

class EpubBuilder {

	const RELATIVE_TEMPLATE_PATH = 'assets/zip/template.epub';
	const CONTENT_FOLDER_PATH = '';
	const STYLESHEET_FOLDER_PATH = 'css';
	const FONT_FOLDER_PATH = 'fonts';
	const GRAPHIC_FOLDER_PATH = 'images';

	private $projectPage;
	private $docPages = [];
	private $tocPages = [];
	private $templatePath;
	private $hasCover = true;
	private $coverDocumentName = 'cover.xhtml';

	public $parentPage;
	public $epubName;
	public $epubVersion;
	public $lang;
	
	public $errors = [];


	public function __construct($projectPage, $options = []) {
		
		if(!$projectPage) {
			trigger_error("First parameter must be an page object.");
		}

		/* Source: Page with content documents */
		$this->projectPage = $projectPage;

		/* Destination: Export folder page  */
		$this->parentPage = $options['parent'] ?? $projectPage;
		
		/* Content Pages */
		$this->docPages = $projectPage->index();

		/* Table of Contents pages */
		if($tocPagesField = $projectPage->projectTableOfContent()) {
			if($tocPagesField->exists() && $tocPagesField->isNotEmpty()) {
				$tocPages = $projectPage->projectTableOfContent()->toPages();
			}
		}
		$this->tocPages = $tocPages ?? $this->docPages;
				
		/* ePub Name */
		$epubName = $options['name'] ?? '';
		$epubName = preg_replace('/\.epub$/', '', $epubName); 
		if(empty($epubName)) {
			$epubName = $projectPage->slug();
		}
		if(empty($epubName)) {
			trigger_error("ePub file name not valid: {$name}");
		}
		$this->epubName = $epubName . '.epub';
		
		/* Template Path */
		$templatePath = $projectPage->kirby()->roots()->plugins() . '/epub-module/' . self::RELATIVE_TEMPLATE_PATH;
		if(!file_exists($templatePath)) {
			trigger_error("Template file does not exists");
		}
		$this->templatePath = $templatePath;

		/* Settings for ePub files */
		$this->lang = $options['language'] ?? $projectPage->kirby()->language()->code();
		$this->epubVersion = $options['version'] ?? '3.0';
		$this->hasCover = $options['cover'] ?? true;
	}


	public function createEpub() {

		$epubName = $this->epubName;
		$projectPage = $this->projectPage;
		$templatePath = $this->templatePath;
		
		/* Delete ePub (if already exists) */
		if($epubFile = $projectPage->file($epubName)) {
			try {
				$epubFile->delete();
			} catch(Exception $err) {
				array_push($this->errors, "File could not be deleted: {$epubName} Error: {$err->getMessage()}");
				return $this;
			}
		}

		/* Create ePub (based on template) */
		$epubFile = File::create([
			'source' => $templatePath,
			'parent' => $projectPage,
			'filename' => $epubName,
			'template' => 'epub',
			'content' => [

			]
		]);

		$epubPath = $epubFile->root();

		try {
			
			/* Open ePub Archive */
			$epub = new ZipArchive();
			$isOpened = $epub->open($epubPath, ZIPARCHIVE::CHECKCONS);
			if(!$isOpened) {
				array_push($this->errors, 'Error opening ePub archive');
				return $this;
			}

			$xmlString = '';

			/* toc.xhtml */
			if($this->checkVersion(3)) {
				$tocXhtmlDoc = $this->createTocXhtmlDocument();
				if(!$tocXhtmlDoc) {
					array_push($this->errors, "Document could not be created: toc.xhtml");
					return $this;
				}
				$xmlString = $tocXhtmlDoc->saveXML();
				$epub->addFromString('OEBPS/toc.xhtml', $xmlString);
			}

			/* toc.ncx */
			$tocNcxDoc = $this->createTocNcxDocument();
			if(!$tocNcxDoc) {
				array_push($this->errors, "Document could not be created: toc.ncx");
				return $this;
			}
			$xmlString = $tocNcxDoc->saveXML();
			$epub->addFromString('OEBPS/toc.ncx', $xmlString);

			/* content.opf */
			$contentOpfDoc = $this->createContentOpfDocument();
			if(!$contentOpfDoc) {
				array_push($this->errors, "Document could not be created: content.opf");
				return $this;
			}
			$xmlString = $contentOpfDoc->saveXML();
			$epub->addFromString('OEBPS/content.opf', $xmlString);

			/* Content Documents */
			foreach($this->docPages as $page) {
				$docName = $this->getDocumentName($page);
				$pageUrl = $page->url() . '.xhtml';
				$content = '';
				$request = Remote::get($pageUrl);
				if($request->code() === 200) {
					$content = $request->content();
				} else {
					array_push($this->errors, "Document could not be created: {$docName} Code: {$request->code()}");
					continue;
				}
				$epub->addFromString('OEBPS/' . $docName, $content);
			}


		} catch(Exception $err) {
			
			array_push($this->errors, $err->getMessage());
			return $this;

		} finally {

			/* Close ePub Archive */
			$isClosed = $epub->close();
			if(!$isClosed) {
				array_push($this->errors, 'Error closing ePub archive.');
				return $this;
			}
		}

		return $this;
	}


	private function createContentOpfDocument() {

		$epubVersion = $this->epubVersion;
		$projectTitle = $this->projectPage->title();
		$projectCreator = 'Roland Dreger';
		$projectID = 'urn:isbn:9781234460001';
		$projectLanguage = $this->lang;
		$projectDate = strftime('%Y-%m-%dT%H:%M:%SZ');//'2021-04-01T10:22:53Z';
		
		/* Create XML Document */
		$doc = new DOMDocument();
		$doc->xmlVersion = '1.0';
		$doc->encoding = 'utf-8';
		$doc->formatOutput = true;
		$doc->preserveWhiteSpace = true;
		$doc->substituteEntities = true;
		$doc->strictErrorChecking = true;

		/* Root Element */
		$rootElement = $doc->createElementNS('http://www.idpf.org/2007/opf', 'package');
		$doc->appendChild($rootElement);
		$rootElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', 'http://purl.org/dc/elements/1.1/');
		$rootElement->setAttribute('unique-identifier', 'bookid');
		$rootElement->setAttribute('version', $epubVersion);

		/* Metadata */
		$metadataElement = $this->addElement($doc, $rootElement, 'metadata');
		$dcTitleElement = $this->addElement($doc, $metadataElement, 'dc:title', [['name'=>'id', 'value'=>'opf_title']], $projectTitle);
		$dcCreatorElement = $this->addElement($doc, $metadataElement, 'dc:creator', [['name'=>'id', 'value'=>'opf_author']], $projectCreator);
		$dcIdentifierElement = $this->addElement($doc, $metadataElement, 'dc:identifier', [['name'=>'id', 'value'=>'bookid']], $projectID);
		$dcLanguageElement = $this->addElement($doc, $metadataElement, 'dc:language', [], $projectLanguage);
		if($this->checkVersion(3)) {
			$metaModifiedElement = $this->addElement($doc, $metadataElement, 'meta', [['name' => 'property', 'value' => 'dcterms:modified']], $projectDate);
		}

		/* Manifest */
		$manifestElement = $this->addElement($doc, $rootElement, 'manifest');

		$this->addElement($doc, $manifestElement, 'item', [['name' => 'id', 'value' => 'ncx'], ['name' => 'href', 'value' => 'toc.ncx'],['name' => 'media-type', 'value' => 'application/x-dtbncx+xml']]);
		if($this->checkVersion(3)) {
			$this->addElement($doc, $manifestElement, 'item', [['name' => 'id', 'value' => 'nav'], ['name' => 'href', 'value' => 'toc.xhtml'],['name' => 'media-type', 'value' => 'application/xhtml+xml'],['name' => 'properties', 'value' => 'nav']]);
		}

		foreach($this->docPages as $page) {
			$idValue = $page->hashID();
			$hrefValue = $this->getDocumentPath($page);
			$this->addElement($doc, $manifestElement, 'item', [['name' => 'id', 'value' => $idValue], ['name' => 'href', 'value' => $hrefValue],['name' => 'media-type', 'value' => 'application/xhtml+xml']]);
		};

		/* Spine */
		$spineElement = $this->addElement($doc, $rootElement, 'spine', [['name'=>'toc', 'value'=>'ncx']]);

		foreach($this->tocPages as $page) {
			$idValue = $page->hashID();
			$this->addElement($doc, $spineElement, 'itemref', [['name' => 'idref', 'value' => $idValue]]);
		};

		/* Guide */
		if($this->checkVersion(2)) {
			$guideElement = $this->addElement($doc, $rootElement, 'guide');

			if($this->hasCover) {
				$coverHref = $this->buildFilePath(self::CONTENT_FOLDER_PATH, $this->coverDocumentName);
				$this->addElement(
					$doc, $guideElement, 'reference', [
					['name' => 'type', 'value' => 'cover'], 
					['name' => 'title', 'value' => 'Cover'],
					['name' => 'href', 'value' => $coverHref]
				]);
			}

			foreach($this->docPages as $page) {
				$documentLandmark = $page->documentLandmark();
				if($documentLandmark->exists() || $documentLandmark->isEmpty()) {
					continue;
				}
				$documentTitle = $page->title();
				$hrefValue = $this->getDocumentPath($page);
				$this->addElement(
					$doc, $guideElement, 'reference', [
					['name' => 'type', 'value' => $documentLandmark], 
					['name' => 'title', 'value' => $documentTitle],
					['name' => 'href', 'value' => $hrefValue]
				]);
			};
		}
		
		$doc->appendChild($rootElement);

		return $doc;
	}


	private function createTocXhtmlDocument() {

		$dom = new DOMImplementation();
		$dom->xmlVersion = '1.0';
		$dom->encoding = 'utf-8';

		$dtd = $dom->createDocumentType('html', '', '');

		/* Create XHTML Document */
		$doc = $dom->createDocument(null, 'html', $dtd);
		$doc->xmlVersion = '1.0';
		$doc->encoding = 'utf-8';
		$doc->formatOutput = true;
		$doc->preserveWhiteSpace = true;
		$doc->substituteEntities = true;
		$doc->strictErrorChecking = true;

		$doc->createAttributeNS('http://www.w3.org/1999/xhtml', 'xmlns');
		$doc->createAttributeNS('http://www.idpf.org/2007/ops', 'epub:type');

		/* xPath */
		$xPath = new DOMXPath($doc);

		/* HTML Element */
		$rootElement = $doc->documentElement;
		$rootElement->setAttribute('xml:lang', $this->lang);
		$rootElement->setAttribute('lang', $this->lang);

		/* HEAD Element */
		$headElement = $doc->createElement('head');
		$metaCharset = $doc->createElement('meta');
		$metaCharset->setAttribute('charset','utf-8');
		$headElement->appendChild($metaCharset);
		$rootElement->appendChild($headElement);

		/* Title */
		$projectTitle = $this->projectPage->title();
		if($projectTitle->exists() && $projectTitle->isNotEmpty()) {
			$titleElement = $doc->createElement('title');
			$titleText = $doc->createTextNode($projectTitle);
			$titleElement->appendChild($titleText);
			$headElement->appendChild($titleElement);
		}

		/* BODY Element */
		$bodyElement = $doc->createElement('body');
		$rootElement->appendChild($bodyElement);

		/* Body Element */
		$bodyElement = $doc->getElementsByTagName('body')->item(0);

		/* Project Title */
		$h1Element = $doc->createElement('h1');
		$h1TextNode = $doc->createTextNode($projectTitle);
		$h1Element->appendChild($h1TextNode);
		$bodyElement->appendChild($h1Element);

		/* Page Navigation */
		$pageNavElement = $doc->createElement('nav');
		$pageNavElement->setAttribute('id','toc');
		$pageNavElement->setAttribute('epub:type','toc');
		$pageNavElement->setAttribute('role','doc-toc');
		
		$tocOlLevel1Element = $this->createTocList($doc, 'ol', 1);
		$pageNavElement->appendChild($tocOlLevel1Element);
		$bodyElement->appendChild($pageNavElement);

		foreach($this->tocPages as $page) {
			
			$levelNum = $this->getLevelNumber($page);
			if(empty($levelNum)) {
				array_push($this->errors, "Level is not specified for the document: {$page->title()}");
				continue;
			}
			
			$hrefValue = $this->getDocumentPath($page) . '#' . $page->hashID();
			$pageTitle = $page->title();
			$liElement = $this->createTocListItem($doc, 'li', $levelNum, $hrefValue, $pageTitle);
						
			/* First level ol element */
			if($levelNum === 1) {
				$tocOlLevel1Element->appendChild($liElement);
				continue;
			}
			
			/* Last ol element of the same level */
			$xPathResults = $xPath->query("/html/body/nav[@id = 'toc']/ol/li[last()]/descendant-or-self::li[@data-level < {$levelNum}][last()]/descendant::ol[@data-level = {$levelNum}][last()]");
			$numOfNodes = $xPathResults->length;
			if($numOfNodes > 0) {
				$lastOlElement = $xPathResults->item($numOfNodes - 1);
				$lastOlElement->appendChild($liElement);
				continue;
			} 
			
			/* Last li element of a higher level */
			$xPathResults = $xPath->query("/html/body/nav[@id = 'toc']/ol/li[last()]/descendant-or-self::li[@data-level < {$levelNum}][last()]");
			$numOfNodes = $xPathResults->length;
			if($numOfNodes === 0) {
				continue;
			}
			
			$tocOlElement = $this->createTocList($doc, 'ol', $levelNum);
			$tocOlElement->appendChild($liElement);
			$lastLiElement = $xPathResults->item($numOfNodes - 1);
			$lastLiElement->appendChild($tocOlElement);
		}

		/* Clean-up: Remove helper attribute »data-level« */
		$xPathResults = $xPath->query("/html/body/nav[@id = 'toc']//*[@data-level]");
		foreach($xPathResults as $node) {
			$node->removeAttribute('data-level');
		}

		/* Landmark Navigation */
		$landmarkNav = $doc->createElement('nav');
		$landmarkNav->setAttribute('id','landmarks');
		$landmarkNav->setAttribute('epub:type','landmarks');
		
		$landmarkOl = $this->createLandmarkList($doc, 'ol');
		$landmarkNav->appendChild($landmarkOl);

		foreach($this->tocPages as $page) {
			$documentLandmark = $page->documentLandmark();
			if(!$documentLandmark->exists() || $documentLandmark->isEmpty()) {
				continue;
			}
			$hrefValue = $this->getDocumentPath($page) . '#' . $page->hashID();
			$textContent = t($documentLandmark->value(), $documentLandmark->value());
			$liElement = $this->createLandmarkListItem($doc, 'li', $documentLandmark, $hrefValue, $textContent);
			$landmarkOl->appendChild($liElement);
		}

		$bodyElement->appendChild($landmarkNav);

		return $doc;
	}


	private function createTocList($doc, $tagName, $levelNum) {
		$olElement = $doc->createElement($tagName);
		$olElement->setAttribute('class', "level-{$levelNum}");
		$olElement->setAttribute('data-level', $levelNum);
		return $olElement;
	}


	private function createTocListItem($doc, $tagName, $levelNum, $hrefValue, $pageTitle) {
		$liElement = $doc->createElement('li');
		$liElement->setAttribute('data-level',$levelNum);
		$aElement = $doc->createElement('a');
		$aElement->setAttribute('href', $hrefValue);
		$textNode = $doc->createTextNode($pageTitle);
		$aElement->appendChild($textNode);
		$liElement->appendChild($aElement);
		return $liElement;
	}


	private function createLandmarkList($doc, $tagName) {
		$olElement = $doc->createElement($tagName);
		return $olElement;
	}


	private function createLandmarkListItem($doc, $tagName, $documentLandmark, $hrefValue, $textContent) {
		$liElement = $doc->createElement('li');
		$aElement = $doc->createElement('a');
		$aElement->setAttribute('epub:type', $documentLandmark);
		$aElement->setAttribute('href', $hrefValue);
		$textNode = $doc->createTextNode($textContent);
		$aElement->appendChild($textNode);
		$liElement->appendChild($aElement);
		return $liElement;
	}


	private function createTocNcxDocument() {

		$projectTitle = $this->projectPage->title();
		$projectID = 'urn:isbn:9781234460001';
		$tocDepth = 1;
		$totalPageCount = 0;
		$maxPageNumber = 0;

		$dom = new DOMImplementation();
		$dom->xmlVersion = '1.0';
		$dom->encoding = 'utf-8';

		/* Create XHTML Document */
		$doc = $dom->createDocument(null, 'ncx');
		$doc->xmlVersion = '1.0';
		$doc->encoding = 'utf-8';
		$doc->formatOutput = true;
		$doc->preserveWhiteSpace = true;
		$doc->substituteEntities = true;
		$doc->strictErrorChecking = true;

		$doc->createAttributeNS('http://www.daisy.org/z3986/2005/ncx/', 'xmlns');

		/* xPath */
		$xPath = new DOMXPath($doc);

		/* Root Element */
		$rootElement = $doc->documentElement;
		$rootElement->setAttribute('xml:lang', $this->lang);
		$rootElement->setAttribute('dir', 'ltr');
		$rootElement->setAttribute('version', '2005-1');

		/* Head Element */
		$headElement = $this->addElement($doc, $rootElement, 'head');
		$metaUidElement = $this->addElement($doc, $headElement, 'meta', [['name'=>'name', 'value'=>'dtb:uid'],['name'=>'content', 'value'=>$projectID]]);
		$metaDepthElement = $this->addElement($doc, $headElement, 'meta', [['name'=>'name', 'value'=>'dtb:depth'],['name'=>'content', 'value'=>$tocDepth]]);
		$metaTotalPageCountElement = $this->addElement($doc, $headElement, 'meta', [['name'=>'name', 'value'=>'dtb:totalPageCount'],['name'=>'content', 'value'=>$totalPageCount]]);
		$metaMaxPageNumberElement = $this->addElement($doc, $headElement, 'meta', [['name'=>'name', 'value'=>'dtb:maxPageNumber'],['name'=>'content', 'value'=>$maxPageNumber]]);

		/* DocTitle Element */
		$docTitleElement = $this->addElement($doc, $rootElement, 'docTitle');
		$textElement = $this->addElement($doc, $docTitleElement, 'text', [], $projectTitle);

		/* NavMap Element */
		$navMapElement = $this->addElement($doc, $rootElement, 'navMap');
		
		$playOrder = 0;

		foreach($this->tocPages as $page) {

			$levelNum = $this->getLevelNumber($page);
			if(empty($levelNum)) {
				array_push($this->errors, "Level is not specified for the document: {$page->title()}");
				continue;
			}

			if($levelNum > $tocDepth) {
				$tocDepth = $levelNum;
			}

			$playOrder += 1;
			$idValue = $page->hashID();
			$pageTitle = $page->title();
			$documentPath = $this->getDocumentPath($page) . '#' . $idValue;
			$navPointElement = $this->createNavPointItem($doc, $idValue, $playOrder, $pageTitle, $documentPath);
			$navPointElement->setAttribute('data-level',$levelNum);

			if($levelNum === 1) {
				$navMapElement->appendchild($navPointElement);
				continue;
			}

			$xPathResults = $xPath->query("/ncx/navMap/descendant-or-self::navPoint");
			
			for($i = ($xPathResults->length)-1; $i>=0; $i--) {
				$node = $xPathResults->item($i);
				$levelValue = $node->getAttribute('data-level');
				if($levelValue < $levelNum) {
					$node->appendchild($navPointElement);
					break;
				}
			}
		};

		/* Clean-up: Remove helper attribute »data-level« */
		$xPathResults = $xPath->query("//navMap//*[@data-level]");
		foreach($xPathResults as $node) {
			$node->removeAttribute('data-level');
		}

		$metaDepthElement->setAttribute('content', $tocDepth);

		return $doc;
	}


	private function createNavPointItem($doc, $id, $playOrder, $text, $src) {
		
		$navPointElement = $doc->createElement('navPoint');
		$navPointElement->setAttribute('id', $id);
		$navPointElement->setAttribute('playOrder', $playOrder);
		$navLabelElement = $doc->createElement('navLabel');
		$textElement = $doc->createElement('text');
		$textTextNode = $doc->createTextNode($text);
		$textElement->appendChild($textTextNode);
		$navLabelElement->appendChild($textElement);
		$navPointElement->appendChild($navLabelElement);
		$contentElement = $doc->createElement('content');
		$contentElement->setAttribute('src', $src);
		$navPointElement->appendChild($contentElement);
		
		return $navPointElement;
	}


	private function getLevelNumber($page) {
		
		$levelNum = intval(preg_replace('/\D+/', '', $page->documentLevel()));
		if(!is_numeric($levelNum) || $levelNum < 1) {
			return;
		}

		return $levelNum;
	}


	private function getDocumentPath($page) {
		
		$contentFolderPath = self::CONTENT_FOLDER_PATH;
		$documentName = $this->getDocumentName($page);
		$documentPath = $this->buildFilePath($contentFolderPath, $documentName);
		
		return $documentPath;
	}


	private function getDocumentName($childPage) {
		
		$projectPagePathArray = explode('/', $this->projectPage->uri());
		$childPagePathArray = explode('/', $childPage->uri());
		
		$projectPageLevel = count($projectPagePathArray);
		$leveledChildPagePathArray = array_slice($childPagePathArray, $projectPageLevel);
		
		$documentName = implode('+', $leveledChildPagePathArray) . '.xhtml';
		
		return $documentName;
	}


	private function addElement($doc, $parentElement, $elementName, $attrArray = [], $textContent = '') {
		
		$element = $doc->createElement($elementName);
		foreach($attrArray as $attr) {
			$element->setAttribute($attr['name'], $attr['value']);
		}
		if(!empty($textContent)) {
			$textNode = $doc->createTextNode($textContent);
			$element->appendChild($textNode);
		}
		$parentElement->appendChild($element);

		return $element;
	}

	
	private function buildFilePath($folderPath = '', $fileName = '') {

		$folderPathArray = [];
		$folderPathArray = explode('/', $folderPath);
		
		$documentName = $fileName;
		array_push($folderPathArray, $documentName);

		$folderPathArray = array_filter($folderPathArray, function($item) { 
			return !empty($item); 
		});

		$filePath = implode('/', $folderPathArray);

		return $filePath;
	}


	private function checkVersion($versionNumber) {
		
		if(intval($this->epubVersion) === $versionNumber) {
			return true;
		}

		return false;
	}
}