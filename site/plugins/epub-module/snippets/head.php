<head>
	<meta charset="UTF-8" />
	<title><?= $page->title(); ?></title>
	<?php
		
		$cssFiles = new Files([]);

		/* CSS files of document page */
		if($cssFilesField = $page->epubCSSFiles()) {
			if($cssFilesField->isNotEmpty()) {
				$cssFiles->add($cssFilesField->toFiles());
			}
		}
		
		/* CSS files of project page */
		foreach($page->parents() as $parentPage) {
			$cssFilesField = $parentPage->epubCSSFiles();
			if(!$cssFilesField->exists()) {
				continue;
			}
			if($cssFilesField->isNotEmpty()) {
				$cssFiles->add($cssFilesField->toFiles());
			}
		}
		
		/* Add link elements of selected stylesheets */
		foreach($cssFiles as $css) {
			echo css($css->url()) . '</link>';
		}
	?>
</head>