<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2020 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount\migrations;

use phpbb\db\migration\migration;
use marttiphpbb\usertopiccount\service\update;

class mgr_2 extends migration
{
	static public function depends_on():array
	{
		return [
			'\marttiphpbb\usertopiccount\migrations\mgr_1',
		];
	}

	public function update_data():array
	{
		return [[
			'custom', [
				[$this, 'update_user_topic_count']
			],
		]];
	}

	public function update_user_topic_count($previous_end):array
	{
		$users_table = $this->table_prefix . 'users';
		$posts_table = $this->table_prefix . 'posts';
		$topics_table = $this->table_prefix . 'topics';

		$start = $previous_end === false ? 1 : $previous_end;

		$end = $start + 1000;
		$update = new update($this->db, $posts_table, $topics_table, $users_table);

		$update->for_user_range($start, $end);

		if (!$update->has_next_user_id($end))
		{
			error_log('user_topic_count_updated in migration');
			return true;
		}

		error_log('user_topic_count_set_up_to_user_' . $end . ' in migration');
		return $end;
	}
}
