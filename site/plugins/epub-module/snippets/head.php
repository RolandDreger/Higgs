<head>
	<meta charset="UFT-8" />
	<title><?= $page->title(); ?></title>
	<?php
		/* projektPage selbst berücksichtigen -> als pageMethod? */
		$parentPages = $page->parents();
		foreach($parentPages as $parent) {
			if($cssFilesField = $parent->epubCSSFiles()) {
				if($cssFilesField->exists()) {
					if($cssFilesField->isNotEmpty()) {
						$cssFiles = $cssFilesField->toFiles();
						foreach($cssFiles as $css) {
							echo css($css->url()) . '</link>';
						}
					}
					break;
				}
			}
		}
	?>
</head>
