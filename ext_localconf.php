<?php
// $Id: ext_localconf.php,v 1.3 2008/06/26 10:49:12 Martin.Kuster Exp $

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

## eID definition
$TYPO3_CONF_VARS['FE']['eID_include']['kn_sitemap_pi_ajax'] = 'EXT:kn_sitemap/pi1/ajax.php';

## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_knsitemap_pi1 = < plugin.tx_knsitemap_pi1.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_knsitemap_pi1.php','_pi1','list_type',0);
?>