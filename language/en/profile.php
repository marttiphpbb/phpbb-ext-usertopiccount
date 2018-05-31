<?php

/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [

	'USERTOPICCOUNT_TOTAL'				=> 'Total topics',
	'USERTOPICCOUNT_SEARCH'				=> 'Search user\'s topics',
	'USERTOPICCOUNT_SEARCH_YOUR_TOPICS'	=> 'Show your topics',

]);
