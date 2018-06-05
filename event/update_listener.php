<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2018 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount\event;

use phpbb\event\data as event;
use marttiphpbb\usertopiccount\service\update;
use phpbb\db\driver\factory as db;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class update_listener implements EventSubscriberInterface
{
	/** @var update */
	protected $update;

	/* @var db */
	protected $db;

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
			$posts_table,
			$topics_table,
			$users_table
	)
	{
		$this->update = $update;
		$this->db = $db;
		$this->posts_table = $posts_table;
		$this->topics_table = $topics_table;
		$this->users_table = $users_table;
	}

	static public function getSubscribedEvents()
	{
		return [
			'core.move_posts_sync_after'	=> 'core_move_posts_sync_after',
			'core.submit_post_end'			=> 'core_submit_post_end',
			'core.delete_post_after'		=> 'core_delete_post_after',
			'core.delete_posts_after'		=> 'core_delete_posts_after',
			'core.prune_delete_before'		=> 'core_prune_delete_before',
			'core.approve_posts_after'		=> 'core_approve_posts_after',
			'core.approve_topics_after'		=> 'core_approve_topics_after',
			'core.disapprove_posts_after'	=> 'core_disapprove_posts_after',
			'core.set_post_visibility_after'
				=> 'core_set_post_visibility_after',		
		];
	}

	// functions_admin.php // test
	public function core_move_posts_sync_after(event $event)
	{
		$topic_id = $event['topic_id'];
		$topic_ids = $event['topic_ids'];
		$topic_ids[] = $topic_id;
		$poster_ary = [];

		$sql = 'select t.topic_poster 
			from ' . $this->topics_table . ' t 
			where ' . $this->db->sql_in_set('t.topic_id', $topic_ids);

		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$poster_ary[$row['topic_poster']] = true;
		}
		
		$this->db->sql_freeresult($result);

		$this->update->for_user_ary(array_keys($poster_ary));

		error_log('core.move_posts_sync_after');
	}

	// functions_posting.php // ok
	public function core_submit_post_end(event $event)
	{
		$mode = $event['mode'];

		if ($mode != 'post')
		{
			return;
		}

		$data = $event['data'];
	
		$this->update->for_user($data['poster_id']);
		error_log('core.submit_post_end');
	}

	// functions_posting.php // test
	public function core_delete_post_after(event $event)
	{
		$post_mode = $event['post_mode'];

		if (!in_array($post_mode, ['delete_topic', 'delete_first_post']))
		{
			return;
		}

		$data = $event['data'];
		$next_post_id = $event['next_post_id'];

		error_log('post_mode');
		error_log($post_mode);
		error_log('data');
		error_log(json_encode($data));
		error_log('next_post_id');
		error_log($next_post_id);

		$user_ids = [
			$data['poster_id'] => true,
		];

		if ($next_post_id)
		{
			$sql = 'select p.poster_id
				from ' . $this->posts_table . '
				where p.post_id = ' . $next_post_id;
				
			$result = $this->db->sql_query($sql);
			$next_poster_id = $this->db->sql_fetchfield('poster_id');
			$this->db->sql_freeresult($result);
			$user_ids[$next_poster_id] = true; 
		}

		$this->update->for_user_ary(array_keys($user_ids));

		error_log('user_ids: ' . json_encode(array_keys($user_ids)));
		error_log('core.delete_post_after');
	}

	// functions_admin.php // test
	public function core_delete_posts_after(event $event)
	{
		$post_ids = $event['post_ids'];

		// This is before the topics table is synced
		// We check if first posts in approved topics where removed
		$sql = 'select t.topic_poster, t.topic_id
			from ' . $this->topics_table . ' t 
			where ' . $this->db->sql_in_set('t.topic_first_post_id', $post_ids) . '
				and t.topic_visibility = ' . ITEM_APPROVED;

		$result = $this->db->sql_query($sql);
	
		while ($row = $this->db->sql_fetchrow($result))
		{
			$topic_change_ary[$row['topic_id']] = true;
			$poster_change_ary[$row['topic_poster']] = true;
		}

		$this->db->sql_freeresult($result);

		$topic_change_ary = array_keys($t, p.topic_idopic_change_ary);

		$sql = 'select p.poster_id
			from ' . $this->posts_table	. ' p
			where ' . $this->db->sql_in_set('p.topic_id', $topic_ids) . '
				and p.post_visibility = ' . ITEM_APPROVED . '
			group by p.topic_id
			having min(p.post_id) = p.post_id';
		
		$result = $this->db->sql_query($sql);

		while ($poster_id = $this->db->sql_fetchfield('poster_id'))
		{
			$poster_change_ary[$poster_id] = true;
		}

		$this->db->sql_freeresult($result);

		$poster_change_ary = array_keys($poster_change_ary);

		$this->update->for_unsynced_user_ary($poster_change_ary);

		error_log('core.delete_posts_after');
	}

	// functions_admin.php // handle in "delete_posts()"
	public function core_prune_delete_before(event $event)
	{
		error_log('core.prune_delete_before');
	}

	// mcp/mcp_queue.php  // test
	public function core_approve_posts_after(event $event)
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

		$sql = 'select poster_id
			from ' . $this->posts_table . '
			where ' . $this->db->sql_in_ary('topic_id', array_keys($topics)) . '
				and post_visibility = ' . ITEM_APPROVED . '
				and ' . $this->db->sql_not_in_ary('post_id', array_keys($posts)) . '
			group by topic_id
			having min(post_id) = post_id';

		$result = $this->db->sql_query($sql);

		while($poster_id = $this->db->sql_fetchfield('poster_id'))
		{
			$posters[$poster_id] = true;
		}

		$this->db->sql_freeresult($result);

		$this->update->for_user_ary(array_keys($posters));	
		error_log('core.approve_posts_after');
	}

	// mcp/mcp_queue.php //test
	public function core_approve_topics_after(event $event)
	{
		$topic_info = $event['topic_info'];

		$posters = [];

		foreach ($topic_info as $topic_id => $topic_data)
		{
			$posters[$topic_data['topic_poster']] = true;
		}

		$this->update->for_user_ary(array_keys($posters));

		error_log('topic_info: ');
		error_log(json_encode($topic_info));
		error_log('core.approve_topics_after');
	}

	// mcp/mcp_queue.php // nothing?
	public function core_disapprove_posts_after(event $event)
	{
		error_log('disapprove posts after');
	}

	// phpbb/content_visibility.php // ?check todo
	public function core_set_post_visibility_after(event $event)
	{
		error_log('core.set_post_visibility_after');
	}
}
