<?php

return [
  'debug' => false,
	'languages' => true,
	'date.handler' => 'strftime',
	'api' => require_once 'api.php',
	'hooks' => require_once 'hooks.php',
	'routes' => require_once 'routes.php'
];