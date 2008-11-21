-- phpMyAdmin SQL Dump
-- version 2.6.1
-- http://www.phpmyadmin.net
-- 
-- Хост: localhost
-- Время создания: Окт 10 2008 г., 17:22
-- Версия сервера: 5.0.45
-- Версия PHP: 5.2.4
-- 
-- БД: `pun_test_database`
-- 

-- --------------------------------------------------------

-- 
-- Структура таблицы `bans`
-- 

CREATE TABLE `bans` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `username` varchar(200) default NULL,
  `ip` varchar(255) default NULL,
  `email` varchar(80) default NULL,
  `message` varchar(255) default NULL,
  `expire` int(10) unsigned default NULL,
  `ban_creator` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- Дамп данных таблицы `bans`
-- 


-- --------------------------------------------------------

-- 
-- Структура таблицы `categories`
-- 

CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `cat_name` varchar(80) NOT NULL default 'New Category',
  `disp_position` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- 
-- Дамп данных таблицы `categories`
-- 

INSERT INTO `categories` VALUES (1, 'Test category', 1);

-- --------------------------------------------------------

-- 
-- Структура таблицы `censoring`
-- 

CREATE TABLE `censoring` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `search_for` varchar(60) NOT NULL default '',
  `replace_with` varchar(60) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- Дамп данных таблицы `censoring`
-- 


-- --------------------------------------------------------

-- 
-- Структура таблицы `config`
-- 

CREATE TABLE `config` (
  `conf_name` varchar(255) NOT NULL default '',
  `conf_value` text,
  PRIMARY KEY  (`conf_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Дамп данных таблицы `config`
-- 

INSERT INTO `config` VALUES ('o_cur_version', '1.3 RC');
INSERT INTO `config` VALUES ('o_board_title', 'title');
INSERT INTO `config` VALUES ('o_board_desc', 'description');
INSERT INTO `config` VALUES ('o_default_timezone', '0');
INSERT INTO `config` VALUES ('o_time_format', 'H:i:s');
INSERT INTO `config` VALUES ('o_date_format', 'Y-m-d');
INSERT INTO `config` VALUES ('o_check_for_updates', '1');
INSERT INTO `config` VALUES ('o_check_for_versions', '1');
INSERT INTO `config` VALUES ('o_timeout_visit', '1800');
INSERT INTO `config` VALUES ('o_timeout_online', '300');
INSERT INTO `config` VALUES ('o_redirect_delay', '1');
INSERT INTO `config` VALUES ('o_show_version', '0');
INSERT INTO `config` VALUES ('o_show_user_info', '1');
INSERT INTO `config` VALUES ('o_show_post_count', '1');
INSERT INTO `config` VALUES ('o_signatures', '1');
INSERT INTO `config` VALUES ('o_smilies', '1');
INSERT INTO `config` VALUES ('o_smilies_sig', '1');
INSERT INTO `config` VALUES ('o_make_links', '1');
INSERT INTO `config` VALUES ('o_default_lang', 'English');
INSERT INTO `config` VALUES ('o_default_style', 'Oxygen');
INSERT INTO `config` VALUES ('o_default_user_group', '3');
INSERT INTO `config` VALUES ('o_topic_review', '15');
INSERT INTO `config` VALUES ('o_disp_topics_default', '30');
INSERT INTO `config` VALUES ('o_disp_posts_default', '25');
INSERT INTO `config` VALUES ('o_indent_num_spaces', '4');
INSERT INTO `config` VALUES ('o_quote_depth', '3');
INSERT INTO `config` VALUES ('o_quickpost', '1');
INSERT INTO `config` VALUES ('o_users_online', '1');
INSERT INTO `config` VALUES ('o_censoring', '0');
INSERT INTO `config` VALUES ('o_ranks', '1');
INSERT INTO `config` VALUES ('o_show_dot', '0');
INSERT INTO `config` VALUES ('o_topic_views', '1');
INSERT INTO `config` VALUES ('o_quickjump', '1');
INSERT INTO `config` VALUES ('o_gzip', '0');
INSERT INTO `config` VALUES ('o_additional_navlinks', '');
INSERT INTO `config` VALUES ('o_report_method', '0');
INSERT INTO `config` VALUES ('o_regs_report', '0');
INSERT INTO `config` VALUES ('o_mailing_list', 'admin@yandex.ru');
INSERT INTO `config` VALUES ('o_avatars', '1');
INSERT INTO `config` VALUES ('o_avatars_dir', 'img/avatars');
INSERT INTO `config` VALUES ('o_avatars_width', '60');
INSERT INTO `config` VALUES ('o_avatars_height', '60');
INSERT INTO `config` VALUES ('o_avatars_size', '10240');
INSERT INTO `config` VALUES ('o_search_all_forums', '1');
INSERT INTO `config` VALUES ('o_sef', 'Default');
INSERT INTO `config` VALUES ('o_admin_email', 'admin@yandex.ru');
INSERT INTO `config` VALUES ('o_webmaster_email', 'admin@yandex.ru');
INSERT INTO `config` VALUES ('o_subscriptions', '1');
INSERT INTO `config` VALUES ('o_smtp_host', NULL);
INSERT INTO `config` VALUES ('o_smtp_user', NULL);
INSERT INTO `config` VALUES ('o_smtp_pass', NULL);
INSERT INTO `config` VALUES ('o_smtp_ssl', '0');
INSERT INTO `config` VALUES ('o_regs_allow', '1');
INSERT INTO `config` VALUES ('o_regs_verify', '0');
INSERT INTO `config` VALUES ('o_announcement', '0');
INSERT INTO `config` VALUES ('o_announcement_heading', 'Sample announcement');
INSERT INTO `config` VALUES ('o_announcement_message', '<p>Enter your announcement here.</p>');
INSERT INTO `config` VALUES ('o_rules', '0');
INSERT INTO `config` VALUES ('o_rules_message', 'Enter your rules here.');
INSERT INTO `config` VALUES ('o_maintenance', '0');
INSERT INTO `config` VALUES ('o_maintenance_message', 'The forums are temporarily down for maintenance. Please try again in a few minutes.<br />\n<br />\n/Administrator');
INSERT INTO `config` VALUES ('p_message_bbcode', '1');
INSERT INTO `config` VALUES ('p_message_img_tag', '1');
INSERT INTO `config` VALUES ('p_message_all_caps', '1');
INSERT INTO `config` VALUES ('p_subject_all_caps', '1');
INSERT INTO `config` VALUES ('p_sig_all_caps', '1');
INSERT INTO `config` VALUES ('p_sig_bbcode', '1');
INSERT INTO `config` VALUES ('p_sig_img_tag', '0');
INSERT INTO `config` VALUES ('p_sig_length', '400');
INSERT INTO `config` VALUES ('p_sig_lines', '4');
INSERT INTO `config` VALUES ('p_allow_banned_email', '1');
INSERT INTO `config` VALUES ('p_allow_dupe_email', '0');
INSERT INTO `config` VALUES ('p_force_guest_email', '1');

-- --------------------------------------------------------

-- 
-- Структура таблицы `extension_hooks`
-- 

CREATE TABLE `extension_hooks` (
  `id` varchar(50) NOT NULL default '',
  `extension_id` varchar(50) NOT NULL default '',
  `code` text,
  `installed` int(10) unsigned NOT NULL default '0',
  `priority` tinyint(1) unsigned NOT NULL default '5',
  PRIMARY KEY  (`id`,`extension_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Дамп данных таблицы `extension_hooks`
-- 

--INSERT INTO `extension_hooks` VALUES ('acg_del_cat_pre_header_load', 'pun_admin_simpletest', '$file_handle = fopen($_SERVER[''DOCUMENT_ROOT''].''/extensions/pun_admin_simpletest/cache_simpletest.php'', ''r'');\n			$number = fgets($file_handle);\n			fclose($file_handle);\n			\n			$forum_page[''form_action''] = forum_link($forum_url[''admin_categories''].''?simpletest=''.$number);\n			$forum_page[''hidden_fields''][''csrf_token''] = ''<input type="hidden" name="csrf_token" value="''.generate_form_token($forum_page[''form_action'']).''" />'';', 1225470631, 5);
--INSERT INTO `extension_hooks` VALUES ('acg_pre_header_load', 'pun_admin_simpletest', '$file_handle = fopen($_SERVER[''DOCUMENT_ROOT''].''/extensions/pun_admin_simpletest/cache_simpletest.php'', ''r'');\r\n			$number = fgets($file_handle);\r\n			fclose($file_handle);\r\n			\r\n			$pun_line_action = $forum_url[''admin_categories''].''?action=foo&simpletest='';\r\n			$pun_line_action .= $number;\r\n			\r\n			$forum_page[''form_action''] = forum_link($pun_line_action);\r\n			$forum_page[''hidden_fields''] = array(\r\n				''csrf_token''	=> ''<input type="hidden" name="csrf_token" value="''.generate_form_token($forum_page[''form_action'']).''" />''\r\n			);', 1225470631, 5);
--INSERT INTO `extension_hooks` VALUES ('li_forgot_pass_pre_header_load', 'pun_admin_simpletest', '$file_handle = fopen($_SERVER[''DOCUMENT_ROOT''].''/extensions/pun_admin_simpletest/cache_simpletest.php'', ''r'');\n			$number = fgets($file_handle);\n			fclose($file_handle);\n			\n			$forum_page[''form_action''] = forum_link($forum_url[''request_password''].''&simpletest=''.$number);', 1225470631, 5);
--INSERT INTO `extension_hooks` VALUES ('rg_register_pre_header_load', 'pun_admin_simpletest', '$file_handle = fopen($_SERVER[''DOCUMENT_ROOT''].''/extensions/pun_admin_simpletest/cache_simpletest.php'', ''r'');\n			$number = fgets($file_handle);\n			fclose($file_handle);\n			\n			$forum_page[''form_action''] .= ''&simpletest=''.$number;', 1225470631, 5);
--INSERT INTO `extension_hooks` VALUES ('li_login_pre_header_load', 'pun_admin_simpletest', '$file_handle = fopen($_SERVER[''DOCUMENT_ROOT''].''/extensions/pun_admin_simpletest/cache_simpletest.php'', ''r'');\n			$number = fgets($file_handle);\n			fclose($file_handle);\n			\n			$forum_page[''form_action''] = forum_link($forum_url[''login''].''?simpletest=''.$number);\n			$forum_page[''hidden_fields''][''csrf_token''] = ''<input type="hidden" name="csrf_token" value="''.generate_form_token($forum_page[''form_action'']).''" />'';', 1225470631, 5);

INSERT INTO `extension_hooks` VALUES ('fn_generate_form_token_start', 'pun_admin_simpletest','$file_handle = fopen($_SERVER[''DOCUMENT_ROOT''].''/extensions/pun_admin_simpletest/cache_simpletest.php'', ''r'');\r\n			$number = fgets($file_handle);\r\n			fclose($file_handle);\r\n			\r\n			if (strstr($target_url, ''simpletest'') == false)\r\n			{\r\n				if (stripos($target_url, ''.ru/'', (strlen($target_url) - 5)))\r\n					$target_url .= ''?simpletest=''.$number;\r\n				else if (strpos($target_url, ''?''))\r\n					$target_url .= ''&simpletest=''.$number;\r\n				else if (stripos($target_url, ''.php'', (strlen($target_url) - 5)))\r\n					$target_url .= ''?simpletest=''.$number;\r\n				else\r\n					$target_url .= ''/?simpletest=''.$number;\r\n			}\r\n			\r\n			$return = sha1(str_replace(''&amp;'', ''&'', $target_url).$forum_user[''csrf_token'']);' , 1225470631, 5);

-- --------------------------------------------------------

-- 
-- Структура таблицы `extensions`
-- 

CREATE TABLE `extensions` (
  `id` varchar(50) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `version` varchar(25) NOT NULL default '',
  `description` text,
  `author` varchar(50) NOT NULL default '',
  `uninstall` text,
  `uninstall_note` text,
  `disabled` tinyint(1) NOT NULL default '0',
  `dependencies` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Дамп данных таблицы `extensions`
-- 

INSERT INTO `extensions` VALUES ('pun_admin_simpletest', 'Simpletest', '0.1', 'Automatic testing all extensions.', 'PunBB Development Team', NULL, NULL, 0, '||');

-- --------------------------------------------------------

-- 
-- Структура таблицы `forum_perms`
-- 

CREATE TABLE `forum_perms` (
  `group_id` int(10) NOT NULL default '0',
  `forum_id` int(10) NOT NULL default '0',
  `read_forum` tinyint(1) NOT NULL default '1',
  `post_replies` tinyint(1) NOT NULL default '1',
  `post_topics` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`group_id`,`forum_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Дамп данных таблицы `forum_perms`
-- 


-- --------------------------------------------------------

-- 
-- Структура таблицы `forums`
-- 

CREATE TABLE `forums` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `forum_name` varchar(80) NOT NULL default 'New forum',
  `forum_desc` text,
  `redirect_url` varchar(100) default NULL,
  `moderators` text,
  `num_topics` mediumint(8) unsigned NOT NULL default '0',
  `num_posts` mediumint(8) unsigned NOT NULL default '0',
  `last_post` int(10) unsigned default NULL,
  `last_post_id` int(10) unsigned default NULL,
  `last_poster` varchar(200) default NULL,
  `sort_by` tinyint(1) NOT NULL default '0',
  `disp_position` int(10) NOT NULL default '0',
  `cat_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- 
-- Дамп данных таблицы `forums`
-- 

INSERT INTO `forums` VALUES (1, 'Test forum', 'This is just a test forum', NULL, NULL, 1, 1, 1223644428, 1, 'admin', 0, 1, 1);

-- --------------------------------------------------------

-- 
-- Структура таблицы `groups`
-- 

CREATE TABLE `groups` (
  `g_id` int(10) unsigned NOT NULL auto_increment,
  `g_title` varchar(50) NOT NULL default '',
  `g_user_title` varchar(50) default NULL,
  `g_moderator` tinyint(1) NOT NULL default '0',
  `g_mod_edit_users` tinyint(1) NOT NULL default '0',
  `g_mod_rename_users` tinyint(1) NOT NULL default '0',
  `g_mod_change_passwords` tinyint(1) NOT NULL default '0',
  `g_mod_ban_users` tinyint(1) NOT NULL default '0',
  `g_read_board` tinyint(1) NOT NULL default '1',
  `g_view_users` tinyint(1) NOT NULL default '1',
  `g_post_replies` tinyint(1) NOT NULL default '1',
  `g_post_topics` tinyint(1) NOT NULL default '1',
  `g_edit_posts` tinyint(1) NOT NULL default '1',
  `g_delete_posts` tinyint(1) NOT NULL default '1',
  `g_delete_topics` tinyint(1) NOT NULL default '1',
  `g_set_title` tinyint(1) NOT NULL default '1',
  `g_search` tinyint(1) NOT NULL default '1',
  `g_search_users` tinyint(1) NOT NULL default '1',
  `g_send_email` tinyint(1) NOT NULL default '1',
  `g_edit_subjects_interval` smallint(6) NOT NULL default '300',
  `g_post_flood` smallint(6) NOT NULL default '30',
  `g_search_flood` smallint(6) NOT NULL default '30',
  `g_email_flood` smallint(6) NOT NULL default '60',
  PRIMARY KEY  (`g_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- 
-- Дамп данных таблицы `groups`
-- 

INSERT INTO `groups` VALUES (1, 'Administrators', 'Administrator', 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0);
INSERT INTO `groups` VALUES (2, 'Guest', NULL, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0);
INSERT INTO `groups` VALUES (3, 'Members', NULL, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 300, 60, 30, 60);
INSERT INTO `groups` VALUES (4, 'Moderators', 'Moderator', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0);

-- --------------------------------------------------------

-- 
-- Структура таблицы `online`
-- 

CREATE TABLE `online` (
  `user_id` int(10) unsigned NOT NULL default '1',
  `ident` varchar(200) NOT NULL default '',
  `logged` int(10) unsigned NOT NULL default '0',
  `idle` tinyint(1) NOT NULL default '0',
  `csrf_token` varchar(40) NOT NULL default '',
  `prev_url` varchar(255) default NULL,
  UNIQUE KEY `online_user_id_ident_idx` (`user_id`,`ident`(25)),
  KEY `online_user_id_idx` (`user_id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;

-- 
-- Дамп данных таблицы `online`
-- 

INSERT INTO `online` VALUES (1, '127.0.0.1', 1223644774, 0, 'bea8e1edc0ca0d44dfbda00ed951f95e49e75024', 'http://prapor.ru/');

-- --------------------------------------------------------

-- 
-- Структура таблицы `posts`
-- 

CREATE TABLE `posts` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `poster` varchar(200) NOT NULL default '',
  `poster_id` int(10) unsigned NOT NULL default '1',
  `poster_ip` varchar(39) default NULL,
  `poster_email` varchar(80) default NULL,
  `message` text,
  `hide_smilies` tinyint(1) NOT NULL default '0',
  `posted` int(10) unsigned NOT NULL default '0',
  `edited` int(10) unsigned default NULL,
  `edited_by` varchar(200) default NULL,
  `topic_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `posts_topic_id_idx` (`topic_id`),
  KEY `posts_multi_idx` (`poster_id`,`topic_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- 
-- Дамп данных таблицы `posts`
-- 

INSERT INTO `posts` VALUES (1, 'admin', 2, '127.0.0.1', NULL, 'If you are looking at this (which I guess you are), the install of PunBB appears to have worked! Now log in and head over to the administration control panel to configure your forum.', 0, 1223644428, NULL, NULL, 1);

-- --------------------------------------------------------

-- 
-- Структура таблицы `ranks`
-- 

CREATE TABLE `ranks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `rank` varchar(50) NOT NULL default '',
  `min_posts` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- 
-- Дамп данных таблицы `ranks`
-- 

INSERT INTO `ranks` VALUES (1, 'New member', 0);
INSERT INTO `ranks` VALUES (2, 'Member', 10);

-- --------------------------------------------------------

-- 
-- Структура таблицы `reports`
-- 

CREATE TABLE `reports` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `post_id` int(10) unsigned NOT NULL default '0',
  `topic_id` int(10) unsigned NOT NULL default '0',
  `forum_id` int(10) unsigned NOT NULL default '0',
  `reported_by` int(10) unsigned NOT NULL default '0',
  `created` int(10) unsigned NOT NULL default '0',
  `message` text,
  `zapped` int(10) unsigned default NULL,
  `zapped_by` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `reports_zapped_idx` (`zapped`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- Дамп данных таблицы `reports`
-- 


-- --------------------------------------------------------

-- 
-- Структура таблицы `search_cache`
-- 

CREATE TABLE `search_cache` (
  `id` int(10) unsigned NOT NULL default '0',
  `ident` varchar(200) NOT NULL default '',
  `search_data` text,
  PRIMARY KEY  (`id`),
  KEY `search_cache_ident_idx` (`ident`(8))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Дамп данных таблицы `search_cache`
-- 


-- --------------------------------------------------------

-- 
-- Структура таблицы `search_matches`
-- 

CREATE TABLE `search_matches` (
  `post_id` int(10) unsigned NOT NULL default '0',
  `word_id` int(10) unsigned NOT NULL default '0',
  `subject_match` tinyint(1) NOT NULL default '0',
  KEY `search_matches_word_id_idx` (`word_id`),
  KEY `search_matches_post_id_idx` (`post_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Дамп данных таблицы `search_matches`
-- 

INSERT INTO `search_matches` VALUES (1, 1, 0);
INSERT INTO `search_matches` VALUES (1, 2, 0);
INSERT INTO `search_matches` VALUES (1, 3, 0);
INSERT INTO `search_matches` VALUES (1, 4, 0);
INSERT INTO `search_matches` VALUES (1, 5, 0);
INSERT INTO `search_matches` VALUES (1, 6, 0);
INSERT INTO `search_matches` VALUES (1, 7, 0);
INSERT INTO `search_matches` VALUES (1, 8, 0);
INSERT INTO `search_matches` VALUES (1, 9, 0);
INSERT INTO `search_matches` VALUES (1, 10, 0);
INSERT INTO `search_matches` VALUES (1, 11, 0);
INSERT INTO `search_matches` VALUES (1, 12, 0);
INSERT INTO `search_matches` VALUES (1, 13, 0);
INSERT INTO `search_matches` VALUES (1, 14, 0);
INSERT INTO `search_matches` VALUES (1, 15, 0);
INSERT INTO `search_matches` VALUES (1, 16, 0);
INSERT INTO `search_matches` VALUES (1, 17, 0);
INSERT INTO `search_matches` VALUES (1, 18, 0);
INSERT INTO `search_matches` VALUES (1, 19, 0);
INSERT INTO `search_matches` VALUES (1, 20, 0);
INSERT INTO `search_matches` VALUES (1, 21, 0);
INSERT INTO `search_matches` VALUES (1, 22, 0);
INSERT INTO `search_matches` VALUES (1, 23, 0);
INSERT INTO `search_matches` VALUES (1, 25, 1);
INSERT INTO `search_matches` VALUES (1, 24, 1);

-- --------------------------------------------------------

-- 
-- Структура таблицы `search_words`
-- 

CREATE TABLE `search_words` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `word` varchar(20) character set utf8 collate utf8_bin NOT NULL default '',
  PRIMARY KEY  (`word`),
  KEY `search_words_id_idx` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 AUTO_INCREMENT=26 ;

-- 
-- Дамп данных таблицы `search_words`
-- 

INSERT INTO `search_words` VALUES (1, 0x796f75);
INSERT INTO `search_words` VALUES (2, 0x617265);
INSERT INTO `search_words` VALUES (3, 0x6c6f6f6b696e67);
INSERT INTO `search_words` VALUES (4, 0x74686973);
INSERT INTO `search_words` VALUES (5, 0x7768696368);
INSERT INTO `search_words` VALUES (6, 0x6775657373);
INSERT INTO `search_words` VALUES (7, 0x746865);
INSERT INTO `search_words` VALUES (8, 0x696e7374616c6c);
INSERT INTO `search_words` VALUES (9, 0x70756e6262);
INSERT INTO `search_words` VALUES (10, 0x61707065617273);
INSERT INTO `search_words` VALUES (11, 0x68617665);
INSERT INTO `search_words` VALUES (12, 0x776f726b6564);
INSERT INTO `search_words` VALUES (13, 0x6e6f77);
INSERT INTO `search_words` VALUES (14, 0x6c6f67);
INSERT INTO `search_words` VALUES (15, 0x616e64);
INSERT INTO `search_words` VALUES (16, 0x68656164);
INSERT INTO `search_words` VALUES (17, 0x6f766572);
INSERT INTO `search_words` VALUES (18, 0x61646d696e697374726174696f6e);
INSERT INTO `search_words` VALUES (19, 0x636f6e74726f6c);
INSERT INTO `search_words` VALUES (20, 0x70616e656c);
INSERT INTO `search_words` VALUES (21, 0x636f6e666967757265);
INSERT INTO `search_words` VALUES (22, 0x796f7572);
INSERT INTO `search_words` VALUES (23, 0x666f72756d);
INSERT INTO `search_words` VALUES (24, 0x74657374);
INSERT INTO `search_words` VALUES (25, 0x706f7374);

-- --------------------------------------------------------

-- 
-- Структура таблицы `subscriptions`
-- 

CREATE TABLE `subscriptions` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `topic_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`topic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Дамп данных таблицы `subscriptions`
-- 


-- --------------------------------------------------------

-- 
-- Структура таблицы `topics`
-- 

CREATE TABLE `topics` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `poster` varchar(200) NOT NULL default '',
  `subject` varchar(255) NOT NULL default '',
  `posted` int(10) unsigned NOT NULL default '0',
  `first_post_id` int(10) unsigned NOT NULL default '0',
  `last_post` int(10) unsigned NOT NULL default '0',
  `last_post_id` int(10) unsigned NOT NULL default '0',
  `last_poster` varchar(200) default NULL,
  `num_views` mediumint(8) unsigned NOT NULL default '0',
  `num_replies` mediumint(8) unsigned NOT NULL default '0',
  `closed` tinyint(1) NOT NULL default '0',
  `sticky` tinyint(1) NOT NULL default '0',
  `moved_to` int(10) unsigned default NULL,
  `forum_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `topics_forum_id_idx` (`forum_id`),
  KEY `topics_moved_to_idx` (`moved_to`),
  KEY `topics_last_post_idx` (`last_post`),
  KEY `topics_first_post_id_idx` (`first_post_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- 
-- Дамп данных таблицы `topics`
-- 

INSERT INTO `topics` VALUES (1, 'admin', 'Test post', 1223644428, 1, 1223644428, 1, 'admin', 0, 0, 0, 0, NULL, 1);

-- --------------------------------------------------------

-- 
-- Структура таблицы `users`
-- 

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `group_id` int(10) unsigned NOT NULL default '3',
  `username` varchar(200) NOT NULL default '',
  `password` varchar(40) NOT NULL default '',
  `salt` varchar(12) default NULL,
  `email` varchar(80) NOT NULL default '',
  `title` varchar(50) default NULL,
  `realname` varchar(40) default NULL,
  `url` varchar(100) default NULL,
  `jabber` varchar(80) default NULL,
  `icq` varchar(12) default NULL,
  `msn` varchar(80) default NULL,
  `aim` varchar(30) default NULL,
  `yahoo` varchar(30) default NULL,
  `location` varchar(30) default NULL,
  `signature` text,
  `disp_topics` tinyint(3) unsigned default NULL,
  `disp_posts` tinyint(3) unsigned default NULL,
  `email_setting` tinyint(1) NOT NULL default '1',
  `notify_with_post` tinyint(1) NOT NULL default '0',
  `auto_notify` tinyint(1) NOT NULL default '0',
  `show_smilies` tinyint(1) NOT NULL default '1',
  `show_img` tinyint(1) NOT NULL default '1',
  `show_img_sig` tinyint(1) NOT NULL default '1',
  `show_avatars` tinyint(1) NOT NULL default '1',
  `show_sig` tinyint(1) NOT NULL default '1',
  `access_keys` tinyint(1) NOT NULL default '0',
  `timezone` float NOT NULL default '0',
  `dst` tinyint(1) NOT NULL default '0',
  `time_format` int(10) unsigned NOT NULL default '0',
  `date_format` int(10) unsigned NOT NULL default '0',
  `language` varchar(25) NOT NULL default 'English',
  `style` varchar(25) NOT NULL default 'Oxygen',
  `num_posts` int(10) unsigned NOT NULL default '0',
  `last_post` int(10) unsigned default NULL,
  `last_search` int(10) unsigned default NULL,
  `last_email_sent` int(10) unsigned default NULL,
  `registered` int(10) unsigned NOT NULL default '0',
  `registration_ip` varchar(39) NOT NULL default '0.0.0.0',
  `last_visit` int(10) unsigned NOT NULL default '0',
  `admin_note` varchar(30) default NULL,
  `activate_string` varchar(80) default NULL,
  `activate_key` varchar(8) default NULL,
  PRIMARY KEY  (`id`),
  KEY `users_registered_idx` (`registered`),
  KEY `users_username_idx` (`username`(8))
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- 
-- Дамп данных таблицы `users`
-- 

INSERT INTO `users` VALUES (1, 2, 'Guest', 'Guest', NULL, 'Guest', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 'English', 'Oxygen', 0, NULL, NULL, NULL, 0, '0.0.0.0', 0, NULL, NULL, NULL);
INSERT INTO `users` VALUES (2, 1, 'admin', '923d8f6e16713cc97b7c99c6ae9b001eba7f7672', 'CI*KFiwcEZ/+', 'admin@yandex.ru', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 'English', 'Oxygen', 1, 1223644428, NULL, NULL, 1223644428, '0.0.0.1', 1223644428, NULL, NULL, NULL);
