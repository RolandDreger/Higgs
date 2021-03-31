<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?= $site->title(); ?></title>
		<?= css('assets/css/index.css'); ?>
	</head>
	<body>

		<header class="header">
			<a class="logo" href="<?= $site->url(); ?>"><?= $site->title(); ?></a>
		</header>
		
		<main class="main">
			<h1><?= $page->title(); ?></h1>
		</main>
		
		<footer>
				<a href="https://twitter.com/<?= $site->twitter() ?>">Follow me on Twitter</a>
		</footer>
	</body>
</html>