<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2020 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount\event;

use phpbb\event\data as event;
use marttiphpbb\usertopiccount\service\update;
use phpbb\db\driver\factory as db;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class update_listener implements EventSubscriberInterface
{
	protected $update;
	protected $db;
	protected $posts_table;
	protected $topics_table;
	protected $users_table;
	protected $users_to_recalc = [];

	public function __construct(
			update $update,
			db $db,
			string $posts_table,
			string $topics_table,
			string $users_table
	)
	{
		$this->update = $update;
		$this->db = $db;
		$this->posts_table = $posts_table;
		$this->topics_table = $topics_table;
		$this->users_table = $users_table;
	}

	static public function getSubscribedEvents():array
	{
		return [
			'core.move_posts_sync_after'
				=> 'core_move_posts_sync_after',
			'core.submit_post_end'
				=> 'core_submit_post_end',
			'core.delete_post_after'
				=> 'core_delete_post_after',
			'core.delete_posts_after'
				=> 'core_delete_posts_after',
			'core.delete_topics_before_query'
				=> 'core_delete_topics_before_query',
			'core.delete_topics_after_query'
				=> 'core_delete_topics_after_query',
			'core.prune_delete_before'
				=> 'core_prune_delete_before',
			'core.approve_posts_after'
				=> 'core_approve_posts_after',
			'core.approve_topics_after'
				=> 'core_approve_topics_after',
			'core.disapprove_posts_after'
				=> 'core_disapprove_posts_after',
			'core.set_post_visibility_after'
				=> 'core_set_post_visibility_after',
			'core.set_topic_visibility_after'
				=> 'core_set_topic_visibility_after',
		];
	}

	// functions_admin.php // test
	public function core_move_posts_sync_after(event $event):void
	{
		$topic_id = $event['topic_id'];
		$topic_ids = $event['topic_ids'];
		$topic_ids[] = $topic_id;

		$topic_poster_ary = $this->get_topic_poster_ary($topic_ids);
		$this->update->for_user_ary($topic_poster_ary);
	}

	// functions_posting.php // ok
	public function core_submit_post_end(event $event):void
	{
		$mode = $event['mode'];

		if ($mode != 'post')
		{
			return;
		}

		$data = $event['data'];

		$this->update->for_user($data['poster_id']);
	}

	// functions_posting.php // test
	public function core_delete_post_after(event $event):void
	{
		$post_mode = $event['post_mode'];

		if (!in_array($post_mode, ['delete_topic', 'delete_first_post']))
		{
			return;
		}

		$data = $event['data'];
		$next_post_id = $event['next_post_id'];

		$user_ids = [
			$data['poster_id'] => true,
		];

		if ($next_post_id)
		{
			$sql = 'select poster_id
				from ' . $this->posts_table . '
				where post_id = ' . $next_post_id;

			$result = $this->db->sql_query($sql);
			$next_poster_id = $this->db->sql_fetchfield('poster_id');
			$this->db->sql_freeresult($result);
			$user_ids[$next_poster_id] = true;
		}

		$this->update->for_user_ary(array_keys($user_ids));
	}

	// functions_admin.php // test
	public function core_delete_posts_after(event $event):void
	{
		error_log('core.delete_posts_after');
	}

	// functions_admin.php
	public function core_delete_topics_before_query(event $event):void
	{
		$topic_ids = $event['topic_ids'];

		// catch users for recalculation in after query.
		$this->users_to_recalc = $this->get_topic_poster_ary($topic_ids);
	}

	// functions_admin.php
	public function core_delete_topics_after_query(event $event):void
	{
		error_log('core.delete_topics_after_query');
		$this->update->for_user_ary($this->users_to_recalc);
	}

	// functions_admin.php // handle in "delete_posts()"
	public function core_prune_delete_before(event $event):void
	{
		error_log('core.prune_delete_before');
	}

	// mcp/mcp_queue.php  // test
	public function core_approve_posts_after(event $event):void
	{
		$post_info = $event['post_info'];
		$topic_info = $event['topic_info'];

		$topics = $posters = $posts = [];

		foreach ($topic_info as $topic_id => $topic_data)
		{
			if ($topic_data['first_post'])
			{
				foreach ($topic_data['posts'] as $post_id)
				{
					$posts[$post_id] = true;
				}

				$topics[$topic_id] = true;
			}
		}

		foreach ($post_info as $post_id => $post_data)
		{
			$posters[$post_info[$post_id]['poster_id']] = true;
		}

		$sql = 'select ps.poster_id from (
				select min(p.post_id), p.poster_id, p.topic_id, p.post_id
				from ' . $this->posts_table . ' p
				where ' . $this->db->sql_in_set('p.topic_id', array_keys($topics)) . '
					and p.post_visibility = ' . ITEM_APPROVED . '
				group by p.topic_id) ps
			where ' . $this->db->sql_in_set('post_id', array_keys($posts), true);

		$result = $this->db->sql_query($sql);

		while($poster_id = $this->db->sql_fetchfield('poster_id'))
		{
			$posters[$poster_id] = true;
		}

		$this->db->sql_freeresult($result);

		$this->update->for_user_ary(array_keys($posters));
	}

	// mcp/mcp_queue.php //test
	public function core_approve_topics_after(event $event):void
	{
		$topic_info = $event['topic_info'];

		$posters = [];

		foreach ($topic_info as $topic_id => $topic_data)
		{
			$posters[$topic_data['topic_poster']] = true;
		}

		$this->update->for_user_ary(array_keys($posters));
	}

	// mcp/mcp_queue.php // nothing?
	public function core_disapprove_posts_after(event $event):void
	{
		error_log('disapprove posts after');
	}

	// phpbb/content_visibility.php //test
	public function core_set_post_visibility_after(event $event):void
	{
		$topic_id = $event['topic_id'];
		$this->update->for_topic($topic_id);
	}

	// phpbb/content_visibility.php //test
	public function core_set_topic_visibility_after(event $event):void
	{
		$topic_id = $event['topic_id'];
		$this->update->for_topic($topic_id);
	}

	private function get_topic_poster_ary(array $topic_ids):array
	{
		$poster_ary = [];

		$sql = 'select topic_poster
			from ' . $this->topics_table . '
			where ' . $this->db->sql_in_set('topic_id', $topic_ids);

		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$poster_ary[$row['topic_poster']] = true;
		}

		return array_keys($poster_ary);
	}
}
