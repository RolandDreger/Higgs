<?php

return [
	/* Backend logic goes here */
	'props' => [
		'buttonLabel' => function($buttonLabel) {
			return I18n::translate($buttonLabel, $buttonLabel);
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
					$errors = $epubBuilder->errors;
					
					return ['data' => ['errors' => $errors]];
				}
			]
		];
	}
];