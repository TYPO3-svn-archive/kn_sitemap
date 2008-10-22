<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Martin Kuster (martin.kuster@kuehne-nagel.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @package	TYPO3
 * @subpackage kn_sitemap
 * @version $Id: class.tx_knsitemap_pi1.php,v 1.10 2008/10/16 11:10:09 Martin.Kuster Exp $
 */

require_once (PATH_tslib . 'class.tslib_pibase.php');
require_once (t3lib_extMgm::extPath("jquery") . "class.tx_jquery.php");

/**
 * Plugin 'Sitemap' for the 'kn_sitemap' extension.
 *
 * @author Martin Kuster <martin.kuster@kuehne-nagel.com>
 * @package	TYPO3
 * @subpackage kn_sitemap
 * @version $Id: class.tx_knsitemap_pi1.php,v 1.10 2008/10/16 11:10:09 Martin.Kuster Exp $
 *
 */
class tx_knsitemap_pi1 extends tslib_pibase {

	/*
	 * Prefix ID
	 * @var string
	 */
	public $prefixId = 'tx_knsitemap_pi1';

	/*
	 * Path to this script relative to the extension dir.
	 * @var string
	 */
	public $scriptRelPath = 'pi1/class.tx_knsitemap_pi1.php';

	/*
	 * The extension key.
	 * @var string
	 */
	public $extKey = 'kn_sitemap';

	/*
	 * Starting Point (ID)
	 * @var integer
	 */
	private $startPoint;

	/*
	 * Where string of get pages sql statement
	 * @var string
	 */
	private $whereKriterium;

	/*
	 * Pages to hide
	 * @var string
	 */
	private $notShow;

	/*
	 * What to show?
	 * @var integer
	 */
	private $showMore;

	/*
	 * Doktypes to show
	 * @var string
	 */
	private $showDoktyp;

	/*
	 * JS-Code to append to the content
	 * @var string
	 */
	private $closeJSCode;

	/*
	 * Array of icons.
	 *
	 * Structure: 'DOKTYPE' => array('icon' => 'ICON','target' => 'TARGET')
	 *
	 * @var array
	 */
	private $iconArray;

	/*
	 * URI of the request
	 * @var string
	 */
	private $requestUri;

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->init($conf);
		$content = '';

		$content .= '<ul>' . $this->outputStartpoint() . $this->outputSubpages() . '</ul>';

		$content .= '<p class="closeAll"><a href="' . $this->requestUri . '#" onClick="closeAll();">Close all</a></p>';

		$this->addHeaderData();

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Initialisierung
	 *
	 * @param array $conf: The PlugIn configuration
	 * @return void
	 */
	function init(array $conf) {
		$this->conf = $conf; // Setting the TypoScript passed to this function in $this->conf
		$this->pi_setPiVarDefaults(); // GP-parameter configuration
		$this->pi_loadLL(); // Loading the LOCAL_LANG values
		$this->pi_USER_INT_obj = 1; // Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		$this->closeJSCode = '';

		$this->initPluginConf();

		$this->initTSConfig();

		$this->initJQuery();

		$this->initWhereClause();
	}

	/**
	 * Erstellen where-Bedingungen
	 * @return void
	 */
	function initWhereClause() {
		$this->whereKriterium = '
			AND deleted=0
			AND hidden=0
			AND nav_hide=0
			AND NOT(`t3ver_state`=1)
			AND doktype IN (' . $this->showDoktyp . ')
			AND uid NOT IN (' . $this->notShow . ')
			AND (`starttime`<=' . time() . ')
			AND (`endtime`=0
				OR `endtime`>' . time() . ')
			AND (`fe_group`=\'\'
				OR `fe_group` IS NULL
				OR `fe_group`=\'0\'
				OR (`fe_group` LIKE \'%,0,%\'
					OR `fe_group` LIKE \'0,%\'
					OR `fe_group` LIKE \'%,0\'
					OR `fe_group`=\'0\')
				OR (`fe_group` LIKE \'%,-1,%\'
					OR `fe_group` LIKE \'-1,%\'
					OR `fe_group` LIKE \'%,-1\'
					OR `fe_group`=\'-1\'))
		';
	}


	/**
	 * Auslesen Plugin-Konf
	 * @return void
	 */
	function initPluginConf() {
		$this->pi_initPIflexForm(); // Init FlexForms array
		$this->ff = $this->cObj->data['pi_flexform'];

		// Startingpoint:
		$this->startPoint = $this->pi_getFFvalue($this->ff, 'startingpoint', 'sDEF');
		// Exclude
		$this->notShow = $this->pi_getFFvalue($this->ff, 'exclude_pages', 'sDEF');
		if (strlen($this->notShow) < 1) $this->notShow = '0';
		// URI:
		$this->requestUri = $_SERVER["REQUEST_URI"];
	}

	/**
	 * Init jquery
	 * @return void
	 */
	function initJQuery() {
		tx_jquery::setPlugins(array());
		tx_jquery::includeLib();
		tx_jquery::setCompatibility(false);
	}

	/**
	 * Init TS Konfig
	 * @return void
	 */
	function initTSConfig() {
		// TODO: TS-Konf:
		$showDescription = '1';
		$showSubtitle = '1';
		$doktype = '1,2,3,4,111';

		if ($showDescription == '1') {
			if ($showSubtitle == '1') {
				$this->showMore = '3';
			} else {
				$this->showMore = '1';
			}
		} else {
			if ($showSubtitle == '1') {
				$this->showMore = '2';
			} else {
				$this->showMore = '0';
			}
		}

		$this->showDoktyp = $doktype;

		$this->iconArray = array('0' => array('icon' => 'pages.gif', 'target' => '_top'), '1' => array('icon' => 'pages.gif', 'target' => '_top'), '2' => array('icon' => 'pages.gif', 'target' => '_top'), '3' => array('icon' => 'pages_link.gif', 'target' => '_blank'), '4' => array('icon' => 'pages.gif', 'target' => '_top'), '111' => array('icon' => 'pages.gif', 'target' => '_top'));
	}

	/**
	 * Printing of 1-level subpages (always visible)
	 *
	 * @return string output
	 */
	function outputSubpages() {
		$content = '';
		$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'pid=' . $this->startPoint . $this->whereKriterium);
		// Count subpages
		$countSubpages = $GLOBALS['TYPO3_DB']->sql_num_rows($res2);
		$i = 1;
		// Every subpage
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) {
			$linkToSubpages = '';
			$class = '';
			$titleTag = $this->getTitleTag($this->showMore, $row);
			// has page subpages?
			$res3 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid=' . $row['uid'] . $this->whereKriterium);
			// Count sub-subpages
			$countSubSubpages = $GLOBALS['TYPO3_DB']->sql_num_rows($res3);
			// if subsubpages exists
			if ($countSubSubpages > 0) {
				// Last subsubpage?
				if ($i >= $countSubpages) {
					// Print open link icon ended
					$linkToSubpages = '<span class="open" title="Open" onClick="upd(\'' . $row['uid'] . '\',\'1\',\'0\');"><img src="typo3conf/ext/kn_sitemap/res/plusbottom.gif" width="18" height="16" alt="+" /></span>';
					$class = 'hasSubpages';
				} else {
					// Print open link icon normal
					$linkToSubpages = '<span class="open" title="Open" onClick="upd(\'' . $row['uid'] . '\',\'0\',\'0\');"><img src="typo3conf/ext/kn_sitemap/res/plus.gif" width="18" height="16" alt="+" /></span	>';
					$class = 'hasSubpages';
				}
			} else {
				// Last subpage?
				if ($i >= $countSubpages) {
					// Print icon ended
					$linkToSubpages = '<img src="typo3conf/ext/kn_sitemap/res/joinbottom.gif" width="18" height="16" alt="-" />';
				} else {
					// Print icon normal
					$linkToSubpages = '<img src="typo3conf/ext/kn_sitemap/res/join.gif" width="18" height="16" alt="-" />';
				}
			}

			// Print output for this subpage:
			$x = '<li class="' . $class . '" id="UL' . $row['uid'] . '">' . $linkToSubpages;
			$x .= '<a href="' . $this->pi_getPageLink($row['uid'], '', array()) . '" title="' . $titleTag . '" target="' . $this->iconArray[$row['doktype']]['target'] . '"><img src="typo3conf/ext/kn_sitemap/res/' . $this->iconArray[$row['doktype']]['icon'] . '" width="16" height="16" title="' . $titleTag . '" alt="' . $titleTag . '" />' . $row['title'] . '</a></li>';

			// Add subpage to close all JS-code
			$this->closeJSCode .= '$j(\'#UL' . $row['uid'] . '\').replaceWith("' . str_replace('"', '\\"', $x) . '");' . "\n";
			$content .= $x;

			$i++;
		}

		return $content;
	}

	/**
	 * Print strating-point
	 *
	 * @return string output
	 */
	function outputStartpoint() {
		$content = '';
		$res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid=' . $this->startPoint . $this->whereKriterium);
		$thisUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1);

		$titleTag = $this->getTitleTag($this->showMore, $thisUid);

		$content .= '<li id="UL' . $thisUid['uid'] . '"><a href="?id=' . $thisUid['uid'] . '" title="' . $titleTag . '"><img src="typo3conf/ext/kn_sitemap/res/pages.gif" width="16" height="16" alt="' . $titleTag . '" title="' . $titleTag . '" />' . $thisUid['title'] . '</a></li>';

		return $content;
	}

	/**
	 * get title-tag
	 *
	 * @param int config
	 * @param array record
	 * @return String rendered content of title-tag
	 */
	function getTitleTag($config, array $data) {
		$titleTag = '';

		if ($config == "1") {
			// desc
			if (strlen($data['description']) > 0) {
				$titleTag = $data['description'];
			} else {
				$titleTag = $data['title'];
			}
		} elseif ($config == "2") {
			// sub
			if (strlen($data['subtitle']) > 0) {
				$titleTag = $data['subtitle'];
			} else {
				$titleTag = $data['title'];
			}
		} elseif ($config == "3") {
			//both
			if (strlen($data['description']) > 0) {
				if (strlen($data['subtitle']) > 0) {
					$titleTag = $data['subtitle'] . ', ' . $data['description'];
				} else {
					$titleTag = $data['description'];
				}
			} else {
				if (strlen($data['subtitle']) > 0) {
					$titleTag = $data['subtitle'];
				} else {
					$titleTag = $data['title'];
				}
			}
		}
		return $titleTag;
	}

	/**
	 * Add header-data to page
	 * @return void
	 */
	function addHeaderData() {
		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] = '
<script type="text/javascript" language="javascript">
	// <![CDATA[
		var $j = jQuery.noConflict();

		function upd(myUid,last,close){
			$j.ajax({
				url: \'index.php?eID=kn_sitemap_pi_ajax&t=' . time() . '\',
    			type: \'POST\',
    			data: {
    				uid: myUid,
    				exclude: \'' . htmlspecialchars(serialize($this->notShow)) . '\',
    				isLast: last,
    				toClose: close,
    				requestUri: \'' . $this->requestUri . '\',
    				showMore: \'' . $this->showMore . '\',
    				showDoktyp: \'' . htmlspecialchars(serialize($this->showDoktyp)) . '\',
    				iconArray: \'' . htmlspecialchars(serialize($this->iconArray)) . '\'
				},
    			dataType: \'text\',
			    timeout: 10000,
			    success: function(result){
			        $j(\'#UL\'+myUid).replaceWith(result);
			    },
			    error: function(res){
			    	alert("Error: "+res);
			    }
			});
		}

		function closeAll(){
			' . $this->closeJSCode . '
		}

		$(function(){
		//	$.ajaxHistory.initialize();

		});
	// ]]>
</script>
		';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kn_sitemap/pi1/class.tx_knsitemap_pi1.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kn_sitemap/pi1/class.tx_knsitemap_pi1.php']);
}

?>