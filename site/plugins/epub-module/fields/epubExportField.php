<?php

return [
	'props' => [
		'buttonLabel' => function($buttonLabel) {
			return I18n::translate($buttonLabel, $buttonLabel);
		},
		'tooltip' => function($tooltip) {
			return I18n::translate($tooltip, $tooltip);
		}
	],
	/* Field API */
	'api' => function () {
		return [
			[
				'pattern' => '/export/epub',
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