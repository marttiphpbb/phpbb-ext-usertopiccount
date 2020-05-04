<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2020 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount\event;

use phpbb\event\data as event;
use phpbb\auth\auth;
use phpbb\config\db as config;
use phpbb\template\twig\twig as template;
use phpbb\user;
use phpbb\language\language;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class show_listener implements EventSubscriberInterface
{
	protected $auth;
	protected $config;
	protected $template;
	protected $user;
	protected $language;
	protected $php_ext;
	protected $phpbb_root_path;

	public function __construct(
			auth $auth,
			config $config,
			template $template,
			user $user,
			language $language,
			string $php_ext,
			string $phpbb_root_path
		)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->language = $language;
		$this->php_ext = $php_ext;
		$this->phpbb_root_path = $phpbb_root_path;
	}

	static public function getSubscribedEvents():array
	{
		return [
			'core.memberlist_view_profile'		=> 'core_memberlist_view_profile',
			'core.viewtopic_cache_user_data'	=> 'core_viewtopic_cache_user_data',
			'core.viewtopic_modify_post_row'	=> 'core_viewtopic_modify_post_row',
			'core.ucp_display_module_before'	=> 'core_ucp_display_module_before',
			'core.ucp_pm_view_message'			=> 'core_ucp_pm_view_message',
		];
	}

	public function core_memberlist_view_profile(event $event):void
	{
		$member = $event['member'];
		$user_id = $member['user_id'];

		$this->template->assign_vars([
			'MARTTIPHPBB_USERTOPICCOUNT'			=> $member['user_topic_count'],
			'U_MARTTIPHPBB_USERTOPICCOUNT_SEARCH'	=> $member['user_topic_count'] ? $this->get_u_search($user_id) : '',
		]);

		$this->language->add_lang('profile', 'marttiphpbb/usertopiccount');
	}

	public function core_viewtopic_cache_user_data(event $event):void
	{
		$row = $event['row'];
		$user_cache_data = $event['user_cache_data'];
		$poster_id = $event['poster_id'];

		$user_cache_data['usertopiccount'] = $row['user_topic_count'];
		$user_cache_data['usertopiccount_search'] = $row['user_topic_count'] ? $this->get_u_search($poster_id) : '';

		$event['user_cache_data'] = $user_cache_data;
	}

	public function core_viewtopic_modify_post_row(event $event):void
	{
		$user_poster_data = $event['user_poster_data'];
		$post_row = $event['post_row'];

		$post_row['MARTTIPHPBB_USERTOPICCOUNT'] = $user_poster_data['usertopiccount'];
		$post_row['U_MARTTIPHPBB_USERTOPICCOUNT_SEARCH'] = $user_poster_data['usertopiccount_search'];

		$event['post_row'] = $post_row;
	}

	public function core_ucp_display_module_before(event $event):void
	{
		$id = $event['id'];
		$mode = $event['mode'];
		$module = $event['module'];

		if (($id == 'ucp_main' && ($mode == '' || $mode == 'front'))
			|| $id == ''
			|| (is_numeric($id) && $module->module_ary[1]['parent'] == $id))
		{
			$user_id = $this->user->data['user_id'];
			$topic_count = $this->user->data['user_topic_count'];

			$this->template->assign_vars([
				'MARTTIPHPBB_USERTOPICCOUNT'			=> $topic_count,
				'U_MARTTIPHPBB_USERTOPICCOUNT_SEARCH'	=> $topic_count ? $this->get_u_search($user_id) : '',
			]);

			$this->language->add_lang('profile', 'marttiphpbb/usertopiccount');
		}
	}

	public function core_ucp_pm_view_message(event $event):void
	{
		$msg_data = $event['msg_data'];
		$message_row = $event['message_row'];
		$author_id = $message_row['author_id'];
		$user_info = $event['user_info'];

		$msg_data['MARTTIPHPBB_USERTOPICCOUNT'] = $user_info['user_topic_count'];
		$msg_data['U_MARTTIPHPBB_USERTOPICCOUNT_SEARCH'] = $user_info['user_topic_count'] ? $this->get_u_search($author_id) : '';

		$event['msg_data'] = $msg_data;
	}

	private function get_u_search(int $user_id):string
	{
		if ($this->config['load_search'] && $this->auth->acl_get('u_search'))
		{
			return append_sid($this->phpbb_root_path . 'search.' . $this->php_ext, 'author_id=' . $user_id . '&amp;sr=topics&amp;sf=firstpost');
		}

		return '';
	}
}
