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
use DateTime;
use Kirby\Cms\Page;
use Kirby\Toolkit\Str;

/**
 * Generating ePubs from page content via Kirby API
 * (Used helper method: Remote::get)
 * 
 * Usage:
 * $options = [];
 * $epubBuilder = new Higgs\Epub\EpubBuilder($page, $options);
 * $epubBuilder->createEpub();
 * 
 * $errors = $epubBuilder->errors;
 * 
 * Options:
 * 'formatOutput': format generated XML files 
 * 'parent': Export folder page (default projectPage)
 * 'overwrite': Overwrite existing ePub
 * 
 * @author Roland Dreger <roland.dreger@a1.net>
 */

class EpubBuilder {

	const TEMPLATE_FILE_RELATIVE_PATH = 'assets/zip/template.epub';
	const XSL_FILE_EPUB2_RELATIVE_PATH = 'assets/xslt/content-epub-2.xsl';
	const XSL_FILE_EPUB3_RELATIVE_PATH = 'assets/xslt/content-epub-3.xsl';
	const OPS_FOLDER_NAME = 'OEBPS';
	const CONTENT_FOLDER_PATH = ''; /* Do not change! */
	const STYLESHEET_FOLDER_PATH = 'css';
	const FONT_FOLDER_PATH = 'fonts';
	const GRAPHIC_FOLDER_PATH = 'images';
	const COVER_DOCUMENT_TITLE = 'Cover';
	const COVER_DOCUMENT_NAME = 'cover.xhtml';

	private $projectPage;
	private $phpVersionID;
	private $templateFilePath;
	private $xslFilePath;
	private $formatOutput;
	private $isToOverwrite;
	private $docPages = [];
	private $tocPages = [];
	private $cssFiles = [];
	private $fontFiles = [];
	private $hasCover;
	private $coverFile;
	private $coverMaxWidth;
	private $coverMaxHeight;
	private $coverQuality;
	private $imageMaxWidth;
	private $imageMaxHeight;
	private $imageQuality;
	
	public $parentPage;
	public $epubVersion;
	public $epubName;
	public $epubFileName;
	public $epubLang;
	public $projectDate;
	public $projectTitle;
	public $epubUrl;

	public $metadataTitle;
	public $metadataID;
	public $metadataCreator;
	public $metadataIDType;
	public $metadataRights;
	public $metadataContributor;
	public $metadataDate;
	public $medadataDescription;

	public $errors = [];


	public function __construct($projectPage, $options = []) {
		
		if(!$projectPage || !($projectPage instanceof Page)) {
			trigger_error("First parameter must be an page object.", E_USER_ERROR);
		}

		/* 
			Project Page (Source)
			Descendent pages are the content documents of the generated ePub
		*/
		$this->projectPage = $projectPage;

		/* 
			Parent Page (Destination) 
			Location for the ePub file
		*/
		$this->parentPage = $this->getParentPage($projectPage, $options);
	
		/* PHP Version */
		$this->phpVersionID = $this->getPhpVersionID();
		
		/* ePub Version */
		$this->epubVersion = $this->getEpubVersion($projectPage);

		/* Template Path */
		$this->templateFilePath = $this->getTemplateFilePath($projectPage);
		
		/* XSL Path */
		$this->xslFilePath = $this->getXslFilePath($projectPage);

		/* Format Output */
		$this->formatOutput = $this->getFormatOutput($options);

		/* Content Pages (all descendants) */
		$this->docPages = $this->getDocPages($projectPage); 

		/* Table of Contents pages */
		$this->tocPages = $this->getTocPages($projectPage);

		/* ePub Name */
		$this->epubName = $this->getEpubName($projectPage);
		$this->epubFileName = $this->getEpubFileName($projectPage);
	
		/* Overwrite existing ePub */
		$this->isToOverwrite = $this->getIsToOverwrite($options);

		/* CSS Files */
		$this->cssFiles = $this->getCssFiles($projectPage);

		/* Font Files */
		$this->fontFiles = $this->getFontFiles($projectPage);

		/* Cover Image Settings */
		$this->coverMaxWidth = $this->getCoverMaxWidth($projectPage);

		


		
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

		/* ++++++++++++ */
		/* + Metadata + */
		/* ++++++++++++ */
		$this->metadataTitle = $projectPage->metadataTitle()->value(); 
		if(empty($this->metadataTitle)) {
			$this->metadataTitle = $projectPage->title();
		}
		$this->metadataID = $projectPage->metadataID()->value();
		$this->metadataCreator = $projectPage->metadataCreator()->value();
		$this->metadataIDType = $projectPage->metadataIDType()->value();
		$this->metadataRights = $projectPage->metadataRights()->value();
		$this->metadataContributor = $projectPage->metadataContributor()->value();
		$this->metadataDate = $projectPage->metadataDate()->value();
		$this->medadataDescription = $projectPage->medadataDescription()->value();
	}


	protected function getParentPage($page, $options) {
		
		return $options['parent'] ?? $page;
	}

	protected function getPhpVersionID() {

		$versionArray = explode('.', PHP_VERSION);
		$phpVersionID = ($versionArray[0] * 10000 + $versionArray[1] * 100 + $versionArray[2]);

		return $phpVersionID;
	}

	protected function getEpubVersion($page) {
		
		$epubVersion = '';

		$epubVersionField = $page->epubVersion();
		if($epubVersionField->exists() && $epubVersionField->isNotEmpty()) {
			$epubVersion = $epubVersionField->value();
		} 
		
		if($epubVersion !== '2.0' && $epubVersion !== '3.0') {
			trigger_error("Invalid ePub version value. Allowed values: 2.0 or 3.0", E_USER_NOTICE);
		}

		return $epubVersion;
	}

	protected function getTemplateFilePath($page) {
		
		$templateFilePath = $page->kirby()->roots()->plugins() . '/epub-module/' . self::TEMPLATE_FILE_RELATIVE_PATH;
		if(!file_exists($templateFilePath)) {
			trigger_error("Template file does not exists.", E_USER_ERROR);
		}

		return $templateFilePath;
	}

	protected function getXslFilePath($page) {

		$xslFilePath = $page->kirby()->roots()->plugins() . '/epub-module/';

		if($this->checkVersion(2)) {
			$xslFilePath .= self::XSL_FILE_EPUB2_RELATIVE_PATH;
		} 
		elseif ($this->checkVersion(3)) {
			$xslFilePath .= self::XSL_FILE_EPUB3_RELATIVE_PATH;
		}

		if(!file_exists($xslFilePath)) {
			trigger_error("XSL file does not exists", E_USER_ERROR);
		}

		return $xslFilePath;
	}

	protected function getFormatOutput($options) {
		
		$formatOutput = false;

		$optionFormatOutput = $options['formatOutput'];
		if(!empty($optionFormatOutput) && is_bool($optionFormatOutput)) {
			$formatOutput = $optionFormatOutput;
		} 

		return $formatOutput;
	}
	
	protected function getDocPages($page) {
		
		$docPages = $page->index();
		if($docPages->count() === 0) {
			trigger_error('No descendant pages available.');
		}

		return $docPages;
	}

	protected function getTocPages($page) {
		
		$tocPages = [];

		$tocPagesField = $page->projectTableOfContent();
		if($tocPagesField->exists() && $tocPagesField->isNotEmpty()) {
			$tocPages = $tocPagesField->toPages();
		}

		return $tocPages;
	}

	protected function getEpubName($page) {
		
		$epubName = '';
		
		$epubNameField = $page->epubName();
		if($epubNameField->exists() && $epubNameField->isNotEmpty()) {
			$epubName = preg_replace('/\.epub$/', '', $epubNameField->value());
		} else {
			$epubName = $page->slug();
		}

		return $epubName;
	}

	protected function getEpubFileName($page) {

		$epubName = $this->getEpubName($page);
		$epubFileName = $epubName . '.epub';
		
		return $epubFileName;
	}

	protected function getIsToOverwrite($options) {

		$isToOverwrite = $options['overwrite'];
		if(empty($isToOverwrite) || !is_bool($isToOverwrite)) {
			$isToOverwrite = false;
		}

		return $isToOverwrite;
	}

	protected function getCssFiles($page) {

		$cssFiles = [];

		$cssFilesField = $page->epubCSSFiles();
		if($cssFilesField->exists() && $cssFilesField->isNotEmpty()) {
			$cssFiles = $cssFilesField->toFiles();
		}

		return $cssFiles;
	}

	protected function getFontFiles($page) {

		$fontFiles = [];

		$fontFilesField = $page->epubFontFiles();
		if($fontFilesField->exists() && $fontFilesField->isNotEmpty()) {
			$fontFiles = $fontFilesField->toFiles();
		}
		return $fontFiles;
	}

	protected function getCoverMaxWidth($page) {

		$coverMaxWidth = 1200;

		$coverWidthField = $page->epubCoverWidth();
		if($coverWidthField->exists() && $coverWidthField->isNotEmpty()) {
			$coverMaxWidth = $coverWidthField->toInt();
		}
		return $coverMaxWidth;
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

		$epubFileName = $this->epubFileName;
		$projectPage = $this->projectPage;

		/* Check: Delete existing ePub? */
		if($this->isToOverwrite === true) {
			$epubFile = $projectPage->file($epubFileName);
			if($epubFile !== null && $epubFile->exists()) {
				$isDeleted = $epubFile->delete();
				if(!$isDeleted) {
					array_push($this->errors, "File could not be deleted: {$epubFileName}");
					return $this;
				}
			}
		} else { 
			$epubFileName = $this->epubName . '_' . $this->getTimestamp() . '.epub';
		}
		
		/* Create ePub (based on template) */
		$epubFile = $projectPage->file($epubFileName);
		if(!$epubFile || !$epubFile->exists()) {
			$templateFilePath = $this->templateFilePath;
			$epubFile = File::create([
				'source' => $templateFilePath,
				'parent' => $projectPage,
				'filename' => $epubFileName,
				'template' => 'epub',
				'content' => []
			]);
			if(!$epubFile->exists()) {
				array_push($this->errors, "File could not be created: {$epubFileName}");
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
				if(empty($coverFile)) {
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
				
				/* +++++++++++++++++++ */
				/* + Content request + */
				/* +++++++++++++++++++ */
				$request = Remote::get($pageUrl);
				if($request->code() === 200) {
					$content = $request->content();
				} else {
					array_push($this->errors, "Document could not be created: {$docFileName} Code: {$request->code()}");
					continue;
				}
				
				/* Check: Doctype */
				if($this->checkDoctype($content)) {
					array_push($this->errors, "Invalid XML: Detected use of illegal DOCTYPE");
					continue;
				}

				/* ++++++++++++++++++++++++++++++ */
				/* + Content XSL Transformation + */
				/* ++++++++++++++++++++++++++++++ */
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

			$this->epubUrl = $epubFile->url();

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
	 * @throws 
	 */
	protected function transformContent($content) {

		$xslFilePath = $this->xslFilePath;

		$xmlDoc = new DOMDocument();
		$xslDoc = new DOMDocument();

		$xslProcessor = new XSLTProcessor();
		$xslProcessor->setParameter('','css-folder-path', self::STYLESHEET_FOLDER_PATH);
		$xslProcessor->setParameter('','image-folder-path', self::GRAPHIC_FOLDER_PATH);
		
		try {

			$internalErrorsOptionPrevValue = libxml_use_internal_errors();

			/* Turn off entity loader (Defens against XML entity expansion) */
			if($this->phpVersionID < 80000) {
				$isEntityLoaderDisabledPrevValue = libxml_disable_entity_loader(true);
			}

			/* Load content string */
			$isLoaded = $xmlDoc->loadXML($content, LIBXML_PARSEHUGE | LIBXML_NONET);
			if(!$isLoaded) {
				array_push($this->errors, "loadXML error: Could not load the given XML string");
				return $content;
			}
			
			/* Check: Doctype (Defens against XML entity expansion) */
			if($this->checkDoctype($xmlDoc)) {
				array_push($this->errors, "Invalid XML: Detected use of illegal DOCTYPE");
				return '';
			}

			/* Turn on entity loader (to be able to load XSL file) */
			if($this->phpVersionID < 80000) {
				libxml_disable_entity_loader(false);
			}

			/* Load XSL file */
			$isLoaded = $xslDoc->load($xslFilePath, LIBXML_NONET);
			if(!$isLoaded) {
				array_push($this->errors, "load error: Could not load the XSL transformation file");
				return $content;
			}

			libxml_use_internal_errors(true);

			/* Load stylesheet */
			$wasImportOK = $xslProcessor->importStyleSheet($xslDoc);
			if(!$wasImportOK) {
				foreach(libxml_get_errors() as $error) {
					$errorMessage = $error->message;
					array_push($this->errors, "libxml error: {$errorMessage}");
				}
				libxml_clear_errors();
				return $content;
			} 
			
			/* Transform content */
			$transformationResult = $xslProcessor->transformToXML($xmlDoc);
			if(!$transformationResult) {
				foreach(libxml_get_errors() as $error) {
					$errorMessage = $error->message;
					array_push($this->errors, "libxml error: {$errorMessage}");
				}
				libxml_clear_errors();
				return $content;
			}
			
		} catch(Exception $error) {

			$errorMessage = $error->getMessage();
			array_push($this->errors, "XSL transformation of content failed. Error: {$errorMessage}");

		} finally {

			libxml_use_internal_errors($internalErrorsOptionPrevValue);
			
			if($this->phpVersionID < 80000) {
				libxml_disable_entity_loader($isEntityLoaderDisabledPrevValue);
			}
		}
		
		return $transformationResult;
	} /* END function transformContent */


	protected function createCoverDocument() {

		$dom = new DOMImplementation();
		$dom->xmlVersion = '1.0';
		$dom->encoding = 'UTF-8';

		/* Create XHTML Document */
		$doc = $dom->createDocument(null, 'html');
		$doc->xmlVersion = '1.0';
		$doc->encoding = 'UTF-8';
		$doc->formatOutput = $this->formatOutput;
		$doc->preserveWhiteSpace = true;
		$doc->strictErrorChecking = true;

		$doc->createAttributeNS('http://www.w3.org/1999/xhtml', 'xmlns');
		$doc->createAttributeNS('http://www.idpf.org/2007/ops', 'epub:type');

		/* HTML Element */
		$rootElement = $doc->documentElement;
		$this->addAttribute($rootElement, 'xml:lang', $this->epubLang, 'attr');
		$this->addAttribute($rootElement, 'lang', $this->epubLang, 'attr');

		/* HEAD Element */
		$headElement = $this->addElement($doc, 'head', $rootElement);
		$metaCharset = $this->addElement($doc, 'meta', $headElement, [['charset','UTF-8','sec']]);
		
		/* Stylsheets */
		foreach($this->cssFiles as $css) {
			$cssFileName = $css->filename();
			$cssArchivePath = $this->buildFilePath(self::STYLESHEET_FOLDER_PATH, $cssFileName, 'ops');
			$this->addElement($doc, 'link', $headElement, [['href',$cssArchivePath,'href'],['rel','stylesheet','sec']]);
		}

		/* Title */
		$titleElement = $this->addElement($doc, 'title', $headElement, [], self::COVER_DOCUMENT_TITLE);
		
		/* BODY Element */
		$bodyElement = $this->addElement($doc, 'body', $rootElement, [['epub:type','cover','sec']]);

		/* Container Element */
		$containerElement = $this->addElement($doc, 'div', $bodyElement, [['class','cover','sec']]);

		/* Cover Image Element */
		$imgElement = $this->addElement($doc, 'img', $containerElement, [['alt','cover','sec'],['role','doc-cover','sec']]);

		$coverFile = $this->coverFile;
		if(empty($coverFile)) {
			return $doc;
		}

		$coverFileName = $coverFile->filename();
		$srcValue = $this->buildFilePath(self::GRAPHIC_FOLDER_PATH, $coverFileName);
		
		$this->addAttribute($imgElement, 'src', $srcValue, 'src');
		
		return $doc;
	} /* END function createCoverDocument */


	protected function createContentOpfDocument() {

		/* Create XML Document */
		$doc = new DOMDocument();
		$doc->xmlVersion = '1.0';
		$doc->encoding = 'UTF-8';
		$doc->formatOutput = $this->formatOutput;
		$doc->preserveWhiteSpace = true;
		$doc->strictErrorChecking = true;

		/* Root Element */
		$epubVersion = $this->epubVersion;
		$rootElement = $this->addElement($doc, ['http://www.idpf.org/2007/opf', 'package'], $doc, [['unique-identifier','bookid','sec'], ['version',$epubVersion,'attr']]);
		$this->addAttribute($rootElement, 'xmlns:dc', 'http://purl.org/dc/elements/1.1/', 'sec');

		/* ++++++++++++ */
		/* + Metadata + */
		/* ++++++++++++ */
		/* Dublin Core Metadata Terms (required) */
		$metadataElement = $this->addElement($doc, 'metadata', $rootElement);
		$dcTitleElement = $this->addElement($doc, 'dc:title', $metadataElement, [['id','opf_title','sec']], $this->metadataTitle);
		$dcIdentifierElement = $this->addElement($doc, 'dc:identifier', $metadataElement, [['id','bookid','sec']], $this->metadataID);
		$dcLanguageElement = $this->addElement($doc, 'dc:language', $metadataElement, [], $this->epubLang);
		
		/* Meta Elements (required) */
		if($this->checkVersion(3)) {
			$metaModifiedElement = $this->addElement($doc, 'meta', $metadataElement, [['refines','#opf_title','sec'], ['property','title-type','sec']], 'main');
			$metadataIDType = $this->metadataIDType;
			if(!empty($metadataIDType)) {
				$typeCode = $this->getIDSpecifier($metadataIDType, 'code');
				$identifier = $this->getIDSpecifier($metadataIDType, 'identifier');
				if(!empty($typeCode)) {
					$metaModifiedElement = $this->addElement($doc, 'meta', $metadataElement, [['refines','#bookid','sec'],['property','identifier-type','sec'],['scheme',$identifier, 'sec']], $typeCode);
				}
			}
			$metaModifiedElement = $this->addElement($doc, 'meta', $metadataElement, [['property','dcterms:modified','sec']], $this->projectDate);
		}
		
		/* Dublin Core Metadata Terms (optional) */

		if(!empty($this->metadataCreator)) {
			$dcCreatorElement = $this->addElement($doc, 'dc:creator', $metadataElement, [['id','opf_author1','sec']], $this->metadataCreator);
		}
		if(!empty($this->metadataRights)) {
			$dcTitleElement = $this->addElement($doc, 'dc:rights', $metadataElement, [], $this->metadataRights);
		}
		if(!empty($this->metadataContributor)) {
			$dcTitleElement = $this->addElement($doc, 'dc:contributor', $metadataElement, [], $this->metadataContributor);
		}
		if(!empty($this->metadataDate)) {
			$dcTitleElement = $this->addElement($doc, 'dc:date', $metadataElement, [], $this->metadataDate);
		}
		if(!empty($this->medadataDescription)) {
			$dcTitleElement = $this->addElement($doc, 'dc:description', $metadataElement, [], $this->medadataDescription);
		}
		
		/* Meta Elements (optional) */
		if($this->checkVersion(3)) {
			if(!empty($this->metadataCreator)) {
				$metaModifiedElement = $this->addElement($doc, 'meta', $metadataElement, [['refines','#opf_author1','sec'],['property','role','sec'], ['scheme','marc:relators','sec']], 'aut');
				$metaModifiedElement = $this->addElement($doc, 'meta', $metadataElement, [['refines','#opf_author1','sec'],['property','file-as','sec']], $this->metadataCreator);
			}
		}
		
		/* ++++++++++++ */
		/* + Manifest + */
		/* ++++++++++++ */
		$manifestElement = $this->addElement($doc, 'manifest', $rootElement);

		/* Table of Contents */
		$this->addElement($doc, 'item', $manifestElement, [['id','ncx','sec'],['href','toc.ncx','href'],['media-type','application/x-dtbncx+xml','sec']]);
		if($this->checkVersion(3)) {
			$this->addElement($doc, 'item', $manifestElement, [['id','nav','sec'],['href','toc.xhtml','href'],['media-type','application/xhtml+xml','sec'],['properties','nav','sec']]);
		}

		/* Content Documents */
		foreach($this->docPages as $page) {
			$pageHashID = $page->hashID();
			$docArchivePath = $this->getDocumentPath($page);
			$this->addElement($doc, 'item', $manifestElement, [['id',$pageHashID,'attr'],['href',$docArchivePath,'href'],['media-type','application/xhtml+xml','sec']]);
			/* Block Image Files */
			$imageFiles = $page->files()->template('blocks/image');
			foreach($imageFiles as $imageFile) {
				$imageHashID = $imageFile->hashID();
				$imageArchivePath = $this->buildFilePath(self::GRAPHIC_FOLDER_PATH, $imageFile->filename(), 'manifest');
				$imageMimeType = mime_content_type($imageFile->realpath()) ?? '';
				$this->addElement($doc, 'item', $manifestElement, [['id',$imageHashID,'attr'],['href',$imageArchivePath,'href'],['media-type',$imageMimeType,'sec']]);
			}
		};

		/* Cover */
		if($this->hasCover && !empty($this->coverFile)) {
			/* cover.jpg */
			$coverFile = $this->coverFile;
			$coverHashID = $coverFile->hashID();
			$coverArchivePath = $this->buildFilePath(self::GRAPHIC_FOLDER_PATH, $coverFile->filename(), 'manifest');
			$coverMimeType = mime_content_type($coverFile->realpath()) ?? '';
			$this->addElement($doc, 'item', $manifestElement, [['id',$coverHashID,'attr'],['href',$coverArchivePath,'href'],['media-type',$coverMimeType,'sec']]);
			/* cover.xhtml */
			$coverHrefValue = $this->buildFilePath(self::CONTENT_FOLDER_PATH, self::COVER_DOCUMENT_NAME, 'manifest');
			$this->addElement($doc, 'item', $manifestElement, [['id','cover','sec'],['href',$coverHrefValue,'href'],['media-type','application/xhtml+xml','sec']]);
		}

		/* CSS Files */
		foreach($this->cssFiles as $cssFile) {
			$cssHashID = $cssFile->hashID();
			$cssArchivePath = $this->buildFilePath(self::STYLESHEET_FOLDER_PATH, $cssFile->filename(), 'manifest');
			$this->addElement($doc, 'item', $manifestElement, [['id',$cssHashID,'attr'],['href',$cssArchivePath,'href'],['media-type','text/css','sec']]);
		}

		/* Font Files */
		foreach($this->fontFiles as $fontFile) {
			$fontHashID = $fontFile->hashID();
			$fontArchivePath = $this->buildFilePath(self::FONT_FOLDER_PATH, $fontFile->filename(), 'manifest');
			$fontMimeType = mime_content_type($fontFile->realpath()) ?? '';
			$this->addElement($doc, 'item', $manifestElement, [['id',$fontHashID,'attr'],['href',$fontArchivePath,'href'],['media-type',$fontMimeType,'sec']]);
		}

		/* +++++++++ */
		/* + Spine + */
		/* +++++++++ */
		$spineElement = $this->addElement($doc, 'spine', $rootElement, [['toc','ncx','sec']]);

		/* Cover */
		if($this->hasCover && !empty($this->coverFile)) {
			$this->addElement($doc, 'itemref', $spineElement, [['idref','cover','sec']]);
		}

		/* Content Documents */
		foreach($this->tocPages as $page) {
			$pageHashID = $page->hashID();
			$this->addElement($doc, 'itemref', $spineElement, [['idref',$pageHashID,'attr']]);
		};

		/* +++++++++ */
		/* + Guide + */
		/* +++++++++ */
		if($this->checkVersion(2)) {
			$guideElement = $this->addElement($doc, 'guide', $rootElement);
			/* Cover */
			if($this->hasCover && !empty($this->coverFile)) {
				$coverArchivePath = $this->buildFilePath(self::CONTENT_FOLDER_PATH, self::COVER_DOCUMENT_NAME, 'guide');
				$this->addElement($doc, 'reference', $guideElement, [['type','cover','sec'],['title','Cover','sec'],['href',$coverArchivePath,'href']]);
			}
			/* Document Pages */
			foreach($this->docPages as $page) {
				$documentLandmark = $page->documentLandmark();
				if($documentLandmark->exists() || $documentLandmark->isEmpty()) {
					continue;
				}
				$pageTitle = $page->title();
				$docArchivePath = $this->getDocumentPath($page);
				$this->addElement($doc, 'reference', $guideElement, [['type',$documentLandmark,'attr'],['title',$pageTitle,'attr'],['href',$docArchivePath,'href']]);
			};
		}
		
		$doc->appendChild($rootElement);

		return $doc;
	} /* END function createContentOpfDocument */


	protected function createTocXhtmlDocument() {

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
		$this->addAttribute($rootElement, 'xml:lang', $this->epubLang, 'attr');
		$this->addAttribute($rootElement, 'lang', $this->epubLang, 'attr');

		/* HEAD Element */
		$headElement = $this->addElement($doc, 'head', $rootElement);
		$metaCharset = $this->addElement($doc, 'meta', $headElement, [['charset','UTF-8','sec']]);

		/* Title */
		$projectTitle = $this->metadataTitle;
		if(!empty($projectTitle)) {
			$titleElement = $this->addElement($doc, 'title', $headElement, [], $projectTitle);
		}

		/* BODY Element */
		$bodyElement = $this->addElement($doc, 'body', $rootElement);

		/* Project Title */
		$h1Element = $this->addElement($doc, 'h1', $bodyElement, [], $projectTitle);

		/* +++++++++++++++++++ */
		/* + Page Navigation + */
		/* +++++++++++++++++++ */
		$pageNavElement = $this->addElement($doc, 'nav', $bodyElement, [['id','toc','sec'],['epub:type','toc','sec'],['role','doc-toc','sec']]);
		
		$tocOlLevel1Element = $this->createTocList($doc, 'ol', 1);
		$pageNavElement->appendChild($tocOlLevel1Element);

		foreach($this->tocPages as $page) {
			
			$pageTitle = $page->title();

			$levelNum = $this->getLevelNumber($page);
			if(empty($levelNum)) {
				array_push($this->errors, "Level is not specified for the document: {$pageTitle}");
				continue;
			}
			
			$hrefValue = $this->getDocumentPath($page) . '#' . $page->hashID();
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
		$landmarkNav = $this->addElement($doc,'nav', $bodyElement, [['id','landmarks','sec'],['epub:type','landmarks','sec']]);
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

		return $doc;
	} /* END function createTocXhtmlDocument */


	protected function createTocList($doc, $tagName, $levelNum) {
		
		$olElement = $this->addElement($doc, $tagName, null, [
			['class', "level-{$levelNum}", 'attr'],
			['data-level', $levelNum, 'attr']
		]);
		
		return $olElement;
	} /* END function createTocList */


	protected function createTocListItem($doc, $tagName, $levelNum, $hrefValue, $pageTitle) {
		
		$liElement = $this->addElement($doc,'li', null, [
			['data-level', $levelNum, 'attr']
		]);
		
		$aElement = $this->addElement($doc,'a', $liElement, [
			['href', $hrefValue, 'href']
		], $pageTitle);

		return $liElement;
	} /* END function createTocListItem */


	protected function createLandmarkList($doc, $tagName) {
		
		$olElement = $this->addElement($doc, $tagName);
		
		return $olElement;
	} /* END function createLandmarkList */


	protected function createLandmarkListItem($doc, $tagName, $documentLandmark, $hrefValue, $textContent) {
		
		$liElement = $this->addElement($doc,'li');
		$aElement = $this->addElement($doc,'a', $liElement, [
			['epub:type', $documentLandmark, 'attr'],
			['href', $hrefValue, 'href']
		], $textContent);

		return $liElement;
	} /* END function createLandmarkListItem */


	protected function createTocNcxDocument() {

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
		$this->addAttribute($rootElement, 'xml:lang', $this->epubLang, 'attr');
		$this->addAttribute($rootElement, 'dir', 'ltr', 'sec');
		$this->addAttribute($rootElement, 'version', '2005-1', 'sec');

		/* Head Element */
		$headElement = $this->addElement($doc, 'head', $rootElement);
		$metaUidElement = $this->addElement($doc, 'meta', $headElement, [['name','dtb:uid','sec'],['content',$this->metadataID,'sani']]);
		$metaDepthElement = $this->addElement($doc, 'meta', $headElement, [['name','dtb:depth','sec'],['content', $tocDepth,'attr']]);
		$metaTotalPageCountElement = $this->addElement($doc, 'meta', $headElement, [['name','dtb:totalPageCount','sec'],['content',$totalPageCount,'attr']]);
		$metaMaxPageNumberElement = $this->addElement($doc, 'meta', $headElement, [['name','dtb:maxPageNumber','sec'],['content',$maxPageNumber,'attr']]);

		/* DocTitle Element */
		$docTitleElement = $this->addElement($doc, 'docTitle', $rootElement);
		$textElement = $this->addElement($doc, 'text', $docTitleElement, [], $this->metadataTitle);

		/* NavMap Element */
		$navMapElement = $this->addElement($doc, 'navMap', $rootElement);
		
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
			$this->addAttribute($navPointElement, 'data-level', $levelNum, 'attr');

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

		$this->addAttribute($metaDepthElement, 'content', $tocDepth, 'attr');

		return $doc;
	} /* END function createTocNcxDocument */


	protected function createNavPointItem($doc, $id, $playOrder, $text, $src) {
		
		$navPointElement = $this->addElement($doc,'navPoint', null, [
			['id', $id, 'attr'],
			['playOrder', $playOrder, 'attr']
		]);
		$navLabelElement = $this->addElement($doc,'navLabel', $navPointElement);
		$textElement = $this->addElement($doc,'text', $navLabelElement, [], $text);
		$contentElement = $this->addElement($doc,'content', $navPointElement, [
			['src', $src, 'src']
		]);
		
		return $navPointElement;
	} /* END function createNavPointItem */


	protected function getLevelNumber($page) {
		
		$levelNum = intval(preg_replace('/\D+/', '', $page->documentLevel()));
		if(!is_numeric($levelNum) || $levelNum < 1) {
			return;
		}

		return $levelNum;
	} /* END function getLevelNumber */


	protected function getDocumentPath($page) {
		
		$contentFolderPath = self::CONTENT_FOLDER_PATH;
		$documentName = $this->getDocumentName($page);
		$documentPath = $this->buildFilePath($contentFolderPath, $documentName);
		
		return $documentPath;
	} /* END function getDocumentPath */


	protected function getDocumentName($childPage) {
		
		$projectPagePathArray = explode('/', $this->projectPage->uri());
		$childPagePathArray = explode('/', $childPage->uri());
		
		$projectPageLevel = count($projectPagePathArray);
		$leveledChildPagePathArray = array_slice($childPagePathArray, $projectPageLevel);
		
		$documentName = implode('+', $leveledChildPagePathArray) . '.xhtml';
		
		return $documentName;
	} /* END function getDocumentName */


	protected function buildFilePath($folderPath = '', $fileName = '', $flag = '') {

		
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


	protected function checkVersion($versionNumber) {
		
		if(intval($this->epubVersion) === $versionNumber) {
			return true;
		}

		return false;
	} /* END function checkVersion */


	protected function addElement($doc, $elementName, $parentElement = null, $attrArray = [], $textContent = '') {
		
		if(is_array($elementName)) {
			$element = $doc->createElementNS($elementName[0], $elementName[1]);
		} else {
			$element = $doc->createElement($elementName);
		}
		
		foreach($attrArray as $attr) {
			$attrName = $attr[0];
			$attrValue = $attr[1] ?? '';
			$attrType = $attr[2] ?? '';
			$this->addAttribute($element, $attrName, $attrValue, $attrType);
		}

		if(!empty($textContent)) {
			$textNode = $doc->createTextNode($textContent);
			$element->appendChild($textNode);
		}

		if(!empty($parentElement)) {
			$parentElement->appendChild($element);
		}
		
		return $element;
	} /* END function addElement */


	protected function addAttribute($element, $name, $value, $type) {

		switch($type) {
			case 'sec':
				$sanitizedValue = $value;
				break;
			case 'sani':
			case 'src':
			case 'href':
				$sanitizedValue = $this->sanitize($value);
				break;
			case 'url':
				$sanitizedValue = esc($value, 'url');
				break;
			case 'attr':
				$sanitizedValue = esc($value, 'attr');
				break;
			default:
				$sanitizedValue = '';
		}
		
		if(is_array($name)) {
			$namespace = $name[0];
			$prefix = $name[1];
			$localName = $name[2];
			$qName = $this->getXMLAttributeName($prefix . ':' . $localName);
			$attr = $element->setAttributeNS($namespace, $qName, $sanitizedValue);
		} else {
			$qName = $this->getXMLAttributeName($name);
			$attr = $element->setAttribute($qName, $sanitizedValue);
		}

		return $attr;
	}


	protected function getIDSpecifier($idType, $flag) {
		
		switch($idType) {
			case 'ISBN':
				$typeCode = '15';
				$identifier = 'onix:codelist5';
				break;
			case 'ISSN':
				$typeCode = '02';
				$identifier = 'onix:codelist5';
				break;
			case 'DOI':
				$typeCode = '06';
				$identifier = 'onix:codelist5';
				break;
			case 'URI':
				$typeCode = 'uri';
				$identifier = 'marc:identifiers';
				break;
			default:
				$typeCode = '';
				$identifier = '';
		}

		switch($flag) {
			case 'code':
				$output = $typeCode;
				break;
			case 'identifier':
				$output = $identifier;
				break;
				default:
				$output = '';
		}

		return $output;
	}


	protected function sanitize($string) {

		$decodedString = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
		$sanitizedString = htmlspecialchars($decodedString, ENT_QUOTES, 'UTF-8', false);

		return $sanitizedString;
	}


	protected function getXMLElementName($string) {

		$string = trim($string);
		$string = preg_replace('/\s+/', '-', $string);
		$string = preg_replace('/[^\p{L}\p{N}\-_:]/i', '', $string);
		$string = preg_replace('/^([\p{N}\-_:]+)?((XML)+)?[\p{N}\-_:]+/i', '', $string);

		return $string;
	}


	protected function getXMLAttributeName($string) {

		$string = trim($string);
		$string = preg_replace('/\s+/', '-', $string);
		$string = preg_replace('/[^\p{L}\p{N}\-_:]/i', '', $string);
		$string = preg_replace('/^[\p{N}\-_:]+/', '', $string);

		return $string;
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
					if($isStrictCheck) {
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


	protected function getTimestamp() {

		$date = new DateTime();
		$timestamp = $date->getTimestamp();

		return $timestamp; 
	}
}