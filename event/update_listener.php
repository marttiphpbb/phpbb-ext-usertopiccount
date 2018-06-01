<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2018 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount\event;

use phpbb\event\data as event;
use marttiphpbb\usertopiccount\service\update;
use phpbb\config\db as config;
use phpbb\db\driver\factory as db;
use phpbb\user;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class update_listener implements EventSubscriberInterface
{
	/** @var update */
	protected $update;

	/* @var db */
	protected $db;

	/* @var user */
	protected $user;

	/* @var string */
	protected $posts_table;

	/* @var string */
	protected $topics_table;

	/* @var string */
	protected $users_table;

	/**
	 * @param update			$update
	* @param db					$db
	* @param user				$user
	* @param string				$posts_table
	* @param string				$topics_table
	* @param string				$users_table
	*/
	public function __construct(
			update $update,
			db $db,
			user $user,
			$posts_table,
			$topics_table,
			$users_table
	)
	{
		$this->update = $update;
		$this->db = $db;
		$this->user = $user;
		$this->posts_table = $posts_table;
		$this->topics_table = $topics_table;
		$this->users_table = $users_table;
	}

	static public function getSubscribedEvents()
	{
		return [
			'core.submit_post_end'					=> 'core_submit_post_end',
			'core.delete_posts_after'				=> 'core_delete_posts_after',
		];
	}

	public function core_submit_post_end(event $event)
	{
		$mode = $event['mode'];
		$data = $event['data'];
		$post_visibility = $event['post_visibility'];

		if ($mode != 'post' || $post_visibility != ITEM_APPROVED)
		{
			return;
		}

		$sql = 'UPDATE ' . $this->users_table . '
			SET user_topic_count = user_topic_count + 1
			WHERE user_id = ' . $data['poster_id'];
		$this->db->sql_query($sql);
	}

	public function core_delete_posts_after(event $event)
	{
		$topic_ids = $event['topic_ids'];
		$post_ids = $event['post_ids'];
		$poster_ids = $event['poster_ids'];

		$add_ary = $adds = $topic_change_ary = [];

		// Find where the first visible post was deleted, to decrease user_topic_count

		$sql_ary = [
			'SELECT'	=> 't.topic_poster, t.topic_id',
			'FROM'		=> [
				$this->topics_table	=> 't',
			],
			'WHERE'	=> $this->db->sql_in_set('t.topic_first_post_id', $post_ids) . '
				AND ' . $this->content_visibility->get_global_visibility_sql('topic', [], 't.'),
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$add_ary[$row['topic_poster']] = (isset($add_ary[$row['topic_poster']])) ? $add_ary[$row['topic_poster']] - 1 : -1;
			$topic_change_ary[] = $row['topic_poster'];
		}
		$this->db->sql_freeresult($result);

		// no first visible post was deleted.
		if (!sizeof($topic_change_ary))
		{
			return;
		}

		// Where the first visible post was deleted, the user_topic_count goes to the next visible post author

		$sql_ary = ['SELECT'	=> 'p.poster_id, MIN(p.post_id), p.post_visibility',
			'FROM'	=> [
				$this->posts_table	=> 'p',
			],
			'WHERE'	=> $this->db->sql_in_set('p.topic_id', $topic_change_ary),
			'GROUP BY'	=> 'p.topic_id',
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['post_visibility'] == ITEM_APPROVED)
			{
				$add_ary[$row['poster_id']] = (isset($add_ary[$row['poster_id']])) ? $add_ary[$row['poster_id']] + 1 : 1;
			}
		}
		$this->db->sql_freeresult($result);

		// update user_topic_count

		foreach ($add_ary as $user_id => $add)
		{
			if (!$add)
			{
				continue;
			}

			$sql = 'UPDATE ' . $this->users_table . '
				SET user_topic_count = 0
				WHERE user_id = ' . $user_id . '
				AND user_topic_count + ' . $add . ' < 0';
			$this->db->sql_query($sql);

			$sql = 'UPDATE ' . $this->users_table . '
				SET user_topic_count = user_topic_count + ' . $add . '
				WHERE user_id = ' . $user_id . '
				AND user_topic_count + ' . $add . ' >= 0';
			$this->db->sql_query($sql);
		}
	}
}
