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

	'api' => function () {
		return [
			[
				'pattern' => "/export/epub",
				'method' => 'POST',
				'action' => function() {
					
					$sourcePageID = get('page');
												
					/* Get source page */
					$sourcePage = $this->page($sourcePageID);
					if(!$sourcePage) {
						return ['data' => [
							'errors' => ["Page could not found: {$sourcePageID}"]
						]];
					}

					/* Create ePub from child pages */
					$options = [
						'formatOutput' => false,
						'overwrite' => true
					];

					$epubBuilder = new Higgs\Epub\EpubBuilder($sourcePage, null, $options);
					$epubBuilder->createEpub();
					
					$epubFileName = $epubBuilder->getEpubFileName();
					$epubUrl = $epubBuilder->getEpubUrl();
					$errors = $epubBuilder->errors;
					
					return ['data' => [
						'errors' => $errors, 
						'fileName' => $epubFileName, 
						'url' => $epubUrl
					]];
				}
			]
		];
	}
];