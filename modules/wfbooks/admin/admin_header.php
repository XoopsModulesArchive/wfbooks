<?php
/**
 * $Id: admin_header.php v 1.00 21 June 2005 John N Exp $
 * Module: WF-Links
 * Version: v1.0.3
 * Release Date: 21 June 2005
 * Developer: John N
 * Team: WF-Projects
 * Licence: GNU
 */
 
include '../../../mainfile.php';
include '../../../include/cp_header.php';

include XOOPS_ROOT_PATH.'/modules/'.$xoopsModule->getVar('dirname').'/include/config.php';
include_once XOOPS_ROOT_PATH.'/modules/'.$xoopsModule->getVar('dirname').'/include/functions.php';
include_once XOOPS_ROOT_PATH.'/modules/'.$xoopsModule->getVar('dirname').'/class/wfl_lists.php';
include_once XOOPS_ROOT_PATH.'/modules/'.$xoopsModule->getVar('dirname').'/class/myts_extended.php';

include_once XOOPS_ROOT_PATH . '/class/xoopstree.php';
include_once XOOPS_ROOT_PATH . '/class/xoopslists.php';
include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

$wfmyts = new wflTextSanitizer(); // MyTextSanitizer object

$imagearray = array(
	'editimg' => "<img src='../images/icon/world_edit.png' alt='" . _AM_WFB_ICO_EDIT . "' align='middle'>",
    'deleteimg' => "<img src='../images/icon/world_delete.png' alt='" . _AM_WFB_ICO_DELETE . "' align='middle'>",
	'altcat' => "<img src='../images/icon/folder_add.png' alt='" . _AM_WFB_ALTCAT_CREATEF . "' align='middle'>",
    'online' => "<img src='../images/icon/on.png' alt='" . _AM_WFB_ICO_ONLINE . "' align='middle'>",
    'offline' => "<img src='../images/icon/off.png' alt='" . _AM_WFB_ICO_OFFLINE . "' align='middle'>",
    'approved' => "<img src='../images/icon/on.png' alt=''" . _AM_WFB_ICO_APPROVED . "' align='middle'>",
    'notapproved' => "<img src='../images/icon/off.png' alt='" . _AM_WFB_ICO_NOTAPPROVED . "' align='middle'>",
    'relatedfaq' => "<img src='../images/icon/link.gif' alt='" . _AM_WFB_ICO_LINK . "' align='absmiddle'>",
    'relatedurl' => "<img src='../images/icon/urllink.gif' alt='" . _AM_WFB_ICO_URL . "' align='middle'>",
    'addfaq' => "<img src='../images/icon/add.gif' alt='" . _AM_WFB_ICO_ADD . "' align='middle'>",
    'approve' => "<img src='../images/icon/accept.png' alt='" . _AM_WFB_ICO_APPROVE . "' align='middle'>",
    'statsimg' => "<img src='../images/icon/stats.gif' alt='" . _AM_WFB_ICO_STATS . "' align='middle'>",
	'ignore' => "<img src='../images/icon/cross.png' alt='" . _AM_WFB_ICO_IGNORE . "' align='middle'>",
    'ack_yes' => "<img src='../images/icon/on.png' alt='" . _AM_WFB_ICO_ACK . "' align='middle'>",
	'ack_no' => "<img src='../images/icon/off.png' alt='" . _AM_WFB_ICO_REPORT . "' align='middle'>",
    'con_yes' => "<img src='../images/icon/on.png' alt='" . _AM_WFB_ICO_CONFIRM . "' align='middle'>",
	'con_no' => "<img src='../images/icon/off.png' alt='" . _AM_WFB_ICO_CONBROKEN . "' align='middle'>",
	'view' => "<img src='../images/icon/view.png' alt='" . _AM_WFB_ICO_VIEW . "' align='middle'>"
	);

//$_database_arr = array( "alltypes", "published", "autoart", "submitted", "online", "offline", "expired", "autoexpire", "noshowart", "user" );
	
?>