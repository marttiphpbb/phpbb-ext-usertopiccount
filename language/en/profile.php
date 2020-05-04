<?php

/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 - 2020 marttiphpbb <info@martti.be>
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

	'MARTTIPHPBB_USERTOPICCOUNT_TOTAL'				=> 'Total topics',
	'MARTTIPHPBB_USERTOPICCOUNT_SEARCH'				=> 'Search user’s topics',
	'MARTTIPHPBB_USERTOPICCOUNT_SEARCH_YOUR_TOPICS'	=> 'Show your topics',

]);
