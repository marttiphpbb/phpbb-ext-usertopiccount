<?php
/**
* phpBB Extension - marttiphpbb usertopiccount
* @copyright (c) 2015 marttiphpbb <info@martti.be>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* Translated By : Basil Taha Alhitary - www.alhitary.net
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
	'USERTOPICCOUNT_TOTAL'				=> 'إجمالي المواضيع ',
	'USERTOPICCOUNT_SEARCH'				=> 'البحث عن مواضيع العضو',
	'USERTOPICCOUNT_SEARCH_YOUR_TOPICS'	=> 'اظهار مواضيعك',
]);
