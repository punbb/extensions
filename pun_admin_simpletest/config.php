<?php

$num = (isset($_GET['simpletest'])) ? $_GET['simpletest'] : 0;
$fp = fopen($_SERVER['DOCUMENT_ROOT'].'/extensions/pun_admin_simpletest/cache_simpletest.php', 'r');
$number = fgets($fp);
fclose($fp);

if ($num == $number)
	define('PUN_SIMPLETEST_RUN', 1);

if (defined('PUN_SIMPLETEST_RUN') && PUN_SIMPLETEST_RUN)
	$db_name = 'pun_test_database';

?>