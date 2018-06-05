<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2018 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount;

use marttiphpbb\usertopiccount\service\update;

class ext extends \phpbb\extension\base
{
	/**
	 * phpBB 3.2.2+ and PHP 7+
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');
		return phpbb_version_compare($config['version'], '3.2.2', '>=') && version_compare(PHP_VERSION, '7', '>=');
	}

	/**
	* @param mixed $old_state
	* @return mixed false after last step, otherwise temporary state
	*/
	public function enable_step($old_state)
	{
		$db = $this->container->get('dbal.conn');
		$posts_table = $this->container->getParameter('tables.posts');
		$topics_table = $this->container->getParameter('tables.topics');
		$users_table = $this->container->getParameter('tables.users');		

		if (!$old_state)
		{
			$db_tools = $this->container->get('dbal.tools');

			// the user_topic_count column has to be present
			$db_tools->perform_schema_changes([
				'add_columns'	=> [
					$users_table	=> [
						'user_topic_count'		=> ['UINT', 0],
					],
				],
			]);

			$sql = 'select max(user_id) as last_id 
				from ' . $users_table;
			$result = $db->sql_query($sql);
			$last_id = $db->sql_fetchfield('last_id');
			$db->sql_freeresult($result);

			$start = 1;
		}
		else if (strpos($old_state, 'user_topic_count_set_' === 0))
		{
			$step = str_replace('user_topic_count_set_', '', $old_state);
			list($start, $last_id) = explode('_', $step);
		}
		else
		{
			return parent::enable_step($old_state);
		}

		$end = ($start + 1000) > $last_id ? $last_id : $start + 1000;

		$update = new update($db, $posts_table, $topics_table, $users_table);

		$update->for_user_range($start, $end);

		if ($end >= $last_id)
		{
			error_log('user_topic_count_updated');
			return 'user_topic_count_updated';
		}

		$step = 'user_topic_count_set_' . $end . '_' . $last_id;
		error_log($step);
		return $step;
	}
}
