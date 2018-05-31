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

	'USERTOPICCOUNT_TOTAL'				=> 'Temas totales',
	'USERTOPICCOUNT_SEARCH'				=> 'Buscar temas del usuario',
	'USERTOPICCOUNT_SEARCH_YOUR_TOPICS'	=> 'Mostrar sus temas',

]);
