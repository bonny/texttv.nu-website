<?php

// Konstanter som texttv.nu/codeigniter/index.php sätter vid en riktig request.
$root = dirname(__DIR__, 2) . '/texttv.nu/codeigniter';

define('ENVIRONMENT', 'development');
define('SELF', 'index.php');
define('EXT', '.php');
define('BASEPATH', $root . '/system/');
define('FCPATH', dirname($root) . '/');
define('SYSDIR', 'system');
define('APPPATH', $root . '/application/');
