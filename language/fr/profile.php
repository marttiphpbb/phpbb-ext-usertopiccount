<?php
/**
*
* User Topic Count extension for the phpBB Forum Software package.
* French translation by tomberaid (http://www.worshiprom.com/)
*
* @copyright (c) 2015 - 2018 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*
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
	'MARTTIPHPBB_USERTOPICCOUNT_TOTAL'				=> 'Sujets',
	'MARTTIPHPBB_USERTOPICCOUNT_SEARCH'				=> 'Rechercher les sujets de l’utilisateur',
	'MARTTIPHPBB_USERTOPICCOUNT_SEARCH_YOUR_TOPICS'	=> 'Voir vos sujets',
]);
