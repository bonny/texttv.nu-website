<?php

$VIEW_PHPINFO_SECRET = $_SERVER['VIEW_PHPINFO_SECRET'] ?? null;
$VIEW_PHPINFO_QUERY_SECRET = $_GET['VIEW_PHPINFO'] ?? null;

if ($VIEW_PHPINFO_SECRET !== $VIEW_PHPINFO_QUERY_SECRET) {
    header("HTTP/1.1 401 Unauthorized");
    echo "Näe.";
    exit;
}

?>
<p>before</p>
<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

phpinfo();

?>
<p>after</p>
