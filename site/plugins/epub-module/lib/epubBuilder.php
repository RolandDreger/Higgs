<?php

namespace Higgs\Epub;

use DOMImplementation;
use DOMDocument;
use DOMXPath;
use ZipArchive;
use File;
use Remote;
use Xml;
use XSLTProcessor;


/**
 * Usage:
 * $options = [];
 * $epubBuilder = new Higgs\Epub\EpubBuilder($page, $options);
 * $epubBuilder->createEpub();
 * 
 * Options:
 * 'parent': Export folder page (default projectPage)
 */

class EpubBuilder {

	const RELATIVE_TEMPLATE_FILE_PATH = 'assets/zip/template.epub';
	const RELATIVE_XSL_FILE_EPUB2_PATH = 'assets/xslt/content-epub-2.xsl';
	const RELATIVE_XSL_FILE_EPUB3_PATH = 'assets/xslt/content-epub-3.xsl';
	const OPS_FOLDER_NAME = 'OEBPS';
	const CONTENT_FOLDER_PATH = ''; /* Do not change! */
	const STYLESHEET_FOLDER_PATH = 'css';
	const FONT_FOLDER_PATH = 'fonts';
	const GRAPHIC_FOLDER_PATH = 'images';
	const COVER_DOCUMENT_TITLE = 'Cover';
	const COVER_DOCUMENT_NAME = 'cover.xhtml';

	private $projectPage;
	private $formatOutput;
	private $docPages = [];
	private $tocPages = [];
	private $cssFiles = [];
	private $fontFiles = [];
	private $templateFilePath;
	private $xslFilePath;
	private $hasCover;
	private $coverFile;
	private $coverMaxWidth;
	private $coverMaxHeight;
	private $coverQuality;
	private $imageMaxWidth;
	private $imageMaxHeight;
	private $imageQuality;
	
	public $parentPage;
	public $epubName;
	public $epubVersion;
	public $epubLang;
	public $projectDate;
	public $projectTitle;
	
	public $metadataTitle;
	public $metadataID;
	public $metadataCreator;
	public $metadataIDIsISBN;
	public $metadataRights;
	public $metadataContributor;
	public $metadataDate;
	public $medadataDescription;

	public $errors = [];


	public function __construct($projectPage, $options = []) {
		
		if(!$projectPage) {
			trigger_error("First parameter must be an page object.");
		}

		/* ePub Version */
		$epubVersion = $projectPage->epubVersion();
		if($epubVersion->exists() && $epubVersion->isNotEmpty()) {
			$this->epubVersion = $epubVersion->value();
		} else {
			$this->epubVersion = '3.0';
		}

		/* Template Path */
		$this->templateFilePath = $projectPage->kirby()->roots()->plugins() . '/epub-module/' . self::RELATIVE_TEMPLATE_FILE_PATH;
		if(!file_exists($this->templateFilePath)) {
			trigger_error("Template file does not exists");
		}

		/* XSL Path */
		if($this->checkVersion(2)) {
			$this->xslFilePath = $projectPage->kirby()->roots()->plugins() . '/epub-module/' . self::RELATIVE_XSL_FILE_EPUB2_PATH;
		} 
		elseif ($this->checkVersion(3)) {
			$this->xslFilePath = $projectPage->kirby()->roots()->plugins() . '/epub-module/' . self::RELATIVE_XSL_FILE_EPUB3_PATH;
		}
		if(!file_exists($this->xslFilePath)) {
			trigger_error("XSL file does not exists");
		}

		/* Source: Page with content documents */
		$this->projectPage = $projectPage;

		/* OPTIONS */
		/* Destination: Export folder page  */
		$this->parentPage = $options['parent'] ?? $projectPage;
		$optionFormatOutput = $options['formatOutput'];
		if(empty($optionFormatOutput) || !is_bool($optionFormatOutput)) {
			$this->formatOutput = false;
		} else {
			$this->formatOutput = $optionFormatOutput;
		}
		
		/* Content Pages (all descendants) */
		$this->docPages = $projectPage->index();

		/* Table of Contents pages */
		$tocPagesField = $projectPage->projectTableOfContent();
		if($tocPagesField->exists() && $tocPagesField->isNotEmpty()) {
			$this->tocPages = $tocPagesField->toPages();
		}
				
		/* ePub Name */
		$epubName = '';
		$epubNameField = $projectPage->epubName();
		if($epubNameField->exists() && $epubNameField->isNotEmpty()) {
			$epubName = preg_replace('/\.epub$/', '', $epubNameField->value());
		} else {
			$epubName = $projectPage->slug();
		}
		$this->epubName = $epubName . '.epub';
		
		/* CSS Files */
		$cssFilesField = $projectPage->epubCSSFiles();
		if($cssFilesField->exists() && $cssFilesField->isNotEmpty()) {
			$this->cssFiles = $cssFilesField->toFiles();
		}
		
		/* Font Files */
		$fontFilesField = $projectPage->epubFontFiles();
		if($fontFilesField->exists() && $fontFilesField->isNotEmpty()) {
			$this->fontFiles = $fontFilesField->toFiles();
		}
		
		/* Cover Image Settings */
		$coverWidthField = $projectPage->epubCoverWidth();
		if($coverWidthField->exists() && $coverWidthField->isNotEmpty()) {
			$this->coverMaxWidth = $coverWidthField->toInt();
		}
		$coverHeightField = $projectPage->epubCoverHeight();
		if($coverHeightField->exists() && $coverHeightField->isNotEmpty()) {
			$this->coverMaxHeight = $coverHeightField->toInt();
		}
		$coverQualityFild = $projectPage->epubCoverQuality();
		if($coverQualityFild->exists() && $coverQualityFild->isNotEmpty()) {
			$this->coverQuality = $coverQualityFild->value();
		}

		/* Content Image Settings */
		$imageWidthField = $projectPage->epubImageWidth();
		if($imageWidthField->exists() && $imageWidthField->isNotEmpty()) {
			$this->imageMaxWidth = $imageWidthField->toInt();
		}
		$imageHeightField = $projectPage->epubImageHeight();
		if($imageHeightField->exists() && $imageHeightField->isNotEmpty()) {
			$this->imageMaxHeight = $imageHeightField->toInt();
		}
		$imageQualityField = $projectPage->epubImageQuality();
		if($imageQualityField->exists() && $imageQualityField->isNotEmpty()) {
			$this->imageQuality = $imageQualityField->value();
		}
		
		/* Cover File */
		$hasCoverField = $projectPage->epubHasCoverImage();
		if($hasCoverField->exists() && $hasCoverField->value() === 'true') {
			$this->hasCover = true;
		} else {
			$this->hasCover = false;
		}
		$coverImageField = $projectPage->epubCoverImageFile();
		if($coverImageField->exists() && $coverImageField->isNotEmpty()) {
			$this->coverFile = $coverImageField->toFiles()->first();
		}
		
		/* ePub Language  */
		$epubLanguage = $projectPage->epubLanguage();
		if($epubLanguage->exists() && $epubLanguage->isNotEmpty()) {
			$this->epubLang = $epubLanguage->value();
		} else {
			$this->epubLang = 'en';
		}

		/* Metadata */
		
		$this->metadataTitle = $projectPage->metadataTitle()->value(); 
		if(empty($this->metadataTitle)) {
			$this->metadataTitle = $projectPage->title();
		}
		$this->metadataID = $projectPage->metadataID()->value();
		$this->metadataCreator = $projectPage->metadataCreator()->value();
		$metadataIDIsISBNField = $projectPage->metadataIDIsISBN();
		if($metadataIDIsISBNField->exists()) {
			if($metadataIDIsISBNField->value() === 'true') {
				$this->metadataIDIsISBN = true;
			} else {
				$this->metadataIDIsISBN = false;
			}
		}
		$this->metadataRights = $projectPage->metadataRights()->value();
		$this->metadataContributor = $projectPage->metadataContributor()->value();
		$this->metadataDate = $projectPage->metadataDate()->value();
		$this->medadataDescription = $projectPage->medadataDescription()->value();
	}

	/**
	 * Create ePub Archive
	 * 
	 * 1. Copies template ePub to project page (or page from options)
	 * 2. Transforms the content documents
	 * 3. Fills the ePub ZIP archiv with files (ToC, OPF, content documents, images, ...) 
	 * 
	 * @return $this
	 */
	public function createEpub() {

		$this->projectDate = strftime('%Y-%m-%dT%H:%M:%SZ'); /* '2021-04-01T10:22:53Z' */

		$epubName = $this->epubName;
		$projectPage = $this->projectPage;
		
		/* Delete ePub (if already exists) */
		$epubFile = $projectPage->file($epubName);
		if($epubFile !== null && $epubFile->exists()) {
			$isDeleted = $epubFile->delete();
			if(!$isDeleted) {
				array_push($this->errors, "File could not be deleted: {$epubName}");
				return $this;
			}
		}
		
		/* Create ePub (based on template) */
		$epubFile = $projectPage->file($epubName);
		if(!$epubFile || !$epubFile->exists()) {
			$templateFilePath = $this->templateFilePath;
			$epubFile = File::create([
				'source' => $templateFilePath,
				'parent' => $projectPage,
				'filename' => $epubName,
				'template' => 'epub',
				'content' => []
			]);
			if(!$epubFile->exists()) {
				array_push($this->errors, "File could not be created: {$epubName}");
				return $this;
			}
		}

		try {
			
			/* Open ePub archive */
			$epub = new ZipArchive();
			$epubPath = $epubFile->realpath();
			$isOpened = $epub->open($epubPath, ZIPARCHIVE::CHECKCONS);
			if(!$isOpened) {
				array_push($this->errors, "Error opening ePub archive: {$epubPath}");
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
				$tocXhtmlArchivePath = $this->buildFilePath('', 'toc.xhtml', 'ops');
				$epub->addFromString($tocXhtmlArchivePath, $xmlString);
			}

			/* toc.ncx */
			$tocNcxDoc = $this->createTocNcxDocument();
			if(!$tocNcxDoc) {
				array_push($this->errors, "Document could not be created: toc.ncx");
				return $this;
			}
			$xmlString = $tocNcxDoc->saveXML();
			$tocNcxArchivePath = $this->buildFilePath('', 'toc.ncx', 'ops');
			$epub->addFromString($tocNcxArchivePath, $xmlString);

			/* content.opf */
			$contentOpfDoc = $this->createContentOpfDocument();
			if(!$contentOpfDoc) {
				array_push($this->errors, "Document could not be created: content.opf");
				return $this;
			}
			$xmlString = $contentOpfDoc->saveXML();
			$contentOpfArchivePath = $this->buildFilePath('', 'content.opf', 'ops');
			$epub->addFromString($contentOpfArchivePath, $xmlString);

			/* Cover */
			if($this->hasCover) {
				$coverFile = $this->coverFile;
				if(empty($coverFile) || !$coverFile->exists()) {
					array_push($this->errors, "Cover file does not exists");
				} else {
					/* cover.xhtml */
					$coverDocFileName = self::COVER_DOCUMENT_NAME;
					$coverDoc = $this->createCoverDocument();
					if(!$coverDoc) {
						array_push($this->errors, "Document could not be created: {$coverDocFileName}");
					} else {
						$xmlString = $coverDoc->saveXML();
						$xmlString = $this->transformContent($xmlString);
						$coverOpfArchivePath = $this->buildFilePath('', $coverDocFileName, 'ops');
						$epub->addFromString($coverOpfArchivePath, $xmlString);
					}
					/* cover.jpg */
					$coverFileName = $coverFile->filename();
					$resizedCover = $coverFile->resize($this->coverMaxWidth, $this->coverMaxHeight, $this->coverQuality);
					$coverRealPath = $resizedCover->realpath();
					$coverArchivePath = $this->buildFilePath(self::GRAPHIC_FOLDER_PATH, $coverFileName, 'ops');
					$epub->addFile($coverRealPath, $coverArchivePath);
				}
			}

			/* Content Documents */
			foreach($this->docPages as $page) {
				$docFileName = $this->getDocumentName($page);
				$pageUrl = $page->url() . '.xhtml';
				$content = '';
				$request = Remote::get($pageUrl);
				if($request->code() === 200) {
					$content = $request->content();
				} else {
					array_push($this->errors, "Document could not be created: {$docFileName} Code: {$request->code()}");
					continue;
				}
				/* XSL Transformation of Content */
				$content = $this->transformContent($content);
				$docArchivePath = $this->buildFilePath('', $docFileName, 'ops');
				$epub->addFromString($docArchivePath, $content);
				/* Graphic Files */
				$graphicFiles = $page->files()->template('blocks/image');
				foreach($graphicFiles as $graphic) {
					$resizedGraphic = $graphic->resize($this->imageMaxWidth, $this->imageMaxHeight, $this->imageQuality);
					$graphicRealPath = $resizedGraphic->realpath();
					$graphicFileName = $graphic->filename();
					$graphicArchivePath = $this->buildFilePath(self::GRAPHIC_FOLDER_PATH, $graphicFileName, 'ops');
					$epub->addFile($graphicRealPath, $graphicArchivePath);
				}
			}

			/* CSS Files */
			foreach($this->cssFiles as $css) {
				$cssRealPath = $css->realpath();
				$cssFileName = $css->filename();
				$cssArchivePath = $this->buildFilePath(self::STYLESHEET_FOLDER_PATH, $cssFileName, 'ops');
				$epub->addFile($cssRealPath, $cssArchivePath);
			}

			/* Font Files */
			foreach($this->fontFiles as $font) {
				$fontRealPath = $font->realpath();
				$fontFileName = $font->filename();
				$fontArchivePath = $this->buildFilePath(self::FONT_FOLDER_PATH, $fontFileName, 'ops');
				$epub->addFile($fontRealPath, $fontArchivePath);
			}

		} catch(Exception $error) {
			array_push($this->errors, $error->getMessage());
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
	}	 /* END function createEpub */

	/**
	 * XSL Transformation of content
	 * (XSL files for ePub version 2 und 3)
	 * 
	 * ePub Version 2: 
	 * - Replaces stylesheet paths
	 * - Replaces image source paths 
	 * - Replaces not allowed elements (header, footer, ...)
   * - Replaces not allowed attributes (role, epub:type, ...)
	 * 
	 * * ePub Version 3: 
	 * - Replaces stylesheet paths
	 * - Replaces image source paths
	 * 
	 * @param {String} $content
	 * @return {String}
	 */
	private function transformContent($content) {

		$xslFilePath = $this->xslFilePath;

		$xmlDoc = new DOMDocument();
		$xslDoc = new DOMDocument();

		$xslProcessor = new XSLTProcessor();
		$xslProcessor->setParameter('','css-folder-path', self::STYLESHEET_FOLDER_PATH);
		$xslProcessor->setParameter('','image-folder-path', self::GRAPHIC_FOLDER_PATH);

		$isLoaded = $xmlDoc->loadXML($content, LIBXML_PARSEHUGE);
		if(!$isLoaded) {
			array_push($this->errors, "loadXML error: Could not load the given XML string");
			return $content;
		}
		$isLoaded = $xslDoc->load($xslFilePath);
		if(!$isLoaded) {
			array_push($this->errors, "load error: Could not load the XSL transformation file");
			return $content;
		}

		libxml_use_internal_errors(true);

		try {
			
			$wasImportOK = $xslProcessor->importStyleSheet($xslDoc);
			if(!$wasImportOK) {
				foreach(libxml_get_errors() as $error) {
					$errorMessage = $error->message;
					array_push($this->errors, "Libxml error: {$errorMessage}");
				}
				libxml_clear_errors();
				return $content;
			} 
			
			$transformationResult = $xslProcessor->transformToXML($xmlDoc);
			if(!$transformationResult) {
				foreach(libxml_get_errors() as $error) {
					$errorMessage = $error->message;
					array_push($this->errors, "Libxml error: {$errorMessage}");
				}
				libxml_clear_errors();
				return $content;
			}
			
		} catch(Exception $error) {
			$errorMessage = $error->getMessage();
			array_push($this->errors, "XSL transformation of content failed. Error: {$errorMessage}");
		} finally {
			libxml_use_internal_errors(false);
		}
		
		return $transformationResult;
	} /* END function transformContent */


	private function createCoverDocument() {

		$dom = new DOMImplementation();
		$dom->xmlVersion = '1.0';
		$dom->encoding = 'UTF-8';

		$dtd = $dom->createDocumentType('html', '', '');

		/* Create XHTML Document */
		$doc = $dom->createDocument(null, 'html', $dtd);
		$doc->xmlVersion = '1.0';
		$doc->encoding = 'UTF-8';
		$doc->formatOutput = $this->formatOutput;
		$doc->preserveWhiteSpace = true;
		$doc->strictErrorChecking = true;

		$doc->createAttributeNS('http://www.w3.org/1999/xhtml', 'xmlns');
		$doc->createAttributeNS('http://www.idpf.org/2007/ops', 'epub:type');

		/* HTML Element */
		$rootElement = $doc->documentElement;
		$rootElement->setAttribute('xml:lang', $this->epubLang);
		$rootElement->setAttribute('lang', $this->epubLang);

		/* HEAD Element */
		$headElement = $doc->createElement('head');
		$metaCharset = $doc->createElement('meta');
		$metaCharset->setAttribute('charset','UTF-8');
		$headElement->appendChild($metaCharset);
		$rootElement->appendChild($headElement);

		/* Title */
		$titleElement = $doc->createElement('title');
		$titleText = $doc->createTextNode(self::COVER_DOCUMENT_TITLE);
		$titleElement->appendChild($titleText);
		$headElement->appendChild($titleElement);
		
		/* BODY Element */
		$bodyElement = $doc->createElement('body');
		$rootElement->appendChild($bodyElement);

		/* Body Element */
		$bodyElement = $doc->getElementsByTagName('body')->item(0);
		$bodyElement->setAttribute('epub:type','cover');

		/* Container Element */
		$containerElement = $doc->createElement('div');
		$containerElement->setAttribute('class','cover');
		$imgElement = $doc->createElement('img');
		$imgElement->setAttribute('alt','cover');
		$imgElement->setAttribute('role','doc-cover');

		$containerElement->appendChild($imgElement);
		$bodyElement->appendChild($containerElement);
		
		$coverFile = $this->coverFile;
		if(empty($coverFile)) {
			return $doc;
		}

		$coverFileName = $coverFile->filename();
		$srcValue = $this->buildFilePath(self::GRAPHIC_FOLDER_PATH, $coverFileName);
		$imgElement->setAttribute('src', $srcValue);
		
		return $doc;
	} /* END function createCoverDocument */


	private function createContentOpfDocument() {

		/* Create XML Document */
		$doc = new DOMDocument();
		$doc->xmlVersion = '1.0';
		$doc->encoding = 'UTF-8';
		$doc->formatOutput = $this->formatOutput;
		$doc->preserveWhiteSpace = true;
		$doc->strictErrorChecking = true;

		/* Root Element */
		$rootElement = $doc->createElementNS('http://www.idpf.org/2007/opf', 'package');
		$doc->appendChild($rootElement);
		$rootElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', 'http://purl.org/dc/elements/1.1/');
		$rootElement->setAttribute('unique-identifier', 'bookid');
		$rootElement->setAttribute('version', $this->epubVersion);

		/* ++++++++++++ */
		/* + Metadata + */
		/* ++++++++++++ */
		/* Dublin Core Metadata Terms (required) */
		$metadataElement = $this->addElement($doc, $rootElement, 'metadata');
		$dcTitleElement = $this->addElement($doc, $metadataElement, 'dc:title', [['name'=>'id', 'value'=>'opf_title']], $this->metadataTitle);
		$dcIdentifierElement = $this->addElement($doc, $metadataElement, 'dc:identifier', [['name'=>'id', 'value'=>'bookid']], $this->metadataID);
		$dcLanguageElement = $this->addElement($doc, $metadataElement, 'dc:language', [], $this->epubLang);
		
		/* Meta Elements (required) */
		if($this->checkVersion(3)) {
			$metaModifiedElement = $this->addElement($doc, $metadataElement, 'meta', [['name' => 'refines', 'value' => '#opf_title'],['name' => 'property', 'value' => 'title-type']], 'main');
			if($this->metadataIDIsISBN) {
				$metaModifiedElement = $this->addElement($doc, $metadataElement, 'meta', [['name' => 'refines', 'value' => '#bookid'],['name' => 'property', 'value' => 'identifier-type'],['name' => 'scheme', 'value' => 'onix:codelist5']], '15');
			}
			$metaModifiedElement = $this->addElement($doc, $metadataElement, 'meta', [['name' => 'property', 'value' => 'dcterms:modified']], $this->projectDate);
		}
		
		/* Dublin Core Metadata Terms (optional) */

		if(!empty($this->metadataCreator)) {
			$dcCreatorElement = $this->addElement($doc, $metadataElement, 'dc:creator', [['name'=>'id', 'value'=>'opf_author1']], $this->metadataCreator);
		}
		if(!empty($this->metadataRights)) {
			$dcTitleElement = $this->addElement($doc, $metadataElement, 'dc:rights', [], $this->metadataRights);
		}
		if(!empty($this->metadataContributor)) {
			$dcTitleElement = $this->addElement($doc, $metadataElement, 'dc:contributor', [], $this->metadataContributor);
		}
		if(!empty($this->metadataDate)) {
			$dcTitleElement = $this->addElement($doc, $metadataElement, 'dc:date', [], $this->metadataDate);
		}
		if(!empty($this->medadataDescription)) {
			$dcTitleElement = $this->addElement($doc, $metadataElement, 'dc:description', [], $this->medadataDescription);
		}
		
		/* Meta Elements (optional) */
		if($this->checkVersion(3)) {
			if(!empty($this->metadataCreator)) {
				$metaModifiedElement = $this->addElement($doc, $metadataElement, 'meta', [['name' => 'refines', 'value' => '#opf_author1'],['name' => 'property', 'value' => 'role'], ['name' => 'scheme', 'value' => 'marc:relators']], 'aut');
				$metaModifiedElement = $this->addElement($doc, $metadataElement, 'meta', [['name' => 'refines', 'value' => '#opf_author1'],['name' => 'property', 'value' => 'file-as']], $this->metadataCreator);
			}
		}
		
		/* ++++++++++++ */
		/* + Manifest + */
		/* ++++++++++++ */
		$manifestElement = $this->addElement($doc, $rootElement, 'manifest');

		/* Table of Contents */
		$tocNcxFileName = 'toc.ncx';
		$this->addElement($doc, $manifestElement, 'item', [['name' => 'id', 'value' => 'ncx'], ['name' => 'href', 'value' => $tocNcxFileName],['name' => 'media-type', 'value' => 'application/x-dtbncx+xml']]);
		if($this->checkVersion(3)) {
			$tocXhtmlFileName = 'toc.xhtml';
			$this->addElement($doc, $manifestElement, 'item', [['name' => 'id', 'value' => 'nav'], ['name' => 'href', 'value' => $tocXhtmlFileName],['name' => 'media-type', 'value' => 'application/xhtml+xml'],['name' => 'properties', 'value' => 'nav']]);
		}

		/* Content Documents */
		foreach($this->docPages as $page) {
			$pageHashID = $page->hashID();
			$docArchivePath = $this->getDocumentPath($page);
			$this->addElement($doc, $manifestElement, 'item', [['name' => 'id', 'value' => $pageHashID], ['name' => 'href', 'value' => $docArchivePath],['name' => 'media-type', 'value' => 'application/xhtml+xml']]);
			/* Block Image Files */
			$imageFiles = $page->files()->template('blocks/image');
			foreach($imageFiles as $imageFile) {
				$imageHashID = $imageFile->hashID();
				$imageArchivePath = $this->buildFilePath(self::GRAPHIC_FOLDER_PATH, $imageFile->filename(), 'manifest');
				$imageMimeType = mime_content_type($imageFile->realpath()) ?? '';
				$this->addElement($doc, $manifestElement, 'item', [['name' => 'id', 'value' => $imageHashID], ['name' => 'href', 'value' => $imageArchivePath],['name' => 'media-type', 'value' => $imageMimeType]]);
			}
		};

		/* Cover */
		if($this->hasCover && !empty($this->coverFile)) {
			/* cover.jpg */
			$coverFile = $this->coverFile;
			$coverHashID = $coverFile->hashID();
			$coverArchivePath = $this->buildFilePath(self::GRAPHIC_FOLDER_PATH, $coverFile->filename(), 'manifest');
			$coverMimeType = mime_content_type($coverFile->realpath()) ?? '';
			$this->addElement($doc, $manifestElement, 'item', [['name' => 'id', 'value' => $coverHashID], ['name' => 'href', 'value' => $coverArchivePath],['name' => 'media-type', 'value' => $coverMimeType]]);
			/* cover.xhtml */
			$coverHrefValue = $this->buildFilePath(self::CONTENT_FOLDER_PATH, self::COVER_DOCUMENT_NAME, 'manifest');
			$this->addElement($doc, $manifestElement, 'item', [['name' => 'id', 'value' => 'cover'], ['name' => 'href', 'value' => $coverHrefValue],['name' => 'media-type', 'value' => 'application/xhtml+xml']]);
		}

		/* CSS Files */
		foreach($this->cssFiles as $cssFile) {
			$cssHashID = $cssFile->hashID();
			$cssArchivePath = $this->buildFilePath(self::STYLESHEET_FOLDER_PATH, $cssFile->filename(), 'manifest');
			$this->addElement($doc, $manifestElement, 'item', [['name' => 'id', 'value' => $cssHashID], ['name' => 'href', 'value' => $cssArchivePath],['name' => 'media-type', 'value' => 'text/css']]);
		}

		/* Font Files */
		foreach($this->fontFiles as $fontFile) {
			$fontHashID = $fontFile->hashID();
			$fontArchivePath = $this->buildFilePath(self::FONT_FOLDER_PATH, $fontFile->filename(), 'manifest');
			$fontMimeType = mime_content_type($fontFile->realpath()) ?? '';
			$this->addElement($doc, $manifestElement, 'item', [['name' => 'id', 'value' => $fontHashID], ['name' => 'href', 'value' => $fontArchivePath],['name' => 'media-type', 'value' => $fontMimeType]]);
		}

		/* +++++++++ */
		/* + Spine + */
		/* +++++++++ */
		$spineElement = $this->addElement($doc, $rootElement, 'spine', [['name'=>'toc', 'value'=>'ncx']]);

		/* Cover */
		if($this->hasCover && !empty($this->coverFile)) {
			$this->addElement($doc, $spineElement, 'itemref', [['name' => 'idref', 'value' => 'cover']]);
		}

		/* Content Documents */
		foreach($this->tocPages as $page) {
			$pageHashID = $page->hashID();
			$this->addElement($doc, $spineElement, 'itemref', [['name' => 'idref', 'value' => $pageHashID]]);
		};

		/* +++++++++ */
		/* + Guide + */
		/* +++++++++ */
		if($this->checkVersion(2)) {
			$guideElement = $this->addElement($doc, $rootElement, 'guide');
			/* Cover */
			if($this->hasCover && !empty($this->coverFile)) {
				$coverArchivePath = $this->buildFilePath(self::CONTENT_FOLDER_PATH, self::COVER_DOCUMENT_NAME, 'guide');
				$this->addElement($doc, $guideElement, 'reference', [['name' => 'type', 'value' => 'cover'], ['name' => 'title', 'value' => 'Cover'], ['name' => 'href', 'value' => $coverArchivePath]]);
			}
			/* Document Pages */
			foreach($this->docPages as $page) {
				$documentLandmark = $page->documentLandmark();
				if($documentLandmark->exists() || $documentLandmark->isEmpty()) {
					continue;
				}
				$pageTitle = $page->title();
				$docArchivePath = $this->getDocumentPath($page);
				$this->addElement($doc, $guideElement, 'reference', [['name' => 'type', 'value' => $documentLandmark], ['name' => 'title', 'value' => $pageTitle], ['name' => 'href', 'value' => $docArchivePath]]);
			};
		}
		
		$doc->appendChild($rootElement);

		return $doc;
	} /* END function createContentOpfDocument */


	private function createTocXhtmlDocument() {

		$dom = new DOMImplementation();
		$dom->xmlVersion = '1.0';
		$dom->encoding = 'UTF-8';

		$dtd = $dom->createDocumentType('html', '', '');

		/* Create XHTML Document */
		$doc = $dom->createDocument(null, 'html', $dtd);
		$doc->xmlVersion = '1.0';
		$doc->encoding = 'UTF-8';
		$doc->formatOutput = $this->formatOutput;
		$doc->preserveWhiteSpace = true;
		$doc->strictErrorChecking = true;

		$doc->createAttributeNS('http://www.w3.org/1999/xhtml', 'xmlns');
		$doc->createAttributeNS('http://www.idpf.org/2007/ops', 'epub:type');

		/* xPath */
		$xPath = new DOMXPath($doc);

		/* HTML Element */
		$rootElement = $doc->documentElement;
		$rootElement->setAttribute('xml:lang', $this->epubLang);
		$rootElement->setAttribute('lang', $this->epubLang);

		/* HEAD Element */
		$headElement = $doc->createElement('head');
		$metaCharset = $doc->createElement('meta');
		$metaCharset->setAttribute('charset','UTF-8');
		$headElement->appendChild($metaCharset);
		$rootElement->appendChild($headElement);

		/* Title */
		$projectTitle = $this->metadataTitle;
		if(!empty($projectTitle)) {
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

		/* +++++++++++++++++++ */
		/* + Page Navigation + */
		/* +++++++++++++++++++ */
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
		
		/* +++++++++++++++++++++++ */
		/* + Landmark Navigation + */
		/* +++++++++++++++++++++++ */
		$landmarkNav = $doc->createElement('nav');
		$landmarkNav->setAttribute('id','landmarks');
		$landmarkNav->setAttribute('epub:type','landmarks');
		
		$landmarkOl = $this->createLandmarkList($doc, 'ol');
		$landmarkNav->appendChild($landmarkOl);
		
		/* Cover */
		if($this->hasCover && !empty($this->coverFile)) {
			$coverHrefValue = $this->buildFilePath(self::CONTENT_FOLDER_PATH, self::COVER_DOCUMENT_NAME, 'manifest');
			$textContent = t('Cover', 'Cover');
			$liElement = $this->createLandmarkListItem($doc, 'li', 'cover', $coverHrefValue, $textContent);
			$landmarkOl->appendChild($liElement);
		}

		/* Content Documents */
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
	} /* END function createTocXhtmlDocument */


	private function createTocList($doc, $tagName, $levelNum) {
		$olElement = $doc->createElement($tagName);
		$olElement->setAttribute('class', "level-{$levelNum}");
		$olElement->setAttribute('data-level', $levelNum);
		return $olElement;
	} /* END function createTocList */


	private function createTocListItem($doc, $tagName, $levelNum, $hrefValue, $pageTitle) {
		$liElement = $doc->createElement('li');
		$liElement->setAttribute('data-level',$levelNum);
		$aElement = $doc->createElement('a');
		$aElement->setAttribute('href', $hrefValue);
		$textNode = $doc->createTextNode($pageTitle);
		$aElement->appendChild($textNode);
		$liElement->appendChild($aElement);
		return $liElement;
	} /* END function createTocListItem */


	private function createLandmarkList($doc, $tagName) {
		$olElement = $doc->createElement($tagName);
		return $olElement;
	} /* END function createLandmarkList */


	private function createLandmarkListItem($doc, $tagName, $documentLandmark, $hrefValue, $textContent) {
		$liElement = $doc->createElement('li');
		$aElement = $doc->createElement('a');
		$aElement->setAttribute('epub:type', $documentLandmark);
		$aElement->setAttribute('href', $hrefValue);
		$textNode = $doc->createTextNode($textContent);
		$aElement->appendChild($textNode);
		$liElement->appendChild($aElement);
		return $liElement;
	} /* END function createLandmarkListItem */


	private function createTocNcxDocument() {

		$tocDepth = 1;
		$totalPageCount = 0;
		$maxPageNumber = 0;

		$dom = new DOMImplementation();
		$dom->xmlVersion = '1.0';
		$dom->encoding = 'UTF-8';

		/* Create XHTML Document */
		$doc = $dom->createDocument(null, 'ncx');
		$doc->xmlVersion = '1.0';
		$doc->encoding = 'UTF-8';
		$doc->formatOutput = $this->formatOutput;
		$doc->preserveWhiteSpace = true;
		$doc->strictErrorChecking = true;

		$doc->createAttributeNS('http://www.daisy.org/z3986/2005/ncx/', 'xmlns');

		/* xPath */
		$xPath = new DOMXPath($doc);

		/* Root Element */
		$rootElement = $doc->documentElement;
		$rootElement->setAttribute('xml:lang', $this->epubLang);
		$rootElement->setAttribute('dir', 'ltr');
		$rootElement->setAttribute('version', '2005-1');

		/* Head Element */
		$headElement = $this->addElement($doc, $rootElement, 'head');
		$metaUidElement = $this->addElement($doc, $headElement, 'meta', [['name' => 'name', 'value' => 'dtb:uid'],['name' => 'content', 'value' => $this->metadataID]]);
		$metaDepthElement = $this->addElement($doc, $headElement, 'meta', [['name' => 'name', 'value' =>'dtb:depth'],['name'=>'content', 'value' => $tocDepth]]);
		$metaTotalPageCountElement = $this->addElement($doc, $headElement, 'meta', [['name' => 'name', 'value' => 'dtb:totalPageCount'],['name' => 'content', 'value' => $totalPageCount]]);
		$metaMaxPageNumberElement = $this->addElement($doc, $headElement, 'meta', [['name' => 'name', 'value' => 'dtb:maxPageNumber'],['name' => 'content', 'value' => $maxPageNumber]]);

		/* DocTitle Element */
		$docTitleElement = $this->addElement($doc, $rootElement, 'docTitle');
		$textElement = $this->addElement($doc, $docTitleElement, 'text', [], $this->metadataTitle);

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
	} /* END function createTocNcxDocument */


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
	} /* END function createNavPointItem */


	private function getLevelNumber($page) {
		
		$levelNum = intval(preg_replace('/\D+/', '', $page->documentLevel()));
		if(!is_numeric($levelNum) || $levelNum < 1) {
			return;
		}

		return $levelNum;
	} /* END function getLevelNumber */


	private function getDocumentPath($page) {
		
		$contentFolderPath = self::CONTENT_FOLDER_PATH;
		$documentName = $this->getDocumentName($page);
		$documentPath = $this->buildFilePath($contentFolderPath, $documentName);
		
		return $documentPath;
	} /* END function getDocumentPath */


	private function getDocumentName($childPage) {
		
		$projectPagePathArray = explode('/', $this->projectPage->uri());
		$childPagePathArray = explode('/', $childPage->uri());
		
		$projectPageLevel = count($projectPagePathArray);
		$leveledChildPagePathArray = array_slice($childPagePathArray, $projectPageLevel);
		
		$documentName = implode('+', $leveledChildPagePathArray) . '.xhtml';
		
		return $documentName;
	} /* END function getDocumentName */


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
	} /* END function addElement */

	
	private function buildFilePath($folderPath = '', $fileName = '', $flag = '') {

		
		$folderPathArray = explode('/', $folderPath);
		
		if($flag === 'ops') {
			array_unshift($folderPathArray, self::OPS_FOLDER_NAME);
		}

		$documentName = $fileName;
		array_push($folderPathArray, $documentName);

		$folderPathArray = array_filter($folderPathArray, function($item) { 
			return !empty($item); 
		});

		$filePath = implode('/', $folderPathArray);

		return $filePath;
	} /* END function buildFilePath */


	private function checkVersion($versionNumber) {
		
		if(intval($this->epubVersion) === $versionNumber) {
			return true;
		}

		return false;
	} /* END function checkVersion */
}