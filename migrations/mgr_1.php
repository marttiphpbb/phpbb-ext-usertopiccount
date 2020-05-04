<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2020 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount\migrations;

use phpbb\db\migration\migration;

class mgr_1 extends migration
{
	static public function depends_on():array
	{
		return [
			'\phpbb\db\migration\data\v32x\v322',
		];
	}

	public function update_schema():array
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'users'	=> [
					'user_topic_count'		=> ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema():array
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'users'	=> [
					'user_topic_count',
				],
			],
		];
	}
}
