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
					
					$pageID = get('page');
												
					/* Get parent page */
					$parentPage = $this->page($pageID);
					if(!$parentPage) {
						return ['data' => [
							'errors' => ["Page could not found: {$pageID}"]
						]];
					}

					/* Create ePub from child pages */
					$options = [
						'formatOutput' => false,
						'overwrite' => true
					];

					$epubBuilder = new Higgs\Epub\EpubBuilder($parentPage, null, $options);
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