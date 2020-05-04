<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2020 marttiphpbb <info@martti.be>
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

	public function for_sql_where(string $sql_where):void
	{
		$sql_where = $sql_where === '' ? '' : ' and ' . $sql_where;

		$sql = 'update ' . $this->users_table . '
			set user_topic_count =
				(select count(*) from ' . $this->topics_table . '
				where topic_visibility = ' . ITEM_APPROVED . '
					and topic_poster = ' . $this->users_table . '.user_id)
			where user_id <> ' . ANONYMOUS . $sql_where;

		$result = $this->db->sql_query($sql);
		$this->db->sql_freeresult($result);
	}

	public function for_user(int $user_id):void
	{
		$this->for_sql_where('user_id = ' . $user_id);
	}

	public function for_user_ary(array $user_ids):void
	{
		$this->for_sql_where($this->db->sql_in_set('user_id', $user_ids));
	}

	public function for_user_range(int $start_user_id, int $end_user_id):void
	{
		$this->for_sql_where('user_id >= ' . $start_user_id . '
			and user_id <= ' . $end_user_id);
	}

	public function has_next_user_id(int $end_user_id):bool
	{
		$sql = 'select min(user_id)
			from ' . $this->users_table . '
			where user_id > ' . $end_user_id;
		$result = $this->db->sql_query($sql);
		$user_id = $this->db->sql_fetchfield('user_id');
		$this->db->sql_freeresult($result);

		return $user_id ? true : false;
	}

	// approve, dissapprove, delete
	public function for_topic(int $topic_id):void
	{
		$sql = 'select topic_poster
			from ' . $this->topics_table . '
			where topic_id = ' . $topic_id;

		$result = $this->db->sql_query($sql);
		$poster_id = $this->db->sql_fetchfield('topic_poster');
		$this->db->sql_freeresult($result);

		if (isset($poster_id))
		{
			$this->for_user($poster_id);
		}
	}
}
