<?php
class WebTestPunPollByAdminUser extends WebTestCase
{
	function setUp()// ÔÓÍÊÖÈß ÏÅĞÅÄ ÒÅÑÒÎÌ
	{
		ini_set('max_execution_time', 400);// set time to more larger then was set..
		
		$this->_switchToWebTestingDb();// ÏÅĞÅÄÅË ÍÀ ÒÅÑÒÎÂÓŞ ÁÀÇÓ
		$this->_setupPunBB();
		$this->_loginToSiteAsUser('admin', 'admin');
		$this->_setupExtensionPunPoll();
		$this->_logoutFromSite();
	}
	
	function testOfIndexPage()
	{
		$this->get('http://www.prapor.ru');
		$this->assertTitle('title');
		$this->_loginToSiteAsUser('admin', 'admin');
		$this->_testCreatePoll();
		
		$this->_showFlagShowResultsAllUsers();
		$this->_showFlagAvailableRevote();
		$this->_showFlagDeleteResultWithNewAnswers();
		$this->_lineQuestionAnswers();
		$this->_lineCountAnswersInput();
		$this->_lineDaysCountInput();
		$this->_testDeletePollButton();
		$this->_showFlagAdminDisableSeeResults();
		$this->_lineAdminMaxAnswers();
		$this->_showFlagAdminEnablePolls();
		
		$this->_testCreatePoll();
		$this->_logoutFromSite();
		$this->_viewEditGuest();
		$this->_testViewCreatingInfo2();
	}
	
	function tearDown()// ÔÓÍÊÖÈß ÏÎÑËÅ ÒÅÑÒÀ
	{
		$this->_switchToProductionDb();//ÏÅĞÅÄÅË ÍÀ ÎÑÍÎÂÍÓŞ ÁÀÇÓ
		
		ini_set('max_execution_time', 30);
	}
	
	function _switchToWebTestingDb()
	{
		$project_dir = dirname(__FILE__) . '\\';// ĞÀÁÎ×ÈÉ ÊÀÒÀËÎÃ
		$tests_dir = dirname(__FILE__) . '\tests\\';// ÊÀÒÀËÎÃ Ñ ÒÅÑÒÎÂÛÌÈ ÄÀÍÍÛÌÈ
		$this->_clearCachePun();
		$conn = mysql_connect("127.0.0.1", "root", ""); // ÍÅÇÍÀŞ ÊÀÊ Ñ ÊÎÍÔÈÃ ÔÀÉËÀ2 ÂÇßÒÜ ÄÀÍÍÛÅ ÍÓÆÍÛÅ
		// ÍÀ×ÀËÎ ÁËÎÊÀ ÏÅĞÂÎÍÀ×ÀËÜÍÛÕ ÓÑÒÀÍÎÂÎÊ ÁÀÇÛ ÄÀÍÍÛÕ
		
		mysql_query('CREATE DATABASE test_pun_poll_db');
		mysql_select_db('test_pun_poll_db', $conn);
		
		
		// ÊÎÍÅÖ ÁËÎÊÀ ÏÅĞÂÎÍÀ×ÀËÜÍÛÕ ÓÑÒÀÍÎÂÎÊ ÁÀÇÛ ÄÀÍÍÛÕ
		
		rename($project_dir . 'config.php', $project_dir . 'test.php~');// ÏÅĞÅÈÌÅÍÎÂÛÂÀÅÌ ÕÎĞÎØÈÉ ÊÎÍÔÈÃ Â ÄĞÓÃÎÉ
	}
	
	function _switchToProductionDb()
	{
		$project_dir = dirname(__FILE__) . '\\'; // ÓÑÒÀÍÎÂÊÀ ĞÀÁÎ×ÅÃÎ ÊÀÒÀËÎÃÀ
		// ÂÎÇÂĞÀÆÅÍÈÅ ÁÀÇÛ ÄÀÍÍÛÕ Â ÍÀ×ÀËÜÍÎÅ ÑÎÑÒÎßÍÈÅ ÍÀ×ÀËÎ
		
		mysql_query('DROP DATABASE test_pun_poll_db');
		
		// ÂÎÇÂĞÀÙÅÍÈÅ ÁÀÇÛ ÄÀÍÍÛÕ Â ÍÀ×ÀËÜÍÎÅ ÑÎÑÒÎßÍÈÅ ÊÎÍÅÖ
		$this->_clearCachePun();
		
		unlink($project_dir . 'config.php');
		rename($project_dir . 'test.php~', $project_dir . 'config.php'); // ÑÒÀÂÈÌ ÎÁĞÀÒÍÎ ÕÎĞÎØÈÉ ÊÎÍÔÈÃ
	}
	
	function _clearCachePun()
	{
		$project_dir = dirname(__FILE__) . '\\';// ĞÀÁÎ×ÈÉ ÊÀÒÀËÎÃ
		$dir = $project_dir.'cache';
		$dh = opendir($dir);
		$files = array();
		
		while (false !== ($filename = readdir($dh)))
		{
			$name = substr_replace($filename, '', 0, (strlen($filename) - 4));
			
			if ($name == '.php')
				unlink($project_dir.'cache\\'.$filename);
		}
	}
	
	function _setupPunBB()
	{
		$project_dir = dirname(__FILE__) . '\\';// ĞÀÁÎ×ÈÉ ÊÀÒÀËÎÃ
		$tests_dir = dirname(__FILE__) . '\tests\\';// ÊÀÒÀËÎÃ Ñ ÒÅÑÒÎÂÛÌÈ ÄÀÍÍÛÌÈ
		
		$this->get('http://www.prapor.ru/install.php');
		$this->assertText('Install PunBB 1.3 RC');
		$this->setField('req_db_name', 'test_pun_poll_db');
		$this->setField('db_username', 'root');
		$this->setField('req_username', 'admin');
		$this->setField('req_password1', 'admin');
		$this->setField('req_password2', 'admin');
		$this->setField('req_email', 'admin@yandex.ru');
		$this->setField('board_title', 'title');
		$this->setField('board_descrip', 'description');
		$this->clickSubmit('Start install');
	}
	
	function _setupExtensionPunPoll()
	{
		$this->get('http://www.prapor.ru/admin/extensions.php?install=pun_poll');
		$this->clickSubmit('Install extension');
	}
	
	function _loginToSiteAsUser($username, $password)
	{
		$this->get('http://www.prapor.ru');
		$this->clickLink('Login');
		$this->assertText('Login to title');
		$this->setField('req_username', $username);
		$this->setField('req_password', $password);
		$this->clickSubmit('Login');
	}
	
	function _testCreatePoll()
	{
		$this->post('http://www.prapor.ru/edit.php?id=1');
		$this->assertText('Poll question');
		$this->setField('question_of_poll', 'My question');
		$this->setFieldById('poll_answer[0]', 'My answer the first');
		$this->setFieldById('poll_answer[1]', 'My answer the second');
		$this->click('Submit');
		$this->assertText('Redirecting');
	}
	
	function _testViewCreatingInfo1()// ÏĞÎÑÌÎÒĞ ÄÀÍÍÛÕ ÏÅĞÂÎÃÎ ÒÅÑÒÈĞÎÂÀÍÈß
	{
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->assertText('Topic poll');
		$this->assertText('My question');
		$this->assertText('My answer the first');
		$this->assertText('My answer the second');
	}
	// This part for Guest
	function _logoutFromSite()
	{
		$this->get('http://www.prapor.ru/');
		$this->clickLink('Logout');
	}
	
	function _viewEditGuest()
	{
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->assertText('You do not have permission to access this page.');
	}
	
	function _testViewCreatingInfo2()// ÏĞÎÑÌÎÒĞ ÃÎËÎÑÎÂÀÍÈß ÊÀÊ ÃÎÑÒß
	{
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->assertText('Topic poll');
		$this->assertText('Guest don\'t vote.');
		$this->assertText('Topic poll');
		$this->assertText('My question');
		$this->assertText('My answer the first');
		$this->assertText('My answer the second');
	}
	
	function _sendOpinion()
	{
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->setField('rad_poll', '1');
		$this->clickSubmit('Submit opinion');
		$this->assertText('Sending opinion . . .');
	}
	
	function _checkResultSending1()// When we send opinion, but not to revote
	{
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->assertText('Votes count:1');
		
		//checking database
		$resource = mysql_query('SELECT * FROM voting');
		$this->assertEqual(mysql_num_rows($resource), 1);
		
		$result_view = array();
		$result_view = mysql_fetch_assoc($resource);
		echo 'There is must be one user with name="admin"';
		echo '<pre>';
		print_r($result_view);
		echo '</pre>';
	}
	/*
	Ôëàãè íà ñòğàíèöå íàñòğîåê àäìèíà
	
	<label for="fld36"><span class="fld-label">Disable revoting</span><br /><input type="checkbox" id="fld36" name="form[disable_rev]" value="1"  /> Users can't revote (Yes/No)</label>
	<label for="fld37"><span class="fld-label">Disable see results</span><br /><input type="checkbox" id="fld37" name="form[disable_see]" value="1"  /> Unvoting users can't see results of poll (Yes/No)</label>
	<label for="fld38"><span class="fld-label">Maximum answers</span><br /><input type="text" id="fld38" name="form[max_ans]" size="2px" value="50" />Maximum answers in poll</label>
	
	*/
	function _showFlagShowResultsAllUsers()
	{
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->setField('form[disable_see]', false);
		$this->clickSubmit('Save changes');
		
		mysql_query('UPDATE questions SET able_see="1" WHERE id_topic="1" ');
		mysql_query('DELETE FROM voting');
		mysql_query('INSERT INTO voting VALUES ("", "1", "3", "2"), ("", "1", "4", "1"), ("", "1", "5", "2"), ("", "1", "6", "2"); ');
		
		$page_html = $this->get('http://www.prapor.ru/viewtopic.php?id=1');
		
		$test_res = strpos($page_html, '<td width="10%">1</td>');
		$this->assertTrue($test_res);
		
		$test_res = strpos($page_html, '<td width="10%">3</td>');
		$this->assertTrue($test_res);
		
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->setField('form[disable_see]', true);
		$this->clickSubmit('Save changes');
		
		$page_html = $this->get('http://www.prapor.ru/viewtopic.php?id=1');
		
		$test_res = strpos($page_html, '<td width="10%">1</td>');
		$this->assertFalse($test_res);
		
		$test_res = strpos($page_html, '<td width="10%">3</td>');
		$this->assertFalse($test_res);
	}
	
	function _showFlagAvailableRevote()
	{
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->setField('form[disable_rev]', true);
		$this->clickSubmit('Save changes');
		
		mysql_query('UPDATE questions SET able_rev="1" WHERE id_topic="1" ');
		mysql_query('DELETE FROM voting');
		
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->setField('rad_poll', '0');
		$this->clickSubmit('Submit opinion');
		$this->assertText('Sending opinion . . .');
		
		$page_html = $this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$test_res = strpos($page_html, 'Revote poll');
		$this->assertTrue($test_res);
		
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->setField('form[disable_rev]', true);
		$this->clickSubmit('Save changes');
		
		mysql_query('UPDATE questions SET able_rev="0" WHERE id_topic="1" ');
		mysql_query('DELETE FROM voting');
		
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->setField('rad_poll', '0');
		$this->clickSubmit('Submit opinion');
		$this->assertText('Sending opinion . . .');
		
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->assertText('You opinion is already counted.');
	}
	
	function _showFlagDeleteResultWithNewAnswers()
	{
		mysql_query('INSERT INTO voting VALUES ("", "1", "3", "2"), ("", "1", "4", "1"), ("", "1", "5", "2"), ("", "1", "6", "2"); ');
		
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->setField('poll_ans_count', '4');
		$this->setField('del_all_results', true);
		$this->clickSubmit('Update poll');
		$this->setFieldById('poll_answer[2]', 'My answer the third');
		$this->setFieldById('poll_answer[3]', 'My answer the fourth');
		$this->clickSubmit('Submit');
		
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->assertText('Votes count:5');
		
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->setField('poll_ans_count', '3');
		$this->setField('del_all_results', true);
		$this->clickSubmit('Update poll');
		$this->setFieldById('poll_answer[2]', 'My answer the third edition');
		$this->clickSubmit('Submit');
		
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->assertText('Votes count:0');
	}
	
	function _lineQuestionAnswers()
	{
		mysql_query('DELETE FROM answers');
		mysql_query('DELETE FROM questions');
		mysql_query('DELETE FROM voting');
		
		//Ââîä îáû÷íîãî òåêñòà
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->setField('question_of_poll', 'My question');
		$this->setFieldById('poll_answer[0]', 'My answer the first');
		$this->setFieldById('poll_answer[1]', 'My answer the second');
		$this->clickSubmit('Submit');
		
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->assertText('My question');
		$this->assertText('My answer the first');
		$this->assertText('My answer the second');
		
		mysql_query('DELETE FROM answers');
		mysql_query('DELETE FROM questions');
		
		//Ââîä ñïåöèàëüíûõ ñèìâîëîâ
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->setField('question_of_poll', '<?php echo "My"?> <!-- question');
		$this->setFieldById('poll_answer[0]', '<?php echo"My"?> <strong>answer</strong> <!-- the first');
		$this->setFieldById('poll_answer[1]', '<?php echo"My"?> <strong>answer</strong> <!-- the second');
		$this->clickSubmit('Submit');
		
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->assertText('<?php echo "My"?> <!-- question');
		$this->assertText('<?php echo"My"?> <strong>answer</strong> <!-- the first');
		$this->assertText('<?php echo"My"?> <strong>answer</strong> <!-- the second');
		
		mysql_query('DELETE FROM answers');
		mysql_query('DELETE FROM questions');
		
		//Ââîä ïóñòûõ ñòğîê
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->setField('poll_ans_count', '4');
		$this->clickSubmit('Update poll');
		$this->setField('question_of_poll', 'My question');
		$this->setFieldById('poll_answer[0]', 'My answer the first');
		$this->setFieldById('poll_answer[1]', 'My answer the second');
		$this->setFieldById('poll_answer[2]', '');
		$this->setFieldById('poll_answer[3]', 'My answer the fourth');
		$this->clickSubmit('Submit');
		
		$page_html = $this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->assertText('My question');
		$this->assertText('My answer the first');
		$this->assertText('My answer the second');
		$this->assertText('My answer the fourth');
		
		$test_res = strpos($page_html, 'My answer the third');
		$this->assertFalse($test_res);
	}
	
	function _lineCountAnswersInput()
	{
		mysql_query('DELETE FROM answers');
		mysql_query('DELETE FROM questions');
		
		$this->post('http://www.prapor.ru/edit.php?id=1');
		
		$this->setField('allow_poll_days', '0');
		$this->clickSubmit('Update poll');
		$this->assertField('allow_poll_days', '');
		
		$this->setField('allow_poll_days', '-37');
		$this->clickSubmit('Update poll');
		$this->assertField('allow_poll_days', '');
		
		$this->setField('allow_poll_days', '<!-- blabla -->');
		$this->clickSubmit('Update poll');
		$this->assertField('allow_poll_days', '');
		
		$this->setField('allow_poll_days', 'abc');
		$this->clickSubmit('Update poll');
		$this->assertField('allow_poll_days', '');
		
		$this->setField('allow_poll_days', '');
		$this->clickSubmit('Update poll');
		$this->assertField('allow_poll_days', '');
		
		$this->setField('allow_poll_days', '5');
		$this->clickSubmit('Update poll');
		$this->assertField('allow_poll_days', '5');
	}
	
	function _lineDaysCountInput()
	{
		mysql_query('DELETE FROM answers');
		mysql_query('DELETE FROM questions');
		
		$this->post('http://www.prapor.ru/edit.php?id=1');
		
		$this->setField('poll_ans_count', '5');
		$this->clickSubmit('Update poll');
		$this->assertField('poll_ans_count', '5');
		
		$this->setField('poll_ans_count', '1');
		$this->clickSubmit('Update poll');
		$this->assertField('poll_ans_count', '2');
		
		$this->setField('poll_ans_count', '-30');
		$this->clickSubmit('Update poll');
		$this->assertField('poll_ans_count', '2');
		
		$this->setField('poll_ans_count', '160');
		$this->clickSubmit('Update poll');
		$this->assertField('poll_ans_count', '50');
		
		$this->setField('poll_ans_count', '<!--adasdas-->');
		$this->clickSubmit('Update poll');
		$this->assertField('poll_ans_count', '2');
		
		$this->setField('poll_ans_count', 'abc');
		$this->clickSubmit('Update poll');
		$this->assertField('poll_ans_count', '2');
	}
	
	function _testDeletePollButton()
	{
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->setField('poll_ans_count', '4');
		$this->clickSubmit('Update poll');
		$this->setField('question_of_poll', 'My question');
		$this->setFieldById('poll_answer[0]', 'My answer the first');
		$this->setFieldById('poll_answer[1]', 'My answer the second');
		$this->setFieldById('poll_answer[2]', 'My answer the third');
		$this->setFieldById('poll_answer[3]', 'My answer the fourth');
		$this->clickSubmit('Submit');
		
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->clickSubmit('Delete poll');
		
		$this->assertField('question_of_poll', '');
		$this->assertFieldById('poll_answer[0]', '');
		$this->assertFieldById('poll_answer[1]', '');
		$this->assertField('poll_ans_count', '2');
	}
	
	function _showFlagAdminDisableSeeResults()
	{
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->setField('form[disable_see]', true);
		$this->clickSubmit('Save changes');
		
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->setField('poll_ans_count', '4');
		$this->clickSubmit('Update poll');
		$this->setField('question_of_poll', 'My question');
		$this->setFieldById('poll_answer[0]', 'My answer the first');
		$this->setFieldById('poll_answer[1]', 'My answer the second');
		$this->setFieldById('poll_answer[2]', 'My answer the third');
		$this->setFieldById('poll_answer[3]', 'My answer the fourth');
		$this->clickSubmit('Submit');
		
		mysql_query('UPDATE questions SET able_see="1" WHERE id_topic="1" ');
		mysql_query('DELETE FROM voting');
		mysql_query('INSERT INTO voting VALUES ("", "1", "3", "2"), ("", "1", "4", "1"), ("", "1", "5", "2"), ("", "1", "6", "2"); ');
		
		$page_html = $this->get('http://www.prapor.ru/viewtopic.php?id=1');
		
		$test_res = strpos($page_html, '<td width="10%">1</td>');
		$this->assertFalse($test_res);
		
		$test_res = strpos($page_html, '<td width="10%">3</td>');
		$this->assertFalse($test_res);
		
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->setField('form[disable_see]', false);
		$this->clickSubmit('Save changes');
		
		$page_html = $this->get('http://www.prapor.ru/viewtopic.php?id=1');
		
		$test_res = strpos($page_html, '<td width="10%">1</td>');
		$this->assertTrue($test_res);
		
		$test_res = strpos($page_html, '<td width="10%">3</td>');
		$this->assertTrue($test_res);
	}
	
	function _showFlagAdminRevotePoll()
	{
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->setField('form[disable_rev]', false);
		$this->clickSubmit('Save changes');
		
		mysql_query('UPDATE questions SET able_rev="1" WHERE id_topic="1" ');
		mysql_query('DELETE FROM voting');
		
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->setField('rad_poll', '0');
		$this->clickSubmit('Submit opinion');
		$this->assertText('Sending opinion . . .');
		
		$page_html = $this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$test_res = strpos($page_html, 'Revote poll');
		$this->assertTrue($test_res);
		
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->setField('form[disable_rev]', true);
		$this->clickSubmit('Save changes');
		
		mysql_query('DELETE FROM voting');
		
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->setField('rad_poll', '0');
		$this->clickSubmit('Submit opinion');
		$this->assertText('Sending opinion . . .');
		
		$this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$this->assertText('You opinion is already counted.');
	}
	
	function _lineAdminMaxAnswers()
	{
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->setField('form[max_ans]', '-19');
		$this->clickSubmit('Save changes');
		$this->assertText('The entered value exceeds the marginally allowed answer number. It was replaced by 50');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->assertField('form[max_ans]', '50');
		
		$this->setField('form[max_ans]', 'abcas');
		$this->clickSubmit('Save changes');
		$this->assertText('Redirecting');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->assertField('form[max_ans]', '50');
		
		$this->setField('form[max_ans]', '25');
		$this->clickSubmit('Save changes');
		$this->assertText('Redirecting');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->assertField('form[max_ans]', '25');
		
		$this->setField('form[max_ans]', '0');
		$this->clickSubmit('Save changes');
		$this->assertText('The entered value exceeds the marginally allowed answer number. It was replaced by 50');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->assertField('form[max_ans]', '25');
		
		$this->setField('form[max_ans]', '130');
		$this->clickSubmit('Save changes');
		$this->assertText('The entered value exceeds the maximally allowed answer number. It was replaced by 100');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->assertField('form[max_ans]', '25');
		
		$this->setField('form[max_ans]', '');
		$this->clickSubmit('Save changes');
		$this->assertText('Redirecting');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=features');
		$this->assertField('form[max_ans]', '25');
	}
	
	function _showFlagAdminEnablePolls()
	{
		mysql_query('UPDATE users SET registration_ip="0.0.0.1" WHERE id=2');
		
		$this->_logoutFromSite();
		
		$this->get('http://www.prapor.ru');
		$this->clickLink('Register');
		$this->setField('req_username', 'user');
		$this->setField('req_password1', 'user');
		$this->setField('req_password2', 'user');
		$this->setField('req_email1', 'user@yandex.ru');
		$this->clickSubmit('Register');
		$this->assertText('Registration complete. Logging in and redirecting');
		
		mysql_query('UPDATE users SET registration_ip="0.0.0.2" WHERE id=3');
		
		//There is test a flag
		$this->_logoutFromSite();
		$this->_loginToSiteAsUser('admin', 'admin');
		
		$this->get('http://www.prapor.ru/admin/groups.php?edit_group=3');
		$this->setField('poll_add', false);
		$this->setFieldById('fld14', false);
		$this->clickSubmit('Save');
		
		$this->_logoutFromSite();
		$this->_loginToSiteAsUser('user', 'user');
		
		$this->get('http://www.prapor.ru/post.php?fid=1');
		$this->assertNoText('Poll question');
		$this->assertNoText('Poll option');
		
		$this->_logoutFromSite();
		$this->_loginToSiteAsUser('admin', 'admin');
		
		$this->get('http://www.prapor.ru/admin/groups.php?edit_group=3');
		$this->setField('poll_add', true);
		$this->setFieldById('fld14', true);
		$this->clickSubmit('Save');
		
		$this->_logoutFromSite();
		$this->_loginToSiteAsUser('user', 'user');
		
		$this->get('http://www.prapor.ru/post.php?fid=1');
		$this->assertText('Poll question');
		$this->assertText('Poll option');
		
		$this->_logoutFromSite();
		$this->_loginToSiteAsUser('admin', 'admin');
	}
}
?>