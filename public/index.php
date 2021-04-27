<?php

include __DIR__ . '/../vendor/autoload.php';

$kirby = new Kirby([
	'roots' => [
		'index'    => __DIR__,
		'base'     => $base    = dirname(__DIR__),
		'content'  => $base . '/content',
		'site'     => $base . '/site',
		'storage'  => $storage = $base . '/storage',
		'accounts' => $storage . '/accounts',
		'cache'    => $storage . '/cache',
		'log'      => $storage . '/log',
		'media'    => $storage . '/media',
		'sessions' => $storage . '/sessions'
	]
]);

if(!file_exists(__DIR__ . '/media')) {
	symlink($kirby->roots()->media(), __DIR__ . '/media');
}

echo $kirby->render();