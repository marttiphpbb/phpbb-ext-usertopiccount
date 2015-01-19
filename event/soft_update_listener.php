<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount\event;

use phpbb\auth\auth;
use phpbb\config\db as config;
use phpbb\content_visibility;
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
class soft_update_listener implements EventSubscriberInterface
{

	/* @var auth */
	protected $auth;

	/* @var config */
	protected $config;

	/* @var content_visibility */
	protected $content_visibility;

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
	protected $posts_table;

	/* @var string */
	protected $topics_table;

	/* @var string */
	protected $users_table;

	/**
	* @param auth				$auth
	* @param config				$config
	* @param content_visibility	$content_visibility
	* @param db					$db
	* @param helper				$helper
	* @param template			$template
	* @param user				$user
	* @param string				$php_ext
	* @param string				$phpbb_root_path
	* @param string				$posts_table
	* @param string				$topics_table
	* @param string				$users_table
	*/
	public function __construct(
			auth $auth,
			config $config,
			content_visibility $content_visibility,
			db $db,
			helper $helper,
			template $template,
			user $user,
			$php_ext,
			$phpbb_root_path,
			$posts_table,
			$topics_table,
			$users_table
		)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->content_visibility = $content_visibility;
		$this->db = $db;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->php_ext = $php_ext;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->posts_table = $posts_table;
		$this->topics_table = $topics_table;
		$this->users_table = $users_table;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.page_footer'			=> 'core_page_footer',
		);
	}

	public function core_page_footer($event)
	{

	}
}
