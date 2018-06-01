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
	/** @var db */
	protected $db;

	/** @var string */
	protected $topics_table;

	/** @var string */
	protected $users_table;

	/**
	* @param db					$db
	* @param string				$topics_table
	* @param string				$users_table
	*/
	public function __construct(
			db $db,
			string $topics_table,
			string $users_table
		)
	{
		$this->db = $db;
		$this->topics_table = $topics_table;
		$this->users_table = $users_table;
	}

	private function update(int $user_id, int $topic_count)
	{
		$sql = 'update ' . $this->users_table . '
			set user_topic_count = ' . $topic_count . '
			where user_id = ' . $user_id;
		$db->sql_query($sql);
	}

	private function for_sql_where(string $sql_where)
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

		foreach($rows as $data)
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
}
