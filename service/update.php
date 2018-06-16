<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2018 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount\service;

use phpbb\db\driver\factory as db;

class update
{
	protected $db;
	protected $posts_table;
	protected $topics_table;
	protected $users_table;

	public function __construct(
		db $db,
		string $posts_table,
		string $topics_table,
		string $users_table
	)
	{
		$this->db = $db;
		$this->posts_table = $posts_table;
		$this->topics_table = $topics_table;
		$this->users_table = $users_table;
	}

	private function update(int $user_id, int $topic_count)
	{
		$sql = 'update ' . $this->users_table . '
			set user_topic_count = ' . $topic_count . '
			where user_id = ' . $user_id;
		$this->db->sql_query($sql);
	}

	public function for_unsynced_user_ary(array $user_ids)
	{
		if (!count($user_ids))
		{
			return;
		}

		$count_ary = [];

		$sql = 'select p.poster_id
			from ' . $this->posts_table	. ' p
			where ' . $this->db->sql_in_set('p.poster_id', $user_ids) . '
				and p.post_visibility = ' . ITEM_APPROVED . '
			group by p.topic_id
			having min(p.post_id) = p.post_id';

		$result = $this->db->sql_query($sql);

		while($poster_id = $this->db->sql_fetchfield('poster_id'))
		{
			if (!isset($count_ary[$poster_id]))
			{
				$count_ary[$poster_id] = 0;
			}

			$count_ary[$poster_id]++;
		}

		$this->db->sql_freeresult($result);

		foreach ($user_ids as $user_id)
		{
			$this->update($user_id, $count_ary[$user_id] ?? 0);
		}
	}

	public function for_sql_where(string $sql_where)
	{
		$sql = 'select count(t.topic_id) as topic_count, u.user_id
			from ' . $this->topics_table . ' t, ' . $this->users_table . ' u
			where t.topic_visibility = ' . ITEM_APPROVED . '
				and t.topic_poster = u.user_id
				and ' . $sql_where . '
				and u.user_id <> ' . ANONYMOUS . '
			group by t.topic_poster
			having count(t.topic_id) <> u.user_topic_count';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		foreach($rows as $row)
		{
			$this->update($row['user_id'], $row['topic_count']);
		}
	}

	public function for_user(int $user_id)
	{
		$this->for_sql_where('u.user_id = ' . $user_id);
	}

	public function for_user_ary(array $user_ids)
	{
		$this->for_sql_where($this->db->sql_in_set('u.user_id', $user_ids));
	}

	public function for_user_range(int $start_user_id, int $end_user_id)
	{
		$this->for_sql_where('u.user_id >= ' . $start_user_id . '
			and u.user_id <= ' . $end_user_id);
	}

	// approve, dissapprove, delete
	public function for_topic(int $topic_id)
	{
		$sql = 'select topic_poster
			from ' . $this->topics_table . '
			where topic_id = ' . $topic_id;

		$result = $this->db->sql_query($sql);
		$topic_poster = $this->db->sql_fetchfield('topic_poster');
		$this->db->sql_freeresult($result);

		if (isset($poster_id))
		{
			$this->for_user($poster_id);
		}
	}
}
