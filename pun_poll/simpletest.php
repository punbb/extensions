<?php
define("PUN_SIMPLETEST_RUN", 1);

if (!defined('SIMPLE_TEST')) {
	define('SIMPLE_TEST', $_SERVER['DOCUMENT_ROOT'].'/extensions/pun_admin_simpletest/simpletest');
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

class WebTestPunPollByAdminUser extends WebTestCase
{
	//Function before testing function
	function setUp()
	{
		ini_set('max_execution_time', 600);// set time to more larger then was set..
		
		$this->_setupPunBB();
		
		$this->get('http://www.prapor.ru');
		$this->clickLink('db_update.php');
		$this->clickSubmit('Start update');
		$this->clickLink('Click here to continue');
		$this->clickLink('Click here to continue');
		$this->clickLink('Click here to continue');
		$this->clickLink('Click here to continue');
		$this->_loginToSiteAsUser('admin', 'admin');
		$this->_setupExtensions();
		$this->_testHelpers();
		$this->_logoutFromSite();
	}
	//Testing function
	function testOfIndexPage()
	{
		$this->get('http://www.prapor.ru');
		$this->assertTitle('title', 'Make sure the PunBB is setup correctly. It can\'t work');
		$this->_loginToSiteAsUser('admin', 'admin');
		$this->_testCreatePoll();
		/*
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
		*/
	}
	//Function after testing function
	function tearDown()
	{
		$this->_clearCachePun();
		
		require($_SERVER['DOCUMENT_ROOT'].'/config.php');
		$db_name = 'pun_test_database';
		mysql_query('DROP DATABASE '.$db_name);
		
		ini_set('max_execution_time', 30);
	}
	
	function _testHelpers()
	{
		/*
		Testing helpers (part 1)
		- add new account
		- add new category
		- add new forum
		- add new topic
		- add new post 
		Access control (admin only) 
		*/
		
		//ADD USER
		
		$this->_logoutFromSite();
		
		$this->post('http://www.prapor.ru');
		$this->clickLink('Register');
		$this->assertText('Register at title', 'You may be already logged.');
		$this->setFieldById('fld1', 'PunBB user');
		$this->setFieldById('fld2', 'punbbuser');
		$this->setFieldById('fld3', 'punbbuser');
		$this->setFieldById('fld4', 'pun_us@ya.ru');
		$this->clickSubmit('Register');
		$this->assertText('Registration complete. Logging in and redirecting', 'There are errors in creating new account.');
		
		mysql_query('UPDATE users SET registration_ip="0.0.0.2" WHERE id=3');
		
		$this->_logoutFromSite();
		$this->_loginToSiteAsUser('admin', 'admin');
		
		$this->post('http://www.prapor.ru');
		
		//ADD CATEGORY
		$this->get('http://www.prapor.ru/admin/categories.php');
		$this->assertText('Add category', 'You don\'t have permission to enter on this page.');
		$this->setField('new_cat_name', 'pun_test_category');
		$this->setField('position', '2');
		$this->clickSubmit('Add category');
		
		$this->assertText('Category added. Redirecting', 'Errors in creating category or you don\'t have permissions');
		
		//ADD FORUM
		$this->get('http://www.prapor.ru/admin/forums.php');
		$this->assertText('Add a new forum to the selected category at the specified position', 'You aren\'t a admin. Please login as administrator.');
		$this->setField('forum_name', 'Pun test forum');
		$this->setField('position', '2');
		$this->clickSubmit('Add forum');
		$this->assertText('Forum added.', 'Errors in creating new forum.');
		
		//ADD TOPIC
		$this->get('http://www.prapor.ru/post.php?fid=2');
		$this->assertText('Compose and post your new topic', 'You don\'t have permissions to visit this page.');
		$this->setField('req_subject', 'Test subject of second forum');
		$this->setField('req_message', 'This is the first message of second forum. The next writing text is for automatic test Simpletest.');
		$this->clickSubmit('Preview topic');
		$this->assertText('Preview your new topic', 'Error in preview function');
		$this->clickSubmit('Submit topic');
		$this->assertText('Post entered.', 'The new topic don\'t created.');
		
		$this->get('http://www.prapor.ru/viewtopic.php?id=2');
		$this->assertText('Test subject of second forum', 'This topic don\'t created.');
		$this->assertText('This is the first message of second forum. The next writing text is for automatic test Simpletest.', 'This topic don\'t created.');
		
		//ADD POST
		$this->clickLink('Post reply');
		$this->assertText('Compose and post your new reply', 'Chto to ne tak');
		$this->setField('req_message', 'Second message of second forum forum.');
		$this->clickSubmit('Preview reply');
		$this->assertText('Second message of second forum forum.', 'Error in preview function');
		$this->clickSubmit('Submit reply');
		$this->assertText('Post entered.', 'The new topic don\'t created.');
		
		$this->get('http://www.prapor.ru/viewtopic.php?pid=3#p3');
		$this->assertText('Second message of second forum forum.', 'This topic don\'t created.');
		
		/*
		Testing helpers (part 2)
		Manage added units
		- manage post
		- manage topic
		- manage forum
		- manage category
		- manage user
		*/
		
		//MANAGE POST
		$this->_logoutFromSite();
		$this->get('http://www.prapor.ru/edit.php?id=3');
		$this->assertNoText('Edit message', 'You do not have permissions on this page. Please login in to the site.');
		//$this->assertField('req_message', 'Second message of second forum forum.');
		//$this->showSource();
		$this->_loginToSiteAsUser('admin', 'admin');
		$this->get('http://www.prapor.ru/edit.php?id=3');
		$this->assertText('Edit message', 'You do not have permissions on this page. Please login in to the site.');
		$this->assertField('req_message', 'Second message of second forum forum.', 'Eto PLOCHO!!!!');
		$this->setField('req_message', 'Second CHANGED message of second forum forum. :)');
		
		$page_gen = $this->clickSubmit('Preview reply');
		$pos = strpos($page_gen, 'src="http://www.prapor.ru/img/smilies/smile.png"');
		$this->assertEqual($pos, true, 'Do not work optional hide smiles, because it is false');
		
		//$this->assertText('src="http://www.prapor.ru/img/smilies/smile.png"', 'Do not work optional hide smiles, because it is false');
		
		$this->setField('hide_smilies', true);
		
		$page_gen = $this->clickSubmit('Preview reply');
		$pos = strpos($page_gen, 'src="http://www.prapor.ru/img/smilies/smile.png"');
		$this->assertEqual($pos, false, 'Do not work optional hide smiles, because it is false');
		
		//$this->assertNoText('src="http://www.prapor.ru/img/smilies/smile.png"', 'Do not work optional hide smiles, because it is true');
		
		$this->setField('req_message', 'Second CHANGED message of second forum forum.');
		$this->clickSubmit('Submit reply');
		$this->assertText('Post updated.', 'There are errors in update post.');
		
		
		/*
		Testing helpers (part 3)
		- remove post
		- remove topic
		- remove forum
		- remove category
		- remove user
		Access control (admin only) 
		*/
		
		//REMOVE POST
		$this->get('http://www.prapor.ru/delete.php?id=3');
		$this->assertText('Delete post by admin posted', 'You don\' have permissions to visit this page.');
		$this->clickSubmit('Delete post');
		$this->assertText('Post deleted.');
		
		//REMOVE TOPIC
		$this->get('http://www.prapor.ru/delete.php?id=2');
		$this->assertText('Delete topic by admin', 'You don\' have permissions to visit this page. ');
		$this->clickSubmit('Delete topic');
		$this->assertText('Topic deleted.');
		
		//REMOVE FORUM
		$this->get('http://www.prapor.ru/admin/forums.php?del_forum=2');
		$this->assertText('You are deleting the forum', 'You don\' have permissions to visit this page. ');
		$this->clickSubmit('Delete forum');
		$this->assertText('Forum deleted.');
		
		//REMOVE CATEGORY
		$this->get('http://www.prapor.ru/admin/categories.php');
		$this->assertText('Delete category (together with all forums and posts it contains)', 'You don\' have permissions to visit this page. ');
		$this->setField('cat_to_delete', 'pun_test_category');
		$this->clickSubmit('Delete category');
		$this->assertText('You are deleting the category "pun_test_category"', 'This category doesn\'t exist.');
		$this->clickSubmit('Delete category');
		$this->assertText('Category deleted.');
		
		//REMOVE USER
		$this->get('http://www.prapor.ru/profile.php?action=delete_user&id=3');
		$this->assertText('Welcome to PunBB user\'s profile', 'You don\' have permissions to visit this page or user doesn\'t exist.');
		$this->assertText('Once deleted a user and/or their posts cannot be restored', 'You don\' have permissions to visit this page or user doesn\'t exist.');
		$this->clickSubmit('Submit');
		$this->assertText('User deleted', 'Errors in deleting user.');
	}
	
	function _clearCachePun()
	{
		define('FORUM_CACHE_DIR', $_SERVER['DOCUMENT_ROOT'].'/cache/');
		$d = dir(FORUM_CACHE_DIR);
		
		while (($entry = $d->read()) !== false)
		{
			if (is_file(FORUM_CACHE_DIR.$entry) && is_writable(FORUM_CACHE_DIR.$entry) && (substr($entry, 0, 6)  == 'cache_') && (substr($entry, -4) == '.php'))
				unlink(FORUM_CACHE_DIR.$entry);
		}
		
		$d->close();
	}
	
	function _setupPunBB()
	{
		define('PUN_SIMPLETEST_RUN', 1);
		
		require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
		
		$this->_clearCachePun();
		$conn = mysql_connect($db_host, $db_username, $db_password);
		mysql_query('CREATE DATABASE '.$db_name);
		mysql_select_db($db_name, $conn);
		echo '<br>'.$db_host;
		echo '<br>Name of database '.$db_name;
		echo '<br>'.$db_username;
		
		system('/usr/local/mysql5/bin/mysql --database="'.$db_name.'" --host="'.$db_host.'" --user="'.$db_username.'" --password="'.$db_password.'" --default-character-set=cp1251 < /home/prapor.ru/www/extensions/pun_admin_simpletest/Dump/dump.sql');
	}
	
	function _setupExtensions()
	{
		$this->get('http://www.prapor.ru/admin/extensions.php?install=pun_poll');
		$this->clickSubmit('Install extension');
		$this->assertText('Extension installed', 'The extension is not installed. Please check you permissions on this page');
	}
	
	function _loginToSiteAsUser($username, $password)
	{
		$this->get('http://www.prapor.ru');
		$this->clickLink('Login');
		$this->assertText('Login to title', 'Check that your login is correct');
		$this->setField('req_username', $username);
		$this->setField('req_password', $password);
		$this->clickSubmit('Login');
	}
	
	function _testCreatePoll()
	{
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->assertText('Poll question', 'Make sure setup extension correct. May be you don\'have permission to visit this page.');
		$this->setField('question_of_poll', 'My question');
		$this->setFieldById('poll_answer[0]', 'My answer the first');
		$this->setFieldById('poll_answer[1]', 'My answer the second');
		$this->click('Submit');
		$this->assertText('Redirecting');
	}
	
	function _testViewCreatingInfo1()// œ–Œ—ÃŒ“– ƒ¿ÕÕ€’ œ≈–¬Œ√Œ “≈—“»–Œ¬¿Õ»ﬂ
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
		$this->post('http://www.prapor.ru');
		$this->clickLink('Logout');
	}
	
	function _viewEditGuest()
	{
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->assertText('You do not have permission to access this page.');
	}
	
	function _testViewCreatingInfo2()// œ–Œ—ÃŒ“– √ŒÀŒ—Œ¬¿Õ»ﬂ  ¿  √Œ—“ﬂ
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
	‘Î‡„Ë Ì‡ ÒÚ‡ÌËˆÂ Ì‡ÒÚÓÂÍ ‡‰ÏËÌ‡
	
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
		
		//¬‚Ó‰ Ó·˚˜ÌÓ„Ó ÚÂÍÒÚ‡
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
		
		//¬‚Ó‰ ÒÔÂˆË‡Î¸Ì˚ı ÒËÏ‚ÓÎÓ‚
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
		
		//¬‚Ó‰ ÔÛÒÚ˚ı ÒÚÓÍ
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
		
		$this->get('http://www.prapor.ru/edit.php?id=1');
		
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
		
		$this->get('http://www.prapor.ru/edit.php?id=1');
		
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
		
		mysql_query('UPDATE users SET registration_ip="0.0.0.3" WHERE id=4');
		
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

class AllTests extends GroupTest
{
	function AllTests()
	{
		$this->GroupTest('Test extension pun_poll at PunBB');
		$this->addTestClass(WebTestPunPollByAdminUser);
	}
}

$test =& new AllTests();

if (SimpleReporter::inCli())
{
	exit ($test->run(new TextReporter()) ? 0 : 1);
}

$test->run(new HtmlReporter());

?>