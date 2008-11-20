<?php
  
function create_stat()
{   
 global $forum_db;
 global $forum_user;
 global $forum_url;
 global $forum_config;
 $pun_stat = array();
 $flag1 = 0;
//получаем значение флагов из таблицы statistcs_vars
     $query = array(
            'SELECT'    => 's.value',
            'FROM'        => 'statistics_vars AS s',
            'WHERE'        => 's.name=\'flag_reg_user\''
     );
     $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
     $mas = $forum_db->fetch_assoc($result);
     $flag_reg_user = $mas['value'];
     $query = array(
            'SELECT'    => 's.value',
            'FROM'        => 'statistics_vars AS s',
            'WHERE'        => 's.name=\'flag_inout_user\''
     );
     $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
     $mas = $forum_db->fetch_assoc($result);
      $flag_inout_user = $mas['value'];   
      $query = array(
            'SELECT'    => 's.value',
            'FROM'        => 'statistics_vars AS s',
            'WHERE'        => 's.name=\'flag_creat_post\''
     );
     $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
     $mas = $forum_db->fetch_assoc($result);
     $flag_creat_post = $mas['value'];   
     $query = array(
            'SELECT'    => 's.value',
            'FROM'        => 'statistics_vars AS s',
            'WHERE'        => 's.name=\'flag_creat_topic\''
     );
     $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
     $mas = $forum_db->fetch_assoc($result);
     $flag_creat_topic = $mas['value'];   
     $query = array(
            'SELECT'    => 's.value',
            'FROM'        => 'statistics_vars AS s',
            'WHERE'        => 's.name=\'flag_creat_forum\''
     );
     $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
     $mas = $forum_db->fetch_assoc($result);
     $flag_creat_forum = $mas['value'];   
     $query = array(
            'SELECT'    => 's.value',
            'FROM'        => 'statistics_vars AS s',
            'WHERE'        => 's.name=\'flag_timestamp_over\''
     );
     $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
     $mas = $forum_db->fetch_assoc($result);
     $flag_timestamp_over = $mas['value'];                      //количество секунд после которого будет производиться полное обновление кэша
//проверяем значение параметра update в таблице statistcs_vars
     $query = array(
            'SELECT'    => 's.value',
            'FROM'        => 'statistics_vars AS s',
            'WHERE'        => 's.name=\'update\''
     );
     $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
     $mas = $forum_db->fetch_assoc($result);
     $date_in_sec = mktime(date('y'), date('m'), date('d'));
     if ( $mas['value'] < $date_in_sec ) 
     {
// дропаем сущ. таблицы          
         $query = array(
            'DELETE'    => 'statistics_idforums'
         );             
         $forum_db->query_build($query) or error(__FILE__, __LINE__);
         $query = array(
            'DELETE'    => 'statistics_idtopics'
         );             
         $forum_db->query_build($query) or error(__FILE__, __LINE__);
         $query = array(
            'DELETE'    => 'statistics_idposts'
         );             
         $forum_db->query_build($query) or error(__FILE__, __LINE__);
         $query = array(
            'DELETE'    => 'statistics_idregistered'
         );             
         $forum_db->query_build($query) or error(__FILE__, __LINE__);
                            
// ЗАПОЛНЕНИЕ ИНДЕКСНЫХ ТАБЛИЦ   
// ФОРУМЫ 
         $query = array(
            'SELECT'    => 's.id',
            'FROM'        => 'forums AS s'
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
              
         for ($i = 0; $i < $result->num_rows ; $i = $i +1)
         {
            $mas = $forum_db->fetch_assoc($result);
            $id_forum = $mas['id']; 
            $query = array(
                'INSERT'    => 'id',
                'INTO'    => 'statistics_idforums',
                'VALUES'    => '\''.$id_forum.'\''
            );                   
            $forum_db->query_build($query) or error(__FILE__, __LINE__);       
         }
// ТОПИКИ
         $query = array(
            'SELECT'    => 's.id',
            'FROM'        => 'topics AS s'
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
              
         for ($i = 0; $i < $result->num_rows ; $i = $i +1)
         {
            $mas = $forum_db->fetch_assoc($result); 
            $id = $mas['id'];  
            $query = array(
                'INSERT'    => 'id',
                'INTO'    => 'statistics_idtopics',
                'VALUES'    => '\''.$id.'\''
            );                   
            $forum_db->query_build($query) or error(__FILE__, __LINE__);       
         }   
// ПОСТЫ
         $query = array(
            'SELECT'    => 's.id',
            'FROM'        => 'posts AS s'
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
              
         for ($i = 0; $i < $result->num_rows ; $i = $i +1)
         {
            $mas = $forum_db->fetch_assoc($result); 
            $id = $mas['id']; 
            $query = array(
                'INSERT'    => 'id',
                'INTO'    => 'statistics_idposts',
                'VALUES'    => '\''.$id.'\''
            );                   
            $forum_db->query_build($query) or error(__FILE__, __LINE__);       
         }
// ЗАРЕГЕСТ. ПОЛЬЗОВАТЕЛИ
         $query = array(
            'SELECT'    => 's.id',
            'FROM'        => 'users AS s'
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
              
         for ($i = 0; $i < $result->num_rows ; $i = $i +1)
         {
            $mas = $forum_db->fetch_assoc($result); 
            $id = $mas['id'];
            $query = array(
                'INSERT'    => 'id',
                'INTO'    => 'statistics_idregistered',
                'VALUES'    => '\''.$id.'\''
            );                   
            $forum_db->query_build($query) or error(__FILE__, __LINE__);       
         }
// Занесение нового значение переменной update    
         $query = array(
            'UPDATE'    => 'statistics_vars',
            'SET'    => 'value=\''.$date_in_sec.'\'',
            'WHERE'    => 'name=\'update\''
         );
         $forum_db->query_build($query) or error(__FILE__, __LINE__);                                 
     } 
         
//проверяем наличие файла кэша для статистики
     $fh = @fopen(FORUM_CACHE_DIR.'cache_stat.php', 'r');
     if ($fh) 
     {
        include FORUM_CACHE_DIR.'cache_stat.php';   
        $pun_stat = $pun_stat1;
        $date_in_sec = time();
        $date_in_sec_1 = $date_in_sec - $flag_timestamp_over;  
//если время полного обновления пришло,выставляем все флаги 
        if ($pun_stat['timestamp'] < $date_in_sec_1)
         {  $flag_reg_user = $flag_inout_user =  $flag_creat_post =  $flag_creat_topic = $flag_creat_forum = $flag1 =  1;     }
     }              
//если файл кэша статистики не существует
     else { $flag_reg_user = $flag_inout_user =  $flag_creat_post =  $flag_creat_topic = $flag_creat_forum = $flag1 = 1;  }                                                                  
     
      function function_stat_online()
      {
         global $forum_db;
         global $forum_user;
         global $forum_url;
         global $forum_config; 
         if ($forum_config['o_users_online'] == '1')
         {
            // Fetch users online info and generate strings for output
            $query = array(
                'SELECT'    => 'o.user_id, o.ident',
                'FROM'        => 'online AS o',
                'WHERE'        => 'o.idle=0',
                'ORDER BY DECR'    => 'o.user_id'
            );
            $result = $forum_db->query_build($query, true) or error(__FILE__, __LINE__);
            $num_guests = 0;
            $users = array();
            $numer_of_users = 0;
             while ($pun_user_online = $forum_db->fetch_assoc($result))
             {
                    if ($pun_user_online['user_id'] > 1)
                    {   
                        $numer_of_users = $numer_of_users + 1;
                        if ($numer_of_users < 11)
                        $users[] = ($forum_user['g_view_users'] == '1') ? '<a href="'.forum_link($forum_url['user'], $pun_user_online['user_id']).'">'.forum_htmlencode($pun_user_online['ident']).'</a>' : forum_htmlencode($pun_user_online['ident']);
                    }
                    else
                        ++$num_guests;
             }
             $online_all = $num_guests +  count($users);
         }    
         //MAX VISITORS
         $query = array(
                'SELECT'    => 's.value',
                'FROM'        => 'statistics_vars AS s',
                'WHERE'        => 's.name=\'max_visitors\''
         );
         $result = $forum_db->query_build($query, true) or error(__FILE__, __LINE__);
         $mas = $forum_db->fetch_assoc($result);
         $i_max_visitors = $mas['value'];
         $query = array(
                'SELECT'    => 's.value',
                'FROM'        => 'statistics_vars AS s',
                'WHERE'        => 's.name=\'max_visitors_date\''
         );
         $result = $forum_db->query_build($query, true) or error(__FILE__, __LINE__);
         $mas = $forum_db->fetch_assoc($result);
         $i_max_visitors_date = $mas['value'];
      
         if ($online_all > $i_max_visitors)
         {
            $i_max_visitors = $online_all;
            $i_max_visitors_date = date('Y-M-d');                            
            $query = array(
                    'UPDATE'    => 'statistics_vars',
                    'SET'        => 'value=\''.$i_max_visitors.'\'',
                    'WHERE'        => 'name=\'max_visitors\''
            );
            $forum_db->query_build($query) or error(__FILE__, __LINE__); 
            $query = array(
                    'UPDATE'    => 'statistics_vars',
                    'SET'        => 'value=\''.$i_max_visitors_date.'\'',
                    'WHERE'        => 'name=\'max_visitors_date\''
            );
            $forum_db->query_build($query) or error(__FILE__, __LINE__);
         }                
         $mas[0] = $online_all;   $mas[1] = $num_guests; $mas[2] = $users;  $mas[3] = $i_max_visitors;$mas[4] = $i_max_visitors_date; 
         return $mas;
      }                      
     
     if ($flag_reg_user == 1)              //РЕГИСТРАЦИЯ ПОЛЬЗОВАТЕЛЕЙЙ, проверка флага
     {
     //all registered users                 
         $query = array(
                'SELECT'    => 'Count(id)',
                'FROM'        => 'users',
                 'WHERE'        => 'email!=\'Guest\''  
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         $mas = $forum_db->fetch_assoc($result);   
         $pun_stat['i_reg_users'] = $mas['Count(id)'];    
         
    //last registered user         
         $j = $pun_stat['i_reg_users']+1;
         $query = array(
                'SELECT'    => 'id, username',
                'FROM'        => 'users',
                'WHERE'        => 'id=\''.$j.'\''  
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         $mas = $forum_db->fetch_assoc($result); 
         $last_reg_userid = $mas['id'];
         $pun_stat['last_reg_user'] = $mas['username'];
         $pun_stat['last_reg_user_link'] = ($forum_user['g_view_users'] == '1') ? '<a href="'.forum_link($forum_url['user'], $last_reg_userid).'">'.forum_htmlencode($pun_stat['last_reg_user']).'</a>' : forum_htmlencode($pun_stat['last_reg_user']);                                
         $mas_online = function_stat_online(); 
         $pun_stat['online_all'] = $mas_online[0];   $pun_stat['num_guests'] = $mas_online[1]; $pun_stat['users'] = $mas_online[2]; 
         $pun_stat['i_max_visitors'] = $mas_online[3]; $pun_stat['i_max_visitors_date'] = $mas_online[4]; 
         
         // ЗАРЕГЕСТ ПОЛЬЗОВАТЕЛИ
         $query = array(
                'SELECT'    => 'id',
                'FROM'        => 'users'
         );
         $result_now =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         
         $query = array(
                'SELECT'    => 'id',
                'FROM'        => 'statistics_idregistered'
         );
         $result_last =  $forum_db->query_build($query) or error(__FILE__, __LINE__);         
         $mas_now = array(); 
         $mas_last = array(); 
         for ($i = 0; $i < $result_now->num_rows; $i = $i +1)
         {
            $mas = $forum_db->fetch_assoc($result_now);
            $mas_now[$i] = $mas['id'];  
         }
         for ($i = 0; $i < $result_last->num_rows; $i = $i +1)
         {
            $mas =  $forum_db->fetch_assoc($result_last); 
            $mas_last[$i] =  $mas['id'] ;
         }
         $mas_result = array_diff($mas_now, $mas_last);
         $pun_stat['i_registered_today'] = count($mas_result);     
     }   
     
     if ($flag_inout_user == 1)                           //ВЫВОД ИЛИ ВХОД ПОЛЬЗОВАТЕЛЕЙ, проверка флага
     {
         $mas_online = function_stat_online(); 
         $pun_stat['online_all'] = $mas_online[0];   $pun_stat['num_guests'] = $mas_online[1]; $pun_stat['users'] = $mas_online[2];  
         $pun_stat['i_max_visitors'] = $mas_online[3]; $pun_stat['i_max_visitors_date'] = $mas_online[4];                
     }
     
     if ($flag_creat_post == 1)                            //ДОБАВЛЕНИЕ/УДАЛЕНИЕ ПОСТА, проверка флага
     {
     //all posts
         $query = array(
                'SELECT'    => 'Count(id)',
                'FROM'        => 'posts'
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         $mas = $forum_db->fetch_assoc($result);   
         $pun_stat['i_posts'] = $mas['Count(id)']; 
         
         if (($pun_stat['i_posts']==1)||($pun_stat['i_posts']==0)) 
            $pun_stat['text_posts'] = 'post';
         else
            $pun_stat['text_posts'] = 'posts';    
     //last created post                                           
         $query = array(
                'SELECT'    => 'id,poster,poster_id',
                'FROM'        => 'posts',
                'ORDER BY'        => 'id DESC'  ,
                'LIMIT'   => '1'
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         $mas = $forum_db->fetch_assoc($result); 
         $last_post_user = $mas['poster'];
         $pun_stat['last_post_link'] = '<a href="'.forum_link($forum_url['post'], $mas['id']).'">'.forum_htmlencode('Last post').'</a>';  
         $pun_stat['last_post_user_link'] = '<a href="'.forum_link($forum_url['user'], $mas['poster_id']).'">'.forum_htmlencode($last_post_user).'</a>';   
         
         // ПОСТЫ
         $query = array(
                'SELECT'    => 'id',
                'FROM'        => 'posts'
         );
         $result_now =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         
         $query = array(
                'SELECT'    => 'id',
                'FROM'        => 'statistics_idposts'
         );
         $result_last =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
           
         $mas_now = array(); 
         $mas_last = array(); 
         for ($i = 0; $i < $result_now->num_rows; $i = $i +1)
         {
            $mas = $forum_db->fetch_assoc($result_now);
            $mas_now[$i] = $mas['id'];  
         }
         for ($i = 0; $i < $result_last->num_rows; $i = $i +1)
         {
            $mas =  $forum_db->fetch_assoc($result_last); 
            $mas_last[$i] =  $mas['id'] ;
         }
         $mas_result = array();
         $mas_result[0] = 1;
         $mas_result = array_diff($mas_now, $mas_last);
         $pun_stat['i_posts_today'] = count($mas_result);                                  
     }
                                                             
     if ($flag_creat_topic == 1)                            //ДОБАВЛЕНИЕ/УДАЛЕНИЕ ТОПИКА, проверка флага
     {
     //all topics
         $query = array(
                'SELECT'    => 'Count(id)',
                'FROM'        => 'topics'
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         $mas = $forum_db->fetch_assoc($result);   
         $pun_stat['i_topics'] = $mas['Count(id)']; 
         if (($pun_stat['i_topics']==1)||($pun_stat['i_topics']==0)) 
            $pun_stat['text_topics'] = 'topic';
         else
            $pun_stat['text_topics'] = 'topics';       
         
     //last created topic
         
         $query = array(
                'SELECT'    => 'id,poster',
                'FROM'        => 'topics',
                'ORDER BY'        => 'id DESC',
                'LIMIT'   => '1'
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         $mas = $forum_db->fetch_assoc($result); 
         $last_topic_user = $mas['poster'];  
         $query = array(
                'SELECT'    => 'id',
                'FROM'        => 'users',
                'WHERE'        => 'username=\''.$last_topic_user.'\''
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         $mas1 = $forum_db->fetch_assoc($result);
         $pun_stat['last_topic_link'] =  '<a href="'.forum_link($forum_url['topic'], $mas['id']).'">'.forum_htmlencode('Last topic').'</a>';   
         $pun_stat['last_topic_user_link'] = '<a href="'.forum_link($forum_url['user'], $mas1['id']).'">'.forum_htmlencode($last_topic_user).'</a>';          
         
         // ТОПИКИ
         $query = array(
                'SELECT'    => 'id',
                'FROM'        => 'topics'
         );
         $result_now =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         
         $query = array(
                'SELECT'    => 'id',
                'FROM'        => 'statistics_idtopics'
         );
         $result_last =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         
         $mas_now = array(); 
         $mas_last = array(); 
        for ($i = 0; $i < $result_now->num_rows; $i = $i +1)
         {
            $mas = $forum_db->fetch_assoc($result_now);
            $mas_now[$i] = $mas['id'];  
         }
         for ($i = 0; $i < $result_last->num_rows; $i = $i +1)
         {
            $mas =  $forum_db->fetch_assoc($result_last); 
            $mas_last[$i] =  $mas['id'] ;
         }
         $mas_result = array_diff($mas_now, $mas_last);
         $pun_stat['i_topics_today'] = count($mas_result);     
         
     }
     
     if ($flag_creat_forum == 1)                            //СОЗДАНИЕ/УДАЛЕНИЕ ФОРУМА, проверка флага
     {
     //all forums      
         $query = array(
                'SELECT'    => 'Count(id)',
                'FROM'        => 'forums'
         );
         $result =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         $mas = $forum_db->fetch_assoc($result);   
         $pun_stat['i_forums'] = $mas['Count(id)']; 
         
         if (($pun_stat['i_forums']==1)||($pun_stat['i_forums']==0)) 
            $pun_stat['text_forums'] = 'forum';
         else
            $pun_stat['text_forums'] = 'forums';   
         
         // ФОРУМЫ
         $query = array(
                'SELECT'    => 'id',
                'FROM'        => 'forums'
         );
         $result_now =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
         
         $query = array(
                'SELECT'    => 'id',
                'FROM'        => 'statistics_idforums'
         );
         $result_last =  $forum_db->query_build($query) or error(__FILE__, __LINE__);
          
         $mas_now = array();   
         $mas_last = array();
         for ($i = 0; $i < $result_now->num_rows; $i = $i +1)
         {
            $mas = $forum_db->fetch_assoc($result_now);
            $mas_now[$i] = $mas['id'];  
         }
         for ($i = 0; $i < $result_last->num_rows; $i = $i +1)
         {
            $mas =  $forum_db->fetch_assoc($result_last); 
            $mas_last[$i] =  $mas['id'] ;
         }
         $mas_result = array_diff($mas_now, $mas_last);
         $pun_stat['i_forums_today'] = count($mas_result);         
                       
     }
//если произошло полное обновление кэша, то записываем новое знгачение     
     if ($flag1 == 1)  
        $pun_stat['timestamp'] =  time();  
     $fh = @fopen(FORUM_CACHE_DIR.'cache_stat.php', 'wb');
     if (!$fh)
        error('Unable to write configuration cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
//запись в файл кэша
     fwrite($fh, '<?php'."\n\n".'define(\'FORUM_STAT_LOADED\', 1);'."\n\n".'$pun_stat1 = '.var_export($pun_stat, true).';'."\n\n".'function get_cache_stat(){global $pun_stat1; return $pun_stat1;}'."\n\n".'?>');
     fclose($fh);       
     
//Обновление значений переменных в таблице statistics_vars
      $query = array(
            'UPDATE'    => 'statistics_vars',
            'SET'    => 'value=\'0\'',
            'WHERE'    => 'name=\'flag_reg_user\''.'or name=\'flag_inout_user\''.'or name=\'flag_creat_post\''.'or name=\'flag_creat_topic\''.'or name=\'flag_creat_forum\''
         );
      $forum_db->query_build($query) or error(__FILE__, __LINE__);    
           
return $pun_stat;     
}       
?>
