<?php
class WebTestPunAttachmentByAdminUser extends WebTestCase
{
	function setUp()// ÔÓÍÊÖÈß ÏÅÐÅÄ ÒÅÑÒÎÌ
	{
		ini_set('max_execution_time', 400);// set time to more larger then was set..
		
		$this->_switchToWebTestingDb();// ÏÅÐÅÄÅË ÍÀ ÒÅÑÒÎÂÓÞ ÁÀÇÓ
		$this->_setupPunBB();
		$this->_loginToSiteAsUser('admin', 'admin');
		$this->_setupExtensionPunAttachment();
		$this->_logoutFromSite();
		
		mysql_query('UPDATE users SET registration_ip="0.0.0.1" WHERE id=2');
		
		$this->get('http://www.prapor.ru');
		$this->assertTitle('title');
		
		$this->clickLink('Register');
		$this->assertText('Register at title');
		
		$this->setField('req_username', 'user');
		$this->setField('req_password1', 'user');
		$this->setField('req_password2', 'user');
		$this->setField('req_email1', 'user@yandex.ru');
		
		$this->clickSubmit('Register');
		$this->assertText('Registration complete. Logging in and redirecting');
		
		mysql_query('UPDATE users SET registration_ip="0.0.0.2" WHERE id=3');
		
		//There is test a flag
		$this->_logoutFromSite();
	}
	
	function testOfIndexPage()
	{
		$this->_loginToSiteAsUser('admin', 'admin');
		$this->_lineAddFile();
		//$this->_showFlagAdminShowingPicturesMini();
		//$this->_linesAdminAttach();
		//$this->_lineAdminAddIconFile();
		$this->_lineFromToNumber();
		$this->_showFlagManagementShowOrphans();
		$this->_selectManagementSortDir();
		$this->_selectManagementOrderBy();
		$this->_selectManagementLineTopic();
		$this->_selectManagementLineAutor();
	}
	
	function tearDown()// ÔÓÍÊÖÈß ÏÎÑËÅ ÒÅÑÒÀ
	{
		$this->_switchToProductionDb();//ÏÅÐÅÄÅË ÍÀ ÎÑÍÎÂÍÓÞ ÁÀÇÓ
		
		ini_set('max_execution_time', 30);
	}
	
	function _switchToWebTestingDb()
	{
		$project_dir = dirname(__FILE__) . '\\';// ÐÀÁÎ×ÈÉ ÊÀÒÀËÎÃ
		$tests_dir = dirname(__FILE__) . '\tests\\';// ÊÀÒÀËÎÃ Ñ ÒÅÑÒÎÂÛÌÈ ÄÀÍÍÛÌÈ
		$this->_clearCachePun();
		$conn = mysql_connect("127.0.0.1", "root", ""); // ÍÅÇÍÀÞ ÊÀÊ Ñ ÊÎÍÔÈÃ ÔÀÉËÀ2 ÂÇßÒÜ ÄÀÍÍÛÅ ÍÓÆÍÛÅ
		// ÍÀ×ÀËÎ ÁËÎÊÀ ÏÅÐÂÎÍÀ×ÀËÜÍÛÕ ÓÑÒÀÍÎÂÎÊ ÁÀÇÛ ÄÀÍÍÛÕ
		
		mysql_query('CREATE DATABASE test_pun_poll_db');
		mysql_select_db('test_pun_poll_db', $conn);
		
		
		// ÊÎÍÅÖ ÁËÎÊÀ ÏÅÐÂÎÍÀ×ÀËÜÍÛÕ ÓÑÒÀÍÎÂÎÊ ÁÀÇÛ ÄÀÍÍÛÕ
		
		rename($project_dir . 'config.php', $project_dir . 'test.php~');// ÏÅÐÅÈÌÅÍÎÂÛÂÀÅÌ ÕÎÐÎØÈÉ ÊÎÍÔÈÃ Â ÄÐÓÃÎÉ
	}
	
	function _switchToProductionDb()
	{
		$project_dir = dirname(__FILE__) . '\\'; // ÓÑÒÀÍÎÂÊÀ ÐÀÁÎ×ÅÃÎ ÊÀÒÀËÎÃÀ
		// ÂÎÇÂÐÀÆÅÍÈÅ ÁÀÇÛ ÄÀÍÍÛÕ Â ÍÀ×ÀËÜÍÎÅ ÑÎÑÒÎßÍÈÅ ÍÀ×ÀËÎ
		
		mysql_query('DROP DATABASE test_pun_poll_db');
		
		// ÂÎÇÂÐÀÙÅÍÈÅ ÁÀÇÛ ÄÀÍÍÛÕ Â ÍÀ×ÀËÜÍÎÅ ÑÎÑÒÎßÍÈÅ ÊÎÍÅÖ
		$this->_clearCachePun();
		
		unlink($project_dir . 'config.php');
		rename($project_dir . 'test.php~', $project_dir . 'config.php'); // ÑÒÀÂÈÌ ÎÁÐÀÒÍÎ ÕÎÐÎØÈÉ ÊÎÍÔÈÃ
	}
	
	function _clearCachePun()
	{
		$project_dir = dirname(__FILE__) . '\\';// ÐÀÁÎ×ÈÉ ÊÀÒÀËÎÃ
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
	
	function _setupExtensionPunAttachment()
	{
		$this->get('http://www.prapor.ru/admin/extensions.php?install=pun_attachment');
		$this->clickSubmit('Install extension');
	}
	
	function _loginToSiteAsUser($username, $password)
	{
		$page_html = $this->get('http://www.prapor.ru');
		$this->assertTitle('title');
		$test_res = strpos('Logout', $page_html);
		
		if ($test_res)
		{
			$this->_logoutFromSite();
		}
		
		$this->get('http://www.prapor.ru');
		$this->assertTitle('title');
		$this->clickLink('Login');
		$this->assertText('Login to title');
		$this->setField('req_username', $username);
		$this->setField('req_password', $password);
		$this->clickSubmit('Login');
	}
	
	function _logoutFromSite()
	{
		$this->get('http://www.prapor.ru/');
		$this->clickLink('Logout');
	}
	
	function _lineAddFile()
	{
		$test_dir = dirname(__FILE__).'\tests\test_pun_attachment\\';
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->assertText('Attachment', 'The extension is enable. If not, then you don\'t have permision or extension don\'t installed.');
		$this->setField('attach_file', $test_dir.'first_file.pdf');
		$this->clickSubmit('Add file');
		
		$this->assertText('Existing attachments:');
		$this->assertText('first_file.pdf');
		$this->assertText(' kbytes, file has never been downloaded');
		
		$this->setField('attach_file', $test_dir.'second_file.jpg');
		$this->clickSubmit('Add file');
		
		$this->assertText('first_file.pdf');
		
		$this->assertField('delete_1', 'Delete');
		$this->assertField('delete_2', 'Delete');
		$this->clickSubmitByName('delete_1');
		
		$page_html = $this->get('http://www.prapor.ru/edit.php?id=1');
		
		$test_res = strpos($page_html, 'name="delete_1"');
		$this->assertFalse($test_res);
		
		$test_res = strpos($page_html, 'name="delete_2"');
		$this->assertTrue($test_res);
		
		$this->setField('attach_file', $test_dir.'aq3d.exe');
		$this->clickSubmit('Add file');
		
		$this->assertText('Warning! The following errors must be corrected before your message can be posted:');
		$this->assertText('The selected file was too large to upload. The server didn\'t allow the upload.');
		
		$this->setField('attach_file', $test_dir.'min_pic.JPG');
		$this->clickSubmit('Add file');
		
		$page_html = $this->get('http://www.prapor.ru/edit.php?id=1');
		$test_res = strpos($page_html, '<img src="http://www.prapor.ru/extensions/pun_attachment/img/image.png" width="15" height="15" alt="Attachment icon" />');
		$this->assertTrue($test_res);
		
		/*$this->setField('attach_file', 'C:\Webservers');
		$this->clickSubmit('Add file');
		$this->assertText('Warning! The following errors must be corrected before your message can be posted:');
		$this->assertText('You did not select a file for upload.');
		
		 ÎØÈÁÊÀ Â ÐÍÐ - ÏÎÄÊËÞ×ÅÍÈÅ ÍÅ ÑÓÙÅÑÒÂÓÞÙÅÃÎ ÔÀÉËÀ
		$this->setField('attach_file', 'C:\blablabla.pdf');
		$this->clickSubmit('Add file');
		$this->showSource();
		$this->assertText('Warning! The following errors must be corrected before your message can be posted:');
		$this->assertText('You did not select a file for upload.');
		
		
		$this->setField('attach_file', '<?php echo"My"?> <strong>answer</strong> <!-- the second');
		$this->clickSubmit('Add file');
		$this->assertText('Warning! The following errors must be corrected before your message can be posted:');
		$this->assertText('You did not select a file for upload.');
		*/
	}
	
	function _showFlagAdminShowingPicturesMini()
	{
		$this->get('http://www.prapor.ru/admin/options.php?section=attach');
		$this->assertText('Icon settings', 'This part attended on this page');
		$this->setField('form[use_icon]', true);
		$this->clickSubmit('Save changes');
		
		$page_html = $this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$test_res = strpos($page_html, '<img src="http://www.prapor.ru/extensions/pun_attachment/img/image.png" width="15" height="15" alt="Attachment icon" />');
		$this->assertTrue($test_res);
		
		$this->get('http://www.prapor.ru/admin/options.php?section=attach');
		$this->assertText('Icon settings', 'This part attended on this page');
		$this->setField('form[use_icon]', false);
		$this->clickSubmit('Save changes');
		
		$page_html = $this->get('http://www.prapor.ru/viewtopic.php?id=1');
		$test_res = strpos($page_html, '<img src="http://www.prapor.ru/extensions/pun_attachment/img/image.png" width="15" height="15" alt="Attachment icon" />');
		$this->assertFalse($test_res);
	}
	
	function _linesAdminAttach()
	{
		$max_size = intval(ini_get('upload_max_filesize')) * 1024 * 1024;
		
		$this->get('http://www.prapor.ru/admin/options.php?section=attach');
		$this->assertText('User attachments', 'This part attended on this page');
		$this->setField('form[max_size]', '-150000');
		$this->setField('form[small_height]', '-1500');
		$this->setField('form[small_width]', '-1500');
		$this->clickSubmit('Save changes');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=attach');
		$this->assertText('User attachments', 'This part attended on this page');
		$this->assertField('form[max_size]' , '150000');
		$this->assertField('form[small_height]' , '60');
		$this->assertField('form[small_width]' , '60');
		
		$this->setField('form[max_size]', '0');
		$this->setField('form[small_height]', '0');
		$this->setField('form[small_width]', '0');
		$this->clickSubmit('Save changes');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=attach');
		$this->assertText('User attachments', 'This part attended on this page');
		$this->assertField('form[max_size]' , $max_size);
		$this->assertField('form[small_height]' , '60');
		$this->assertField('form[small_width]' , '60');
		
		$this->setField('form[max_size]', 'abc');
		$this->setField('form[small_height]', 'abc');
		$this->setField('form[small_width]', 'abc');
		$this->clickSubmit('Save changes');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=attach');
		$this->assertText('User attachments', 'This part attended on this page');
		$this->assertField('form[max_size]' , $max_size);
		$this->assertField('form[small_height]' , '60');
		$this->assertField('form[small_width]' , '60');
		
		$this->setField('form[max_size]', '?> <!--');
		$this->setField('form[small_height]', '<!--');
		$this->setField('form[small_width]', '/*');
		$this->clickSubmit('Save changes');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=attach');
		$this->assertText('User attachments', 'This part attended on this page');
		$this->assertField('form[max_size]' , $max_size);
		$this->assertField('form[small_height]' , '60');
		$this->assertField('form[small_width]' , '60');
		
		$this->setField('form[max_size]', '');
		$this->setField('form[small_height]', '');
		$this->setField('form[small_width]', '');
		$this->clickSubmit('Save changes');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=attach');
		$this->assertText('User attachments', 'This part attended on this page');
		$this->assertField('form[max_size]' , $max_size);
		$this->assertField('form[small_height]' , '60');
		$this->assertField('form[small_width]' , '60');
	}
	
	function _lineAdminAddIconFile()
	{
		$this->get('http://www.prapor.ru/admin/options.php?section=attach');
		$this->assertText('Icon settings', 'This part attended on this page');
		$this->setField('add_field_icon', 'wmv');
		$this->setField('add_field_file', 'video2.png');
		$this->clickSubmit('Save changes');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=attach');
		$this->assertText('Icon settings', 'This part attended on this page');
		$this->assertText('The following icons are missing:');
		
		$this->setField('add_field_icon', 'avi');
		$this->setField('add_field_file', 'video2.png');
		$this->clickSubmit('Save changes');
		
		$page_html = $this->get('http://www.prapor.ru/admin/options.php?section=attach');
		$this->assertText('Icon settings', 'This part attended on this page');
		$test_res = strpos($page_html, 'attach_ext_14');
		$this->assertFalse($test_res);
		$test_res = strpos($page_html, 'attach_ico_14');
		$this->assertFalse($test_res);
	}
	
	function _lineFromToNumber()
	{
		$test_dir = dirname(__FILE__).'\tests\test_pun_attachment\\';
		$this->get('http://www.prapor.ru/edit.php?id=1');
		$this->assertText('Attachment', 'The extension is enable. If not, then you don\'t have permision or extension don\'t installed.');
		
		$this->setField('attach_file', $test_dir.'first_file.pdf');
		$this->clickSubmit('Add file');
		
		$this->setField('attach_file', $test_dir.'second_file.jpg');
		$this->clickSubmit('Add file');
		
		$this->setField('attach_file', $test_dir.'first_file.pdf');
		$this->clickSubmit('Add file');
		
		$this->setField('attach_file', $test_dir.'second_file.jpg');
		$this->clickSubmit('Add file');
		
		$this->setField('attach_file', $test_dir.'first_file.pdf');
		$this->clickSubmit('Add file');
		
		$this->setField('attach_file', $test_dir.'second_file.jpg');
		$this->clickSubmit('Add file');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=list_attach');
		$this->assertText('Attachments', 'The extension is enable. If not, then you don\'t have permision or extension don\'t installed.');
		$this->setFieldById('fld1', '2');
		$page_html = $this->clickSubmit('Apply');
		
		//print_r($this->_browser->_page->_raw);
		$test_res = strpos($page_html, '<td width="1%" scope="col">3</td>');
		$this->assertTrue($test_res);
		$test_res = strpos($page_html, '<td width="1%" scope="col">2</td>');
		$this->assertFalse($test_res);
		
		$this->setFieldById('fld1', '2');
		$this->setFieldById('fld2', '1');
		$page_html = $this->clickSubmit('Apply');
		
		$test_res = strpos($page_html, '<td width="1%" scope="col">3</td>');
		$this->assertTrue($test_res);
		$test_res = strpos($page_html, '<td width="1%" scope="col">4</td>');
		$this->assertFalse($test_res);
		
		$this->setFieldById('fld1', '-2');
		$this->setFieldById('fld2', '-1');
		$this->clickSubmit('Apply');
		
		$this->assertFieldById('fld1', 1);
		$this->assertFieldById('fld2', 50);
		
		$this->setFieldById('fld1', '0');
		$this->setFieldById('fld2', '0');
		$this->clickSubmit('Apply');
		
		$this->assertFieldById('fld1', 1);
		$this->assertFieldById('fld2', 50);
		
		$this->setFieldById('fld1', '<!--');
		$this->setFieldById('fld2', '?> <!--');
		$this->clickSubmit('Apply');
		
		$this->assertFieldById('fld1', 1);
		$this->assertFieldById('fld2', 50);
		
		$this->setFieldById('fld1', 'abc def');
		$this->setFieldById('fld2', 'def abc-123lkj');
		$this->clickSubmit('Apply');
		
		$this->assertFieldById('fld1', 1);
		$this->assertFieldById('fld2', 50);
		
		$this->setFieldById('fld1', '');
		$this->setFieldById('fld2', '');
		$this->clickSubmit('Apply');
		
		$this->assertFieldById('fld1', 1);
		$this->assertFieldById('fld2', 50);
	}
	
	function _showFlagManagementShowOrphans()
	{
		$test_dir = dirname(__FILE__).'\tests\test_pun_attachment\\';
		$this->get('http://www.prapor.ru/post.php?fid=1');
		$this->assertText('Compose your new topic', 'If this expression doesn\'t exist, then you not have permision to visit the page. Login please');
		
		$this->setField('req_subject', 'test topic 1');
		$this->setField('req_message', 'test message');
		
		$this->setField('attach_file', $test_dir.'first_file.pdf');
		$this->clickSubmit('Add file');
		
		$this->setField('attach_file', $test_dir.'second_file.jpg');
		$this->clickSubmit('Add file');
		
		$this->setField('attach_file', $test_dir.'first_file.pdf');
		$this->clickSubmit('Add file');
		
		$this->setField('attach_file', $test_dir.'second_file.jpg');
		$this->clickSubmit('Add file');
		
		$this->setField('attach_file', $test_dir.'first_file.pdf');
		$this->clickSubmit('Add file');
		
		$this->setField('attach_file', $test_dir.'second_file.jpg');
		$this->clickSubmit('Add file');
		
		$this->get('http://www.prapor.ru/admin/options.php?section=list_attach');
		$this->assertText('Attachments');
		$this->setField('form[orphans]', false);
		$page_html = $this->clickSubmit('Apply');
		$test_res = strpos($page_html, '<td class="tc4" scope="col">Test post</td>');
		$this->assertTrue($test_res);
		
		$this->setField('form[orphans]', true);
		$page_html = $this->clickSubmit('Apply');
		
		$test_res = strpos($page_html, '<td class="tc4" scope="col">Test post</td>');
		$this->assertFalse($test_res);
	}
	
	function _selectManagementSortDir()
	{
		$this->post('http://www.prapor.ru/admin/options.php?section=list_attach');
		$this->assertText('Attachments');
		
		$this->setField('form[orphans]', false);
		$this->setField('form[sort_dir]', false);
		$page_html = $this->clickSubmit('Apply');
		
		$test_res_first = strpos($page_html, '<td width="1%" scope="col">2</td>');
		$test_res_second = strpos($page_html, '<td width="1%" scope="col">15</td>');
		$this->assertTrue($test_res_first < $test_res_second);
		
		$this->setField('form[sort_dir]', true);
		
		$page_html = $this->clickSubmit('Apply');
		$test_res_first = strpos($page_html, '<td width="1%" scope="col">2</td>');
		$test_res_second = strpos($page_html, '<td width="1%" scope="col">15</td>');
		$this->assertTrue($test_res_first > $test_res_second);
	}
	
	function _selectManagementOrderBy()
	{
		$this->post('http://www.prapor.ru/admin/options.php?section=list_attach');
		$this->assertText('Attachments');
		
		$this->setField('form[sort_dir]', false);
		$this->setField('form[order]', 'id');
		$page_html = $this->clickSubmit('Apply');
		$test_res_first = strpos($page_html, '<td width="1%" scope="col">2</td>');
		$test_res_second = strpos($page_html, '<td width="1%" scope="col">15</td>');
		$this->assertTrue($test_res_first < $test_res_second);
		
		$this->setField('form[order]', 'filename');
		$page_html = $this->clickSubmit('Apply');
		$test_res_first = strpos($page_html, '<td width="1%" scope="col">2</td>');
		$test_res_second = strpos($page_html, '<td width="1%" scope="col">15</td>');
		$this->assertTrue($test_res_first < $test_res_second);
		
		$this->setField('form[order]', 'owner');
		$page_html = $this->clickSubmit('Apply');
		$test_res_first = strpos($page_html, '<td width="1%" scope="col">3</td>');
		$test_res_second = strpos($page_html, '<td width="1%" scope="col">2</td>');
		$this->assertTrue($test_res_first < $test_res_second);
		
		$this->setField('form[order]', 'uploaded_date');
		$page_html = $this->clickSubmit('Apply');
		$test_res_3 = strpos($page_html, '<td width="1%" scope="col">3</td>');
		$test_res_2 = strpos($page_html, '<td width="1%" scope="col">2</td>');
		$test_res_12 = strpos($page_html, '<td width="1%" scope="col">12</td>');
		$test_res_15 = strpos($page_html, '<td width="1%" scope="col">15</td>');
		$this->assertTrue($test_res_2 < $test_res_3);
		$this->assertTrue($test_res_3 < $test_res_12);
		$this->assertTrue($test_res_12 < $test_res_15);
		
		$this->setField('form[order]', 'mime');
		$page_html = $this->clickSubmit('Apply');
		$test_res_3 = strpos($page_html, '<td width="1%" scope="col">3</td>');
		$test_res_2 = strpos($page_html, '<td width="1%" scope="col">2</td>');
		$test_res_12 = strpos($page_html, '<td width="1%" scope="col">12</td>');
		$test_res_15 = strpos($page_html, '<td width="1%" scope="col">15</td>');
		$this->assertTrue($test_res_3 < $test_res_2);
		$this->assertTrue($test_res_3 < $test_res_12);
		$this->assertTrue($test_res_12 < $test_res_15);
		
		$this->setField('form[order]', 'topic_id');
		$page_html = $this->clickSubmit('Apply');
		$test_res_3 = strpos($page_html, '<td width="1%" scope="col">3</td>');
		$test_res_2 = strpos($page_html, '<td width="1%" scope="col">2</td>');
		$test_res_12 = strpos($page_html, '<td width="1%" scope="col">12</td>');
		$test_res_15 = strpos($page_html, '<td width="1%" scope="col">15</td>');
		$this->assertTrue($test_res_12 < $test_res_15);
		$this->assertTrue($test_res_15 < $test_res_3);
		$this->assertTrue($test_res_3 < $test_res_2);
		
		$this->setField('form[order]', 'post_id');
		$page_html = $this->clickSubmit('Apply');
		$test_res_3 = strpos($page_html, '<td width="1%" scope="col">3</td>');
		$test_res_2 = strpos($page_html, '<td width="1%" scope="col">2</td>');
		$test_res_12 = strpos($page_html, '<td width="1%" scope="col">12</td>');
		$test_res_15 = strpos($page_html, '<td width="1%" scope="col">15</td>');
		$this->assertTrue($test_res_12 < $test_res_15);
		$this->assertTrue($test_res_15 < $test_res_3);
		$this->assertTrue($test_res_3 < $test_res_2);
		
		$this->setField('form[order]', 'downloads');
		$page_html = $this->clickSubmit('Apply');
		$test_res_3 = strpos($page_html, '<td width="1%" scope="col">3</td>');
		$test_res_2 = strpos($page_html, '<td width="1%" scope="col">2</td>');
		$test_res_12 = strpos($page_html, '<td width="1%" scope="col">12</td>');
		$test_res_15 = strpos($page_html, '<td width="1%" scope="col">15</td>');
		$this->assertTrue($test_res_3 < $test_res_2);
		$this->assertTrue($test_res_2 < $test_res_12);
		$this->assertTrue($test_res_12 < $test_res_15);
	}
	
	function _selectManagementLineTopic()
	{
		$this->post('http://www.prapor.ru/admin/options.php?section=list_attach');
		$this->assertText('Attachments');
		
		$this->setField('form[topic]', 'Test post');
		$page_html = $this->clickSubmit('Apply');
		$test_res = strpos($page_html, '<td class="tc4" scope="col"><b>None<b></td>');
		$this->assertFalse($test_res);
	}
	
	function _selectManagementLineAutor()
	{
		$this->_loginToSiteAsUser('user', 'user');
		$this->post('http://www.prapor.ru/viewtopic.php?id=1');
		$this->assertText('Test post');
		$this->clickLink('Post reply');
		$this->showSource();
		
		$this->setField('req_message', 'This is message from user. Please test this message in database.');
	}
}
?>