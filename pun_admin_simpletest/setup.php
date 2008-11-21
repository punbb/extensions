<?php
if (! defined('SIMPLE_TEST')) {
	define('SIMPLE_TEST', 'simpletest/'); //объ€вили переменную под путь, дабы лишние символы не писать, удобство
}
 
if (!file_exists(SIMPLE_TEST . '/browser.php')) {
  die ('Make sure the SIMPLE_TEST constant is set correctly in this file(' . SIMPLE_TEST . ')');
}
 
require_once(SIMPLE_TEST . '/web_tester.php');
require_once(SIMPLE_TEST . '/reporter.php');
require_once(SIMPLE_TEST . '/unit_tester.php');
require_once(SIMPLE_TEST . '/mock_objects.php');
require_once(SIMPLE_TEST . '/browser.php');
require_once(SIMPLE_TEST . '/simpletest.php');
require_once(SIMPLE_TEST . '/user_agent.php');
?>