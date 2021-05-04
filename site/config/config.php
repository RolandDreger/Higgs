<?php

return [
  'debug' => false,
	'languages' => true,
	'date.handler' => 'strftime',
	'content' => require_once 'content.php',
	'api' => require_once 'api.php',
	'hooks' => require_once 'hooks.php',
	'routes' => require_once 'routes.php'
];