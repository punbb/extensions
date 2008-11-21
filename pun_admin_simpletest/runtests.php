<?php
require_once(dirname(__FILE__) . '/setup.php');
 
class AllTests extends GroupTest
{
	function AllTests()
	{
		$this->GroupTest('Test extension pun_poll at PunBB');
		//$this->addTestFile('test_pun_poll.php');
		$this->addTestFile('test_pun_attachment.php');
	}
}
 
$test =& new AllTests();

if (SimpleReporter::inCli())
{
	exit ($test->run(new TextReporter()) ? 0 : 1);
}

$test->run(new HtmlReporter());
?>