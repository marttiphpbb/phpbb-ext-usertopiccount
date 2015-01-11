<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 marttiphpbb <info@martti.be>
* @license http://opensource.org/licenses/MIT
*/

namespace marttiphpbb\usertopiccount\event;

use phpbb\auth\auth;
use phpbb\config\db as config;
use phpbb\db\driver\factory as db;
use phpbb\controller\helper;
use phpbb\template\twig\twig as template;
use phpbb\user;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{

	/* @var auth */
	protected $auth;

	/* @var config */
	protected $config;

	/* @var db */
	protected $db;

	/* @var helper */
	protected $helper;

	/* @var template */
	protected $template;

	/* @var user */
	protected $user;

	/* @var string */
	protected $php_ext;

	/* @var string */
	protected $phpbb_root_path;

	/* @var string */
	protected $table_prefix;

	/**
	* @param auth				$auth
	* @param config				$config
	* @param db					$db
	* @param helper				$helper
	* @param template			$template
	* @param user				$user
	* @param string				$php_ext
	* @param string				$phpbb_root_path
	* @param string				$table_prefix
	*/
	public function __construct(
			auth $auth,
			config $config,
			db $db,
			helper $helper,
			template $template,
			user $user,
			$php_ext,
			$phpbb_root_path,
			$table_prefix
		)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->php_ext = $php_ext;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->table_prefix = $table_prefix;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.memberlist_view_profile'			=> 'core_memberlist_view_profile',
			'core.viewtopic_cache_user_data'		=> 'core_viewtopic_cache_user_data',
			'core.viewtopic_modify_post_row'		=> 'core_viewtopic_modify_post_row',
			'core.ucp_display_module_before'		=> 'core_ucp_display_module_before',
			'core.submit_post_end'					=> 'core_submit_post_end',
		);
	}

	public function core_memberlist_view_profile($event)
	{
		$member = $event['member'];

		$user_id = $member['user_id'];
		$search = ($this->config['load_search'] && $this->auth->acl_get('u_search')) ? append_sid($this->phpbb_root_path . 'search.' . $this->php_ext, 'author_id=' . $user_id . '&amp;sr=topics') : '';

		$this->template->assign_vars(array(
			'USERTOPICCOUNT'			=> $member['user_topic_count'],
			'U_USERTOPICCOUNT_SEARCH'	=> $search,
		));

		$this->user->add_lang_ext('marttiphpbb/usertopiccount', 'profile');
	}

	public function core_viewtopic_cache_user_data($event)
	{
		$row = $event['row'];
		$user_cache_data = $event['user_cache_data'];
		$poster_id = $event['poster_id'];

		$user_cache_data['usertopiccount'] = $row['user_topic_count'];
		$user_cache_data['usertopiccount_search'] = ($this->config['load_search'] && $this->auth->acl_get('u_search')) ? append_sid($this->phpbb_root_path . 'search.' . $this->php_ext, 'author_id=' . $poster_id . '&amp;sr=topics') : '';

		$event['user_cache_data'] = $user_cache_data;
	}

	public function core_viewtopic_modify_post_row($event)
	{
		$user_poster_data = $event['user_poster_data'];
		$post_row = $event['post_row'];

		$post_row['USERTOPICCOUNT'] = $user_poster_data['usertopiccount'];
		$post_row['U_USERTOPICCOUNT_SEARCH'] = $user_poster_data['usertopiccount_search'];

		$event['post_row'] = $post_row;
	}

	public function core_ucp_display_module_before($event)
	{
		$id = $event['id'];
		$mode = $event['mode'];
		$module = $event['module'];

		if (($id == 'ucp_main' && ($mode == '' || $mode == 'front'))
			|| $id == ''
			|| (is_numeric($id) && $module->module_ary[1]['parent'] == $id))
		{
			$user_id = $this->user->data['user_id'];
			$search = ($this->config['load_search'] && $this->auth->acl_get('u_search')) ? append_sid($this->phpbb_root_path . 'search.' . $this->php_ext, 'author_id=' . $user_id . '&amp;sr=topics') : '';

			$this->template->assign_vars(array(
				'USERTOPICCOUNT'			=> $this->user->data['user_topic_count'],
				'U_USERTOPICCOUNT_SEARCH'	=> $search,
			));

			$this->user->add_lang_ext('marttiphpbb/usertopiccount', 'profile');
		}
	}

	public function core_submit_post_end($event)
	{
		$mode = $event['mode'];
		$data = $event['data'];
		$post_visibility = $event['post_visibility'];

		var_dump($mode, $post_visibility, ITEM_APPROVED, $data);

		if ($mode != 'post' || $post_visibility != ITEM_APPROVED)
		{
			return;
		}

		$sql = 'UPDATE ' . $this->table_prefix . 'users
			SET user_topic_count = user_topic_count + 1
			WHERE user_id = ' . $data['poster_id'];
		$this->db->sql_query($sql);
	}
}
