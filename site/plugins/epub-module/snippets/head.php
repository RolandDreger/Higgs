<head>
	<meta charset="UFT-8" />
	<title><?= $page->title(); ?></title>
	<?php
		
		$cssFiles = new Collection([]);

		/* CSS files of page */
		if($cssFilesField = $page->epubCSSFiles()) {
			if($cssFilesField->exists()) {
				if($cssFilesField->isNotEmpty()) {
					$cssFiles->add($cssFilesField->toFiles());
				}
			}
		}
		
		/* CSS files of project pages */
		$parentPages = $page->parents();
			
		foreach($parentPages as $parentPage) {
			if($parentPage->intendedTemplate()->name() !== 'project') {
				continue;
			}
			if($cssFilesField = $parentPage->epubCSSFiles()) {
				if($cssFilesField->exists()) {
					if($cssFilesField->isNotEmpty()) {
						$cssFiles->add($cssFilesField->toFiles());
						break;
					}
				}
			}
		}
		
		/* Add stylesheet link elements */
		foreach($cssFiles as $css) {
			echo css($css->url()) . '</link>';
		}		
	?>
</head>
