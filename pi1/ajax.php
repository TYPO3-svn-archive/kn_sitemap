<?
/**
 * Ajax-Class
 *
 * @author 		Martin Kuster <martin.kuster@kuehne-nagel.com>
 * @package 	TYPO3
 * @subpackage 	kn_sitemap
 * @version 	$Id: ajax.php,v 1.8 2008/10/21 15:36:28 Martin.Kuster Exp $
 * @todo 		Add comments
 */

ini_set('display_errors', 'on');
//ini_set('error_reporting', E_ALL);
?>
<?php

//if (!defined ('PATH_typo3conf')) 	die ('Access denied.');


//var_dump(unserialize(htmlspecialchars_decode($_POST['iconArray'])));
//exit;
require_once (PATH_tslib . 'class.tslib_content.php');
require_once (PATH_t3lib . 'class.t3lib_page.php');
require_once (PATH_tslib . 'class.tslib_fe.php');
require_once (PATH_t3lib . 'class.t3lib_cs.php');
require_once (PATH_t3lib . 'class.t3lib_userauth.php');
require_once (PATH_tslib . 'class.tslib_feuserauth.php');
require_once (PATH_t3lib . 'class.t3lib_tstemplate.php');

### Initialisierung:
tslib_eidtools::connectDB();

$cObj = t3lib_div::makeInstance('tslib_cObj');

$temp_TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
$TSFE = new $temp_TSFEclassName($TYPO3_CONF_VARS, t3lib_div::_GP('id'), t3lib_div::_GP('type'), t3lib_div::_GP('no_cache'), t3lib_div::_GP('cHash'), t3lib_div::_GP('jumpurl'), t3lib_div::_GP('MP'), t3lib_div::_GP('RDCT'));
$TSFE->initFEuser();
$TSFE->determineId();
$TSFE->initTemplate();

$TSFE->getConfigArray();

//$cObj = new tslib_cObj();
//echo '<pre>';
//echo $cObj->getTypoLink_URL(21,array(),'');
//echo 'link:'.$cObj->getTypoLink('label','12');
//var_dump($TSFE->sys_page);
//exit;


### Variablen initialisieren:
$result = '';
$liClass = 'expanded';
$leeren = 0;
$last = 0;
$showMore = 0;
$requestUri = '';

### Input einlesen
$myUid = t3lib_div::_POST('uid');
### Konfiguration
// Menupunkt schliessen?
if (t3lib_div::_POST('toClose') == '1') {
	$leeren = 1;
}
// Letzter Menupunkt?
if (t3lib_div::_POST('isLast') == '1') {
	$last = 1;
}
// Tooltip zeigen?
if (strlen(t3lib_div::_POST('showMore')) > 0) {
	$showMore = t3lib_div::_POST('showMore');
}
// UID wirklich Zahl?
if (!t3lib_div::testInt($myUid)) {
	die('Access denied.');
}
// RequestUri:
$requestUri = t3lib_div::_POST('requestUri');
// NotShow:
$notShow = unserialize(htmlspecialchars_decode(t3lib_div::_POST('exclude')));
// iconArray:
$iconArray = unserialize(htmlspecialchars_decode(t3lib_div::_POST('iconArray')));
// showDoktyp:
$showDoktyp = unserialize(htmlspecialchars_decode(t3lib_div::_POST('showDoktyp')));
// Where String
$whereKriterium = ' AND deleted=0
					AND hidden=0
					AND nav_hide=0
					AND NOT(`t3ver_state`=1)
					AND doktype IN (' . $showDoktyp . ')
					AND uid NOT IN (' . $notShow . ')
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
//FIXME XSS saeubern!


### Ausgewählte UID anzeigen
// DB-Abfrage
$res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid=' . $myUid . $whereKriterium);
$thisUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1);

$titleTag = getTitleTag($showMore, $thisUid);

// Menupunkt schliessen?
if ($leeren) {
	// Ob Subseiten vorhanden muss nicht geprüft werden, da Schliessen nur von Seiten mit Subseiten erfolgen kann.
	// Letzter Menupunkt?
	if ($last) {
		// Öffneder Link abgeschlossen
		$result .= '<span class="open" title="Open" onClick="upd(\'' . $thisUid['uid'] . '\',\'1\',\'0\');"><img src="typo3conf/ext/kn_sitemap/res/plusbottom.gif" width="18" height="16" alt="+" /></span>';
	} else {
		// Öffnender Link normal
		$result .= '<span class="open" title="Open" onClick="upd(\'' . $thisUid['uid'] . '\',\'0\',\'0\');"><img src="typo3conf/ext/kn_sitemap/res/plus.gif" width="18" height="16" alt="+" /></span>';
	}
	// Text-Link
	$result .= '<a href="' . $cObj->getTypoLink_URL($thisUid['uid']) . '" title="' . $titleTag . '" target="' . $iconArray[$thisUid['doktype']]['target'] . '"><img src="typo3conf/ext/kn_sitemap/res/' . $iconArray[$thisUid['doktype']]['icon'] . '" width="16" height="16" title="' . $titleTag . '" alt="' . $titleTag . '" />' . $thisUid['title'] . '</a>';

	// Da Menu geschlossen, Ausgabe und raus
	echo '<li id="UL' . $myUid . '" class="">' . $result . '</li>';
	exit();
} else {
	// Letzter Menupunkt?
	if ($last) {
		// Schliessender Link abgeschlossen:
		$result .= '<span class="close" title="Close" onClick="upd(\'' . $thisUid['uid'] . '\',\'1\',\'1\');"><img src="typo3conf/ext/kn_sitemap/res/minusbottom.gif" width="18" height="16" alt="+" /></span>';
		$liClass = 'expanded last';
	} else {
		// Schliessender Link normal:
		$result .= '<span class="close" title="Close" onClick="upd(\'' . $thisUid['uid'] . '\',\'0\',\'1\');"><img src="typo3conf/ext/kn_sitemap/res/minus.gif" width="18" height="16" alt="+" /></span>';
	}
	// Text-Link
	$result .= '<a href="' . $cObj->getTypoLink_URL($thisUid['uid']) . '" title="' . $titleTag . '" target="' . $iconArray[$thisUid['doktype']]['target'] . '"><img src="typo3conf/ext/kn_sitemap/res/' . $iconArray[$thisUid['doktype']]['icon'] . '" width="16" height="16" title="' . $titleTag . '" alt="' . $titleTag . '" />' . $thisUid['title'] . '</a>';

	//$cObj->getTypoLink('label','12');
	// Subliste starten
	$result .= '<ul>';
}

### Ausgabe Unterpunkte
// DB-Abfrage
$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'pid=' . $myUid . $whereKriterium);
// Anzahl Unterseite => Letzte finden
$countSubpages = $GLOBALS['TYPO3_DB']->sql_num_rows($res2);
$i = 1;
// Für jede Unterseite
while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) {
	$linkToSubpages = '';
	$class = '';
	$titleTag = getTitleTag($showMore, $row);
	// Hat diese Subseiten?
	$res3 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid=' . $row['uid'] . $whereKriterium);
	// Zählen der SubSubseiten
	$countSubSubpages = $GLOBALS['TYPO3_DB']->sql_num_rows($res3);
	// SubSubseiten vorhanden?
	if ($countSubSubpages > 0) {
		// Letzte Subseite?
		if ($i >= $countSubpages) {
			// Öffnender Link abgeschlossen:
			$linkToSubpages = '<span class="open" title="Open" onClick="upd(\'' . $row['uid'] . '\',\'1\',\'0\');"><img src="typo3conf/ext/kn_sitemap/res/plusbottom.gif" width="18" height="16" alt="+" /></span>';
			$class = 'hasSubpages';
		} else {
			// Öffnender Link normal:
			$linkToSubpages = '<span class="open" title="Open" onClick="upd(\'' . $row['uid'] . '\',\'0\',\'0\');"><img src="typo3conf/ext/kn_sitemap/res/plus.gif" width="18" height="16" alt="+" /></span>';
			$class = 'hasSubpages';
		}
	} else {
		// Letzte Subseite?
		if ($i >= $countSubpages) {
			// Strich abgeschlossen:
			$linkToSubpages = '<img src="typo3conf/ext/kn_sitemap/res/joinbottom.gif" width="18" height="16" alt="-" />';
		} else {
			//Strich normal:
			$linkToSubpages = '<img src="typo3conf/ext/kn_sitemap/res/join.gif" width="18" height="16" alt="-" />';
		}
	}
	// Zusammenbau Ausgabe für diese Subseite:
	$result .= '<li class="' . $class . '" id="UL' . $row['uid'] . '">' . $linkToSubpages;
	$result .= '<a href="' . $cObj->getTypoLink_URL($row['uid']) . '" title="' . $titleTag . '" target="' . $iconArray[$row['doktype']]['target'] . '"><img src="typo3conf/ext/kn_sitemap/res/' . $iconArray[$row['doktype']]['icon'] . '" width="16" height="16" title="' . $titleTag . '" alt="' . $titleTag . '" />' . $row['title'] . '</a></li>';

	$i++;
}

### Ende:
// Liste schliessen
$result .= '</ul>';
// Ausgabe in <li> packen
$result = '<li id="UL' . $myUid . '" class="' . $liClass . '">' . $result . '</li>';

// Ausgabe:
echo $result;

/**
 * Erzeugt title-Tag
 */
function getTitleTag($config, $data) {
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
	} else {
		$titleTag = $data['title'];
	}

	return $titleTag;
}

?>