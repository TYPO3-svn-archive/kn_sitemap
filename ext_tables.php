<?php
// $Id: ext_tables.php,v 1.2 2008/06/26 10:49:12 Martin.Kuster Exp $

if (!defined('TYPO3_MODE')) die('Access denied.');

t3lib_extMgm::addStaticFile($_EXTKEY, 'static/default/', 'Default Stylesheet');

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'tx_knsitemap_category;;;;1-1-1, pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY . '_pi1', 'FILE:EXT:kn_sitemap/flexform_ds.xml');

t3lib_extMgm::addPlugin(array(
	'LLL:EXT:kn_sitemap/locallang_db.xml:tt_content.list_type_pi1',
	$_EXTKEY . '_pi1'
), 'list_type');

if (TYPO3_MODE == "BE") $TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_knsitemap_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY) . 'pi1/class.tx_knsitemap_pi1_wizicon.php';
?>