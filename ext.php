<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2018 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace marttiphpbb\usertopiccount;

class ext extends \phpbb\extension\base
{
	/**
	 * phpBB 3.2.x and PHP 7+
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');
		return phpbb_version_compare($config['version'], '3.2', '>=') && version_compare(PHP_VERSION, '7', '>=');
	}

	/**
	* @param mixed $old_state
	* @return mixed false after last step, otherwise temporary state
	*/
	public function enable_step($old_state)
	{
		$db = $this->container->get('dbal.conn');
		$table_prefix = $this->container->getParameter('core.table_prefix');

		if (!$old_state)
		{
			$db_tools = $this->container->get('dbal.tools');

			// the user_topic_count column has to be present
			$db_tools->perform_schema_changes([
				'add_columns'	=> [
					$table_prefix . 'users'	=> [
						'user_topic_count'		=> ['UINT', 0],
					],
				],
			]);

			$sql = 'SELECT MAX(user_id) as last_id FROM ' . $table_prefix . 'users';
			$result = $db->sql_query($sql);
			$last_id = $db->sql_fetchfield('last_id');
			$db->sql_freeresult($result);

			$start = 0;
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

		$sql = 'SELECT COUNT(t.topic_id) as count, u.user_id, u.user_topic_count
				FROM ' . $table_prefix . 'topics t, ' . $table_prefix . 'users u
				WHERE t.topic_visibility = ' . ITEM_APPROVED . '
					AND t.topic_poster = u.user_id
					AND u.user_id >= ' . $start . '
					AND u.user_id < ' . $end . '
					AND u.user_id <> ' . ANONYMOUS . '
				GROUP BY t.topic_poster
				HAVING COUNT(t.topic_id) <> u.user_topic_count';
		$result = $db->sql_query($sql);
		$users = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		foreach ($users as $user)
		{
			$sql = 'UPDATE ' . $table_prefix . 'users
				SET user_topic_count = ' . $user['count'] . '
				WHERE user_id = ' . $user['user_id'];
			$db->sql_query($sql);
		}

		if ($end >= $last_id)
		{
			return 'user_topic_count';
		}

		return 'user_topic_count_set_' . $end . '_' . $last_id;
	}
}
