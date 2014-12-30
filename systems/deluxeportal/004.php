<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| ####################################################################
|| # vBulletin Impex
|| # ----------------------------------------------------------------
|| # All PHP code in this file is Copyright 2000-2014 vBulletin Solutions Inc.
|| # This code is made available under the Modified BSD License -- see license.txt
|| # http://www.vbulletin.com 
|| ####################################################################
\*======================================================================*/
/**
* deluxeportal_004 Import User module
*
* @package			ImpEx.deluxeportal
* @date				$Date: 2011-01-03 14:45:32 -0500 (Mon, 03 Jan 2011) $
* @copyright		http://www.vbulletin.com/license.html
*
*/
class deluxeportal_004 extends deluxeportal_000
{
	var $_version 		= '0.0.1';
	var $_dependent 	= '003';
	var $_modulestring 	= 'Import User';


	function deluxeportal_004()
	{
		// Constructor
	}


	function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		if ($this->check_order($sessionobject,$this->_dependent))
		{
			if ($this->_restart)
			{
				if ($this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_users'))
				{
					$displayobject->display_now('<h4>Imported users have been cleared</h4>');
					$this->_restart = true;
				}
				else
				{
					$sessionobject->add_error('fatal',
											 $this->_modulestring,
											 get_class($this) . '::restart failed , clear_imported_users','Check database permissions');
				}
			}


			// Start up the table
			$displayobject->update_basic('title','Import User');
			$displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
			$displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
			$displayobject->update_html($displayobject->make_hidden_code('import_user','working'));
			$displayobject->update_html($displayobject->make_table_header($this->_modulestring));


			// Ask some questions
			$displayobject->update_html($displayobject->make_input_code('Users to import per cycle (must be greater than 1)','userperpage',250));


			// End the table
			$displayobject->update_html($displayobject->do_form_footer('Continue','Reset'));


			// Reset/Setup counters for this
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
			$sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
			$sessionobject->add_session_var('userstartat','0');
			$sessionobject->add_session_var('userdone','0');
		}
		else
		{
			// Dependant has not been run
			$displayobject->update_html($displayobject->do_form_header('index',''));
			$displayobject->update_html($displayobject->make_description('<p>This module is dependent on <i><b>' . $sessionobject->get_module_title($this->_dependent) . '</b></i> cannot run until that is complete.'));
			$displayobject->update_html($displayobject->do_form_footer('Continue',''));
			$sessionobject->set_session_var(substr(get_class($this) , -3),'FALSE');
			$sessionobject->set_session_var('module','000');
		}
	}


	function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
	{
		// Set up working variables.
		$displayobject->update_basic('displaymodules','FALSE');
		$target_database_type	= $sessionobject->get_session_var('targetdatabasetype');
		$target_table_prefix	= $sessionobject->get_session_var('targettableprefix');
		$source_database_type	= $sessionobject->get_session_var('sourcedatabasetype');
		$source_table_prefix	= $sessionobject->get_session_var('sourcetableprefix');


		// Per page vars
		$user_start_at			= $sessionobject->get_session_var('userstartat');
		$user_per_page			= $sessionobject->get_session_var('userperpage');
		$class_num				= substr(get_class($this) , -3);


		// Start the timing
		if(!$sessionobject->get_session_var($class_num . '_start'))
		{
			$sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
		}


		// Get an array of user details
		$user_array 	= $this->get_deluxeportal_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);


		// Groups info
		$user_group_ids_array = $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);


		// Display count and pass time
		$displayobject->display_now('<h4>Importing ' . count($user_array) . ' users</h4><p><b>From</b> : ' . $user_start_at . ' ::  <b>To</b> : ' . ($user_start_at + count($user_array)) . '</p>');


		$user_object = new ImpExData($Db_target, $sessionobject, 'user');


		foreach ($user_array as $user_id => $user_details)
		{
			$try = (phpversion() < '5' ? $user_object : clone($user_object));

			// User options
			$options = 0;

			if($usergroup_details['displaysignatures'])					{ $options += 1;}
			if($usergroup_details['show_avatars'])						{ $options += 2;}
			if($usergroup_details['img'])								{ $options += 4;}
			#if($usergroup_details['coppa_user'])						{ $options += 8;}
			if($usergroup_details['subscribe_email'])					{ $options += 16;}
			#if($usergroup_details['showvcard'])						{ $options += 32;}
			if($usergroup_details['time_zone_dst'])						{ $options += 64;}
			if($usergroup_details['auto_time_zone'])					{ $options += 128;}
			if($usergroup_details['hide_email'])	{} else				{ $options += 256;}
			if($usergroup_details['invisible'])							{ $options += 512;}
			#if($usergroup_details['showreputation'])					{ $options += 1024;}
			if($usergroup_details['pm'])								{ $options += 2048;}
			if($usergroup_details['notify_pm'])							{ $options += 4096;}
			#if($usergroup_details['hasaccessmask'])					{ $options += 8192;}
			#if($usergroup_details['postorder'])						{ $options += 32768;}


			//************


			// Mandatory
			$try->set_value('mandatory', 'usergroupid',				$user_group_ids_array["$user_details[groupid]"]);
			$try->set_value('mandatory', 'username',				$user_details['name']);
			$try->set_value('mandatory', 'email',					$user_details['email']);
			$try->set_value('mandatory', 'importuserid',			$user_id);


			// Non Mandatory
			#$try->set_value('nonmandatory', 'membergroupids',		$user_details['membergroupids']);
			#$try->set_value('nonmandatory', 'displaygroupid',		$user_details['displaygroupid']);
			// its an md5(md5($moo)+$salt) Though the salt is bigger.
			$try->set_value('nonmandatory', 'password',				'moo');
			#$try->set_value('nonmandatory', 'passworddate',		$user_details['passworddate']);
			#$try->set_value('nonmandatory', 'styleid',				$user_details['styleid']);
			#$try->set_value('nonmandatory', 'parentemail',			$user_details['parentemail']);
			$try->set_value('nonmandatory', 'homepage',				addslashes($user_details['website']));
			$try->set_value('nonmandatory', 'icq',					addslashes($user_details['icq']));
			$try->set_value('nonmandatory', 'aim',					addslashes($user_details['aol']));
			$try->set_value('nonmandatory', 'yahoo',				addslashes($user_details['yahoo']));
			$try->set_value('nonmandatory', 'showvbcode',			'2');
			$try->set_value('nonmandatory', 'usertitle',			addslashes($user_details['title']));
			#$try->set_value('nonmandatory', 'customtitle',			$user_details['customtitle']);
			$try->set_value('nonmandatory', 'joindate',				$user_details['joindate']);
			#$try->set_value('nonmandatory', 'daysprune',			$user_details['daysprune']);
			$try->set_value('nonmandatory', 'lastvisit',			$user_details['lastvisit']);
			$try->set_value('nonmandatory', 'lastactivity',			$user_details['lastactivity']);
			$try->set_value('nonmandatory', 'lastpost',				$user_details['lastpost']);
			$try->set_value('nonmandatory', 'posts',				$user_details['posts']);
			#$try->set_value('nonmandatory', 'reputation',			$user_details['reputation']);
			#$try->set_value('nonmandatory', 'reputationlevelid',	$user_details['reputationlevelid']);
			$try->set_value('nonmandatory', 'timezoneoffset',		$user_details['time_zone_dst']);
			$try->set_value('nonmandatory', 'pmpopup',				$user_details['pm_popup']);
			#$try->set_value('nonmandatory', 'avatarid',			$user_details['avatarid']);
			#$try->set_value('nonmandatory', 'avatarrevision',		$user_details['avatarrevision']);
			$try->set_value('nonmandatory', 'options',				$options);

			#$try->set_value('nonmandatory', 'birthday',			$user_details['birthday']);
			#$try->set_value('nonmandatory', 'maxposts',			$user_details['maxposts']);
			$try->set_value('nonmandatory', 'startofweek',			'1');
			#$try->set_value('nonmandatory', 'ipaddress',			$user_details['ipaddress']);
			#$try->set_value('nonmandatory', 'referrerid',			$user_details['referrerid']);
			#$try->set_value('nonmandatory', 'languageid',			$user_details['languageid']);
			$try->set_value('nonmandatory', 'msn',					addslashes($user_details['msn']));
			#$try->set_value('nonmandatory', 'emailstamp',			$user_details['emailstamp']);
			#$try->set_value('nonmandatory', 'threadedmode',		$user_details['threadedmode']);
			#$try->set_value('nonmandatory', 'pmtotal',				$user_details['pmtotal']);
			#$try->set_value('nonmandatory', 'pmunread',			$user_details['pmunread']);
			#$try->set_value('nonmandatory', 'salt',				$user_details['salt']);
			#$try->set_value('nonmandatory', 'autosubscribe',		$user_details['autosubscribe']);
			#$try->set_value('nonmandatory', 'avatar',				$user_details['avatar']);
			#$try->set_value('nonmandatory', 'birthday_search',		$user_details['birthday_search']);

			$try->add_default_value('Location', 					$user_details['location']);
			$try->add_default_value('signature', 					addslashes($user_details['signature']));


			// Check if user object is valid
			if($try->is_valid())
			{
				if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
				{
					$displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> :: user -> ' . $user_details['name']);
					$sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
				}
				else
				{
					$sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
					$sessionobject->add_error('warning', $this->_modulestring, get_class($this) . '::import_custom_profile_pic failed.', 'Check database permissions and database table');
					$displayobject->display_now("<br />Found avatar user and <b>DID NOT</b> imported to the  {$target_database_type} database");
				}
			}
			else
			{
				$displayobject->display_now("<br />Invalid user object, skipping." . $try->_failedon);
			}
			unset($try);
		}// End foreach


		// Check for page end
		if (count($user_array) == 0 OR count($user_array) < $user_per_page)
		{
			$sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
			$sessionobject->remove_session_var($class_num . '_start');
			$this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);

			$displayobject->update_html($displayobject->module_finished($this->_modulestring,
										$sessionobject->return_stats($class_num, '_time_taken'),
										$sessionobject->return_stats($class_num, '_objects_done'),
										$sessionobject->return_stats($class_num, '_objects_failed')
										));


			$sessionobject->set_session_var($class_num ,'FINISHED');
			$sessionobject->set_session_var('import_user','done');
			$sessionobject->set_session_var('module','000');
			$sessionobject->set_session_var('autosubmit','0');
			$displayobject->update_html($displayobject->print_redirect('index.php','1'));
		}


		$sessionobject->set_session_var('userstartat',$user_start_at+$user_per_page);
		$displayobject->update_html($displayobject->print_redirect('index.php'));
	}// End resume
}//End Class
# Autogenerated on : September 11, 2004, 2:50 pm
# By ImpEx-generator 1.0.
/*======================================================================*/
?>