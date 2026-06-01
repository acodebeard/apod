<?php

declare(strict_types=1);

defined('APOD_ROOT') || define('APOD_ROOT', dirname(__DIR__, 2));
defined('APOD_APP') || define('APOD_APP', APOD_ROOT . '/app');
defined('APOD_DATA_FILE') || define('APOD_DATA_FILE', APOD_ROOT . '/data/apod.local.json');
defined('APOD_BASE_PATH') || define('APOD_BASE_PATH', '/apod');

require_once APOD_APP . '/includes/assets.php';
require_once APOD_APP . '/includes/media.php';
