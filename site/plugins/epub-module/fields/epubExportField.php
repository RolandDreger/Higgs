<?php

return [
	'props' => [
		'buttonLabel' => function($buttonLabel) {
			return I18n::translate($buttonLabel, $buttonLabel);
		},
		'tooltip' => function($tooltip) {
			return I18n::translate($tooltip, $tooltip);
		},
		'apiPrefix' => function() {
			return option('higgs.epub-module.apiPrefix', 'higgs');
		}
	],
	/* Field API */
	'api' => function () {
		$apiPrefix = option('higgs.epub-module.apiPrefix', 'higgs');
		return [
			[
				'pattern' => "/{$apiPrefix}/export/epub",
				'method' => 'POST',
				'action' => function() {
					
					$pageID = get('page');
												
					/* Get parent page */
					$parentPage = $this->page($pageID);
					if(!$parentPage) {
						return ['data' => ['errors' => ["Page could not found: {$pageID}"]]];
					}

					/* Build ePub */
					$options = ['formatOutput' => true];
					$epubBuilder = new Higgs\Epub\EpubBuilder($parentPage, $options);
					$epubBuilder->createEpub();
					$epubFileName = $epubBuilder->epubFileName;
					$epubUrl = $epubBuilder->epubUrl;
					$errors = $epubBuilder->errors;
					
					return ['data' => ['errors' => $errors, 'fileName' => $epubFileName, 'url' => $epubUrl]];
				}
			]
		];
	}
];