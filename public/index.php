<?php

include dirname(__DIR__, 1) . '/vendor/autoload.php';

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
		'sessions' => $storage . '/sessions'
	]
]);

echo $kirby->render();