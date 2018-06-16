<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2018 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount\migrations;

use phpbb\db\migration\migration;

class v_1_0_0 extends migration
{
	static public function depends_on()
	{
		return [
			'\phpbb\db\migration\data\v32x\v322',
		];
	}

	public function update_schema()
	{
		return [ // uncluded here for consistency; the user_topic_count column was already created in ext.php
			'add_columns'	=> [
				$this->table_prefix . 'users'	=> [
					'user_topic_count'		=> ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema()
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
