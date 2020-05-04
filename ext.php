<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2020 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount;

use marttiphpbb\usertopiccount\service\update;

class ext extends \phpbb\extension\base
{
	public function is_enableable():bool
	{
		$config = $this->container->get('config');
		return phpbb_version_compare($config['version'], '3.3', '>=')
			&& version_compare(PHP_VERSION, '7.1', '>=');
	}

	/**
	* @param mixed $old_state
	* @return mixed false after last step, otherwise temporary state
	*/
	public function enable_step($old_state)
	{
		$db_tools = $this->container->get('dbal.tools');
		$users_table = $this->container->getParameter('tables.users');

		if (!$db_tools->sql_column_exists($users_table, 'user_topic_count'))
		{
			return parent::enable_step($old_state);
		}

		$db = $this->container->get('dbal.conn');
		$posts_table = $this->container->getParameter('tables.posts');
		$topics_table = $this->container->getParameter('tables.topics');

		if (!$old_state)
		{
			$start = 1;
		}
		else if (strpos($old_state, 'user_topic_count_set_up_to_user_' === 0))
		{
			$start = str_replace('user_topic_count_set_up_to_user_', '', $old_state);
		}
		else
		{
			return parent::enable_step($old_state);
		}

		$end = $start + 1000;
		$update = new update($db, $posts_table, $topics_table, $users_table);

		$update->for_user_range($start, $end);

		if (!$update->has_next_user_id($end))
		{
			error_log('user_topic_count_updated');
			return 'user_topic_count_updated';
		}

		$step = 'user_topic_count_set_up_to_user_' . $end;
		error_log($step);
		return $step;
	}
}
