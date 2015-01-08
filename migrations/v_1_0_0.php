<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 marttiphpbb <info@martti.be>
* @license http://opensource.org/licenses/MIT
*/

namespace marttiphpbb\usertopiccount\migrations;

use phpbb\db\migration\migration;

class v_1_0_0 extends migration
{

	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'update_user_topic_count'))),
		);
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'users'	=> array(
					'user_topic_count'		=> array('UINT', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'	=> array(
				$this->table_prefix . 'users'	=> array(
					'user_topic_count',
				),
			),
		);
	}

	public function update_user_topic_count()
	{
		$sql = 'SELECT COUNT (t.topic_id) as count, t.topic_poster 
				FROM ' . $this->table_prefix . 'topics t
				WHERE t.topic_visibility = ' . ITEM_APPROVED . '
				GROUP BY t.topic_poster';
		$result = $this->db->sql_query($sql);
		$users = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		foreach ($users as $user)
		{
			$sql = 'UPDATE ' . $this->table_prefix . 'users
				SET user_topic_count = ' . $user['count'] . '
				WHERE user_id = ' . $user['topic_poster'];
			$this->db->sql_query($sql);
		}
	}
}
