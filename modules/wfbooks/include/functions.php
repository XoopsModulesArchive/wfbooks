<?php
/**
 * $Id: functions.php v 1.00 21 June 2005 John N Exp $
 * Module: WF-Links
 * Version: v1.0.3
 * Release Date: 21 June 2005
 * Developer: John N
 * Team: WF-Projects
 * Licence: GNU
 */
if (!defined('XOOPS_ROOT_PATH')) {
	die('XOOPS root path not defined');
}
/**
 * wfs_gethandler()
 * 
 * @param  $name 
 * @param boolean $optional 
 * @return 
 */
function &wfl_gethandler( $name, $optional = false ) {
    global $handlers, $xoopsModule;

    $name = strtolower( trim( $name ) );
    if ( !isset( $handlers[$name] ) ) {
        if ( file_exists( $hnd_file = XOOPS_ROOT_PATH . '/modules/' . $xoopsModule -> getVar( 'dirname' ) . '/class/class_' . $name . '.php' ) ) {
            require_once $hnd_file;
        } 
        $class = 'wfl' . ucfirst( $name ) . 'Handler';
        if ( class_exists( $class ) ) {
            $handlers[$name] = new $class( $GLOBALS['xoopsDB'] );
        } 
    } 
    if ( !isset( $handlers[$name] ) && !$optional ) {
        trigger_error( '<div>Class <b>' . $class . '</b> does not exist.</div>
						<div>Handler Name: ' . $name, E_USER_ERROR ) . '</div>';
    } 
    return isset( $handlers[$name] ) ? $handlers[$name] : false;
} 

function checkgroups( $cid = 0, $permType = 'WFBookCatPerm', $redirect = false ) {

    global $xoopsUser, $xoopsModule;

    $groups = is_object( $xoopsUser ) ? $xoopsUser -> getGroups() : XOOPS_GROUP_ANONYMOUS;
    $gperm_handler = &xoops_gethandler( 'groupperm' );
    if ( !$gperm_handler -> checkRight( $permType, $cid, $groups, $xoopsModule -> getVar( 'mid' ) ) ) {
        if ( $redirect == false ) {
            return false;
        } else {
            redirect_header( 'index.php', 3, _NOPERM );
            exit();
        } 
    } 
    return true;
} 

function getVoteDetails( $lid = 0 ) {
    global $xoopsDB;

    $sql = "SELECT 
		COUNT(rating) AS rate, 
		MIN(rating) AS min_rate, 
		MAX(rating) AS max_rate, 
		AVG(rating) AS avg_rate, 
		COUNT(ratinguser) AS rating_user, 
		MAX(ratinguser) AS max_user, 
		MAX(title) AS max_title, 
		MIN(title) AS min_title, 
		sum(ratinguser = 0) AS null_ratinguser 
		FROM " . $xoopsDB -> prefix( 'wfbooks_votedata' );
    if ( $lid > 0 ) {
        $sql .= " WHERE lid = $lid";
    } 
    if ( !$result = $xoopsDB -> query( $sql ) ) {
        return false;
    } 
    $ret = $xoopsDB -> fetchArray( $result );

    return $ret;
} 

function calcVoteData( $sel_id = 0 ) {
    $ret = array();
    $ret['useravgrating'] = 0;

    $sql = 'SELECT rating FROM ' . $xoopsDB -> prefix( 'wfbooks_votedata' );
    if ( $sel_id != 0 ) {
        " WHERE lid = " . $sel_id;
    } 
    if ( !$result = $xoopsDB -> query( $sql ) ) {
        return false;
    } 
    $ret['uservotes'] = $xoopsDB -> getRowsNum( $result );
    while ( list( $rating ) = $xoopsDB -> fetchRow( $result ) ) {
        $ret['useravgrating'] += intval( $rating );
    } 
    if ( $ret['useravgrating'] > 0 ) {
        $ret['useravgrating'] = number_format( ( $ret['useravgrating'] / $ret['uservotes'] ), 2 );
    } 
    return $ret;
} 

function wfl_cleanRequestVars( &$array, $name = null, $def = null, $strict = false, $lengthcheck = 15 ) {

// Sanitise $_request for further use.  This method gives more control and security.
// Method is more for functionality rather than beauty at the moment, will correct later.
    unset( $array['usercookie'] );
    unset( $array['PHPSESSID'] );

    if ( is_array( $array ) && $name == null ) {
        $globals = array();
        foreach ( array_keys( $array ) as $k ) {
            $value = strip_tags( trim( $array[$k] ) );
            if ( strlen( $value >= $lengthcheck ) ) {
                return null;
            } 
            if ( ctype_digit( $value ) ) {
                $value = intval( $value );
            } else  {
                if ( $strict == true ) {
                    $value = preg_replace( '/\W/', '', trim( $value ) );
                } 
                $value = strtolower( strval( $value ) );
            } 
            $globals[$k] = $value;
        } 
        return $globals;
    } 
    if ( !isset( $array[$name] ) || !array_key_exists( $name, $array ) ) {
        return $def;
    } else {
        $value = strip_tags( trim( $array[$name] ) );
    } 
    if ( ctype_digit( $value ) ) {
        $value = intval( $value );
    } else {
        if ( $strict == true ) {
            $value = preg_replace( '/\W/', '', trim( $value ) );
        } 
        $value = strtolower( strval( $value ) );
    } 
    return $value;
} 


// toolbar()
// @return
function wfl_toolbar( $cid = 0 ) {
    $toolbar = "[ ";
    if ( true == checkgroups( $cid, 'WFBookSubPerm' ) ) {
        $toolbar .= "<a href='submit.php?cid=" . $cid . "'>" . _MD_WFB_SUBMITLINK . "</a> | ";
    } 
    $toolbar .= "<a href='newlist.php?newlinkshowdays=7'>" . _MD_WFB_LATESTLIST . "</a> | <a href='topten.php?list=hit'>" . _MD_WFB_POPULARITY . "</a> | <a href='topten.php?list=rate'>" . _MD_WFB_TOPRATED . "</a> ]";
    return $toolbar;
} 

// wfl_serverstats()
// @return
function wfl_serverstats() {
    echo "<fieldset><legend style='font-weight: bold; color: #0A3760;'>" . _AM_WFB_LINK_IMAGEINFO . "</legend>\n
		<div style='padding: 8px;'>\n
		<div>" . _AM_WFB_LINK_SPHPINI . "</div>\n";

    $safemode = ( ini_get( 'safe_mode' ) ) ? _AM_WFB_LINK_ON . _AM_WFB_LINK_SAFEMODEPROBLEMS : _AM_WFB_LINK_OFF;
    $registerglobals = ( ini_get( 'register_globals' ) == '' ) ? _AM_WFB_LINK_OFF : _AM_WFB_LINK_ON;
    $links = ( ini_get( 'file_uploads' ) ) ? _AM_WFB_LINK_ON : _AM_WFB_LINK_OFF;

    $gdlib = ( function_exists( 'gd_info' ) ) ? _AM_WFB_LINK_GDON : _AM_WFB_LINK_GDOFF;
    echo "<li>" . _AM_WFB_LINK_GDLIBSTATUS . $gdlib;
    if ( function_exists( 'gd_info' ) ) {
        if ( true == $gdlib = gd_info() ) {
            echo "<li>" . _AM_WFB_LINK_GDLIBVERSION . "<b>" . $gdlib['GD Version'] . "</b>";
        } 
    } 
    echo "<br /><br />\n\n";
    echo "<li>" . _AM_WFB_LINK_SAFEMODESTATUS . $safemode;
    echo "<li>" . _AM_WFB_LINK_REGISTERGLOBALS . $registerglobals;
    echo "<li>" . _AM_WFB_LINK_SERVERUPLOADSTATUS . $links;
    echo "</div>";
    echo "</fieldset><br />";
} 

// displayicons()
// @param  $time
// @param integer $status
// @param integer $counter
// @return
function wfl_displayicons( $time, $status = 0, $counter = 0 ) {
    global $xoopsModuleConfig, $xoopsModule;

    $new = '';
    $pop = '';

    $newdate = ( time() - ( 86400 * intval( $xoopsModuleConfig['daysnew'] ) ) );
    $popdate = ( time() - ( 86400 * intval( $xoopsModuleConfig['daysupdated'] ) ) ) ;

    if ( $xoopsModuleConfig['displayicons'] != 3 ) {
        if ( $newdate < $time ) {
            if ( intval( $status ) > 1 ) {
                if ( $xoopsModuleConfig['displayicons'] == 1 )
                    $new = "&nbsp;<img src=" . XOOPS_URL . "/modules/" . $xoopsModule -> getVar( 'dirname' ) . "/images/icon/update.png alt='' align ='absmiddle'/>";
                if ( $xoopsModuleConfig['displayicons'] == 2 )
                    $new = "<i>Updated!</i>";
            } else {
                if ( $xoopsModuleConfig['displayicons'] == 1 )
                    $new = "&nbsp;<img src=" . XOOPS_URL . "/modules/" . $xoopsModule -> getVar( 'dirname' ) . "/images/icon/new.png alt='' align ='absmiddle'/>";
                if ( $xoopsModuleConfig['displayicons'] == 2 )
                    $new = "<i>New!</i>";
            }
        } 
        if ( $popdate > $time ) {
            if ( $counter >= $xoopsModuleConfig['popular'] ) {
                if ( $xoopsModuleConfig['displayicons'] == 1 )
                    $pop = "&nbsp;<img src =" . XOOPS_URL . "/modules/" . $xoopsModule -> getVar( 'dirname' ) . "/images/icon/popular.png alt='' align ='absmiddle'/>";
                if ( $xoopsModuleConfig['displayicons'] == 2 )
                    $pop = "<i>Popular!</i>";
            } 
        } 
    } 
    $icons = $new . " " . $pop;
    return $icons;
} 

if ( !function_exists( 'wfl_convertorderbyin' ) ) {
    // Reusable Link Sorting Functions
    // wfl_convertorderbyin()
    // @param  $orderby
    // @return
    function wfl_convertorderbyin( $orderby ) {
        switch ( trim( $orderby ) ) {
            case "titleA":
                $orderby = "title ASC";
                break;
            case "dateA":
                $orderby = "published ASC";
                break;
            case "hitsA":
                $orderby = "hits ASC";
                break;
            case "ratingA":
                $orderby = "rating ASC";
                break;
            case "countryA":
                $orderby = "country ASC";
                break;
            case "titleD":
                $orderby = "title DESC";
                break;
            case "hitsD":
                $orderby = "hits DESC";
                break;
            case "ratingD":
                $orderby = "rating DESC";
                break;
            case"dateD":
                $orderby = "published DESC";
                break;
            case "countryD":
                $orderby = "country DESC";
                break;
        }
        return $orderby;
    } 
} 
if ( !function_exists( 'wfl_convertorderbytrans' ) ) {
    function wfl_convertorderbytrans( $orderby ) {
        if ( $orderby == "hits ASC" ) $orderbyTrans = _MD_WFB_POPULARITYLTOM;
        if ( $orderby == "hits DESC" ) $orderbyTrans = _MD_WFB_POPULARITYMTOL;
        if ( $orderby == "title ASC" ) $orderbyTrans = _MD_WFB_TITLEATOZ;
        if ( $orderby == "title DESC" ) $orderbyTrans = _MD_WFB_TITLEZTOA;
        if ( $orderby == "published ASC" ) $orderbyTrans = _MD_WFB_DATEOLD;
        if ( $orderby == "published DESC" ) $orderbyTrans = _MD_WFB_DATENEW;
        if ( $orderby == "rating ASC" ) $orderbyTrans = _MD_WFB_RATINGLTOH;
        if ( $orderby == "rating DESC" ) $orderbyTrans = _MD_WFB_RATINGHTOL;
        if ( $orderby == "country ASC" ) $orderbyTrans = _MD_WFB_COUNTRYLTOH;
        if ( $orderby == "country DESC" ) $orderbyTrans = _MD_WFB_COUNTRYHTOL;
        return $orderbyTrans;
    } 
} 
if ( !function_exists( 'wfl_convertorderbyout' ) ) {
    function wfl_convertorderbyout( $orderby ) {
        if ( $orderby == "title ASC" ) $orderby = "titleA";
        if ( $orderby == "published ASC" ) $orderby = "dateA";
        if ( $orderby == "hits ASC" ) $orderby = "hitsA";
        if ( $orderby == "rating ASC" ) $orderby = "ratingA";
        if ( $orderby == "country ASC" ) $orderby = "countryA";
        if ( $orderby == "weight ASC" ) $orderby = "weightA";
        if ( $orderby == "title DESC" ) $orderby = "titleD";
        if ( $orderby == "published DESC" ) $orderby = "dateD";
        if ( $orderby == "hits DESC" ) $orderby = "hitsD";
        if ( $orderby == "rating DESC" ) $orderby = "ratingD";
        if ( $orderby == "country DESC" ) $orderby = "countryD";
        return $orderby;
    } 
} 

// updaterating()
// @param  $sel_id
// @return updates rating data in itemtable for a given item
function wfl_updaterating( $sel_id ) {
    global $xoopsDB;
    $query = "select rating FROM " . $xoopsDB -> prefix( 'wfbooks_votedata' ) . " WHERE lid=" . $sel_id;
    $voteresult = $xoopsDB -> query( $query );
    $votesDB = $xoopsDB -> getRowsNum( $voteresult );
    $totalrating = 0;
    while ( list( $rating ) = $xoopsDB -> fetchRow( $voteresult ) ) {
        $totalrating += $rating;
    } 
    $finalrating = $totalrating / $votesDB;
    $finalrating = number_format( $finalrating, 4 );
    $sql = sprintf( "UPDATE %s SET rating = %u, votes = %u WHERE lid = %u", $xoopsDB -> prefix( 'wfbooks_links' ), $finalrating, $votesDB, $sel_id );
    $xoopsDB -> query( $sql );
} 

// totalcategory()
// @param integer $pid
// @return
function wfl_totalcategory( $pid = 0 ) {
    global $xoopsDB;

    $sql = "SELECT cid FROM " . $xoopsDB -> prefix( 'wfbooks_cat' );
    if ( $pid > 0 ) {
        $sql .= " WHERE pid = 0";
    } 
    $result = $xoopsDB -> query( $sql );
    $catlisting = 0;
    while ( list( $cid ) = $xoopsDB -> fetchRow( $result ) ) {
        if ( checkgroups( $cid ) ) {
            $catlisting++;
        } 
    } 
    return $catlisting;
} 

// wfl_getTotalItems()
// @param integer $sel_id
// @param integer $get_child
// @param integer $return_sql
// @return
function wfl_getTotalItems( $sel_id = 0, $get_child = 0, $return_sql = 0 ) {
    global $xoopsDB, $mytree, $_check_array;

    if ( $sel_id > 0 ) {
        $sql = "SELECT DISTINCT a.lid, a.cid, a.published FROM " . $xoopsDB -> prefix( 'wfbooks_links' ) . " a LEFT JOIN "
         . $xoopsDB -> prefix( 'wfbooks_altcat' ) . " b "
         . "ON b.lid=a.lid "
         . "WHERE a.published > 0 AND a.published <= " . time()
         . " AND (a.expired = 0 OR a.expired > " . time() . ") AND offline = 0 "
         . " AND (b.cid=a.cid OR (a.cid=" . $sel_id . " OR b.cid=" . $sel_id . ")) ";
    } else {
        $sql = "SELECT lid, cid, published from " . $xoopsDB -> prefix( 'wfbooks_links' ) . " WHERE offline = 0 AND published > 0 AND published <= " . time() . " AND (expired = 0 OR expired > " . time() . ")";
    } 
    if ( $return_sql == 1 ) {
        return $sql;
    } 

    $count = 0;
    $published_date = 0;

    $arr = array();
    $result = $xoopsDB -> query( $sql );
    while ( list( $lid, $cid, $published ) = $xoopsDB -> fetchRow( $result ) ) {
        if ( true == checkgroups() ) {
            $count++;
            $published_date = ( $published > $published_date ) ? $published : $published_date;
        } 
    } 

    $child_count = 0;
    if ( $get_child == 1 ) {
        $arr = $mytree -> getAllChildId( $sel_id );
        $size = count( $arr );
        for( $i = 0; $i < count( $arr ); $i++ ) {
            $query2 = "SELECT DISTINCT a.lid, a.published, a.cid FROM " . $xoopsDB -> prefix( 'wfbooks_links' ) . " a LEFT JOIN "
             . $xoopsDB -> prefix( 'wfbooks_altcat' ) . " b "
             . "ON b.lid=a.lid "
             . "WHERE a.published > 0 AND a.published <= " . time()
             . " AND (a.expired = 0 OR a.expired > " . time() . ") AND offline = 0 "
             . " AND (b.cid=a.cid OR (a.cid=" . $arr[$i] . " OR b.cid=" . $arr[$i] . ")) ";

            $result2 = $xoopsDB -> query( $query2 );
            while ( list( $lid, $published ) = $xoopsDB -> fetchRow( $result2 ) ) {
                if ( $published == 0 ) {
                    continue;
                } 
                $published_date = ( $published > $published_date ) ? $published : $published_date;
                $child_count++;
            } 
        } 
    } 
    $info['count'] = $count + $child_count;
    $info['published'] = $published_date;
    return $info;
} 

function wfl_imageheader( $indeximage = '', $indexheading = '' ) {
    global $xoopsDB, $xoopsModuleConfig;

    if ( $indeximage == '' ) {
        $result = $xoopsDB -> query( "SELECT indeximage, indexheading FROM " . $xoopsDB -> prefix( 'wfbooks_indexpage' ) );
        list( $indeximage, $indexheading ) = $xoopsDB -> fetchrow( $result );
    } 

    $image = '';
    if ( !empty( $indeximage ) ) {
        $image = wfl_displayimage( $indeximage, "'index.php'", $xoopsModuleConfig['mainimagedir'], $indexheading );
    } 
    return $image;
} 

function wfl_displayimage( $image = '', $path = '', $imgsource = '', $alttext = '' ) {
    global $xoopsConfig, $xoopsUser, $xoopsModule;

    $showimage = '';
    // Check to see if link is given
    if ( $path ) {
        $showimage = "<a href=" . $path . ">";
    } 

    // checks to see if the file is valid else displays default blank image
    if ( !is_dir( XOOPS_ROOT_PATH . "/{$imgsource}/{$image}" ) && file_exists( XOOPS_ROOT_PATH . "/{$imgsource}/{$image}" ) ) {
        $showimage .= "<img src='" . XOOPS_URL . "/{$imgsource}/{$image}' border='0' alt='" . $alttext . "' /></a>";
    } else {
        if ( $xoopsUser && $xoopsUser -> isAdmin( $xoopsModule -> getVar( 'mid' ) ) ) {
            $showimage .= "<img src='" . XOOPS_URL . "/modules/" . $xoopsModule -> getVar( 'dirname' ) . "/images/brokenimg.gif' alt='" . _MD_WFB_ISADMINNOTICE . "' /></a>";
        } else {
            $showimage .= "<img src='" . XOOPS_URL . "/modules/" . $xoopsModule -> getVar( 'dirname' ) . "/images/blank.gif' alt='" . $alttext . "' /></a>";
        } 
    } 
    clearstatcache();
    return $showimage;
} 

function wfl_letters() {
    global $xoopsModule;

    $letterchoice = "<div>" . _MD_WFB_BROWSETOTOPIC . "</div>";
    $letterchoice .= "[  ";
    $alphabet = array ( "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z" );
    $num = count( $alphabet ) - 1;
    $counter = 0;
    while ( list( , $ltr ) = each( $alphabet ) ) {
        $letterchoice .= "<a href='" . XOOPS_URL . "/modules/" . $xoopsModule -> getVar( 'dirname' ) . "/viewcat.php?list=$ltr'>$ltr</a>";
        if ( $counter == round( $num / 2 ) )
            $letterchoice .= " ]<br />[ ";
        elseif ( $counter != $num )
            $letterchoice .= "&nbsp;|&nbsp;";
        $counter++;
    } 
    $letterchoice .= " ]";
    return $letterchoice;
} 

function wfl_isnewimage( $published ) {
    global $xoopsModule, $xoopsDB;

    $oneday = ( time() - ( 86400 * 1 ) );
    $threedays = ( time() - ( 86400 * 3 ) );
    $week = ( time() - ( 86400 * 7 ) );

    $path = "modules/" . $xoopsModule -> getVar( 'dirname' ) . "/images/icon";

    if ( $published > 0 && $published < $week ) {
        $indicator['image'] = "$path/linkload4.gif";
        $indicator['alttext'] = _MD_WFB_NEWLAST;
    } elseif ( $published >= $week && $published < $threedays ) {
        $indicator['image'] = "$path/linkload3.gif";
        $indicator['alttext'] = _MD_WFB_NEWTHIS;
    } elseif ( $published >= $threedays && $published < $oneday ) {
        $indicator['image'] = "$path/linkload2.gif";
        $indicator['alttext'] = _MD_WFB_THREE;
    } elseif ( $published >= $oneday ) {
        $indicator['image'] = "$path/linkload1.gif";
        $indicator['alttext'] = _MD_WFB_TODAY;
    } else {
        $indicator['image'] = "$path/linkload.gif";
        $indicator['alttext'] = _MD_WFB_NO_FILES;
    } 
    return $indicator;
} 

function wfl_strrrchr( $haystack, $needle ) {
    return substr( $haystack, 0, strpos( $haystack, $needle ) + 1 );
} 

function wfl_adminmenu( $header = '', $menu = '', $extra = '', $scount = 4 ) {
    global $xoopsConfig, $xoopsModule, $xoopsModuleConfig;

    $_named_url = xoops_getenv( 'PHP_SELF' );
    if ( $_named_url )
        $thispage = basename( $_named_url );

    $op = ( isset( $_GET['op'] ) ) ? $op = "?op=" . $_GET['op'] : '';

    echo "<h4 style='color: #2F5376;'>" . _AM_WFB_MODULE_NAME . "</h4>";
	echo "
		<table width='100%' cellspacing='0' cellpadding='0' border='0' class='outer'>\n
		<tr>\n
		<td style='font-size: 10px; text-align: left; color: #2F5376; padding: 2px 6px; line-height: 18px;'>\n
		<a href='../../system/admin.php?fct=modulesadmin&op=update&module=" . $xoopsModule -> getVar( 'dirname' ) . "'>" . _AM_WFB_BUPDATE . "</a> | \n
		<a href='../../system/admin.php?fct=preferences&op=showmod&mod=" . $xoopsModule -> getVar( 'mid' ) . "'>" . _AM_WFB_PREFS . "</a> | \n
		<a href='../admin/index.php'>" . _AM_WFB_BINDEX . "</a> | \n
		<a href='../admin/permissions.php'>" . _AM_WFB_BPERMISSIONS . "</a> | \n
		<a href='../admin/myblocksadmin.php'>" . _AM_WFB_BLOCKADMIN . "</a> | \n
		<a href='../index.php'>" . _AM_WFB_GOMODULE . "</a> | \n
		<a href='../admin/about.php'>" . _AM_WFB_ABOUT . "</a> \n
		</td>\n
		</tr>\n
		</table><br />\n
		";

    if ( empty( $menu ) ) {
         // You can change this part to suit your own module. Defining this here will save you form having to do this each time.
        $menu = array(
            _AM_WFB_INDEXPAGE => "indexpage.php",
            _AM_WFB_MCATEGORY => "category.php",
            _AM_WFB_MLINKS => "index.php?op=edit",
            _AM_WFB_MUPLOADS => "upload.php",
            _AM_WFB_MVOTEDATA => "votedata.php",
            _AM_WFB_MLISTPINGTIMES => "index.php?op=pingtime",
            _AM_WFB_MCOMMENTS => "../../system/admin.php?module=" . $xoopsModule -> getVar( 'mid' ) . "&status=0&limit=100&fct=comments&selsubmit=Go", 
            );
    } 

    if ( !is_array( $menu ) ) {
        echo "<table width='100%' cellpadding= '2' cellspacing= '1' class='outer'>\n";
        echo "<tr><td class ='even' align ='center'><b>" . _AM_WFB_NOMENUITEMS . "</b></td></tr></table><br />\n";
        return false;
    } 

    $oddnum = array( 1 => "1", 3 => "3", 5 => "5", 7 => "7", 9 => "9", 11 => "11", 13 => "13" ); 
    // number of rows per menu
    $menurows = count( $menu ) / $scount; 
    // total amount of rows to complete menu
    $menurow = ceil( $menurows ) * $scount; 
    // actual number of menuitems per row
    $rowcount = $menurow / ceil( $menurows );
    $count = 0;
    for ( $i = count( $menu ); $i < $menurow; $i++ ) {
        $tempArray = array( 1 => null );
        $menu = array_merge( $menu, $tempArray );
        $count++;
    } 

     // Sets up the width of each menu cell
    $width = 100 / $scount;
    $width = ceil( $width );

    $menucount = 0;
    $count = 0;

     // Menu table output
    echo "<table width='100%' cellpadding= '2' cellspacing= '1' class='outer'><tr>";

     // Check to see if $menu is and array
    if ( is_array( $menu ) ) {
        $classcounts = 0;
        $classcol[0] = "even";

        for ( $i = 1; $i < $menurow; $i++ ) {
            $classcounts++;
            if ( $classcounts >= $scount ) {
                if ( $classcol[$i-1] == 'odd' ) {
                    $classcol[$i] = ( $classcol[$i-1] == 'odd' && in_array( $classcounts, $oddnum ) ) ? "even" : "odd";
                } else {
                    $classcol[$i] = ( $classcol[$i-1] == 'even' && in_array( $classcounts, $oddnum ) ) ? "odd" : "even";
                } 
                $classcounts = 0;
            } else {
                $classcol[$i] = ( $classcol[$i-1] == 'even' ) ? "odd" : "even";
            } 
        } 
        unset( $classcounts );

        foreach ( $menu as $menutitle => $menulink ) {
            if ( $thispage . $op == $menulink ) {
                $classcol[$count] = "outer";
            } 
            echo "<td class='" . $classcol[$count] . "' style='text-align: center;' valign='middle' width='$width%'>";
            if ( is_string( $menulink ) ) {
                echo "<a href='" . $menulink . "'><small>" . $menutitle . "</small></a></td>";
            } else {
                echo "&nbsp;</td>";
            } 
            $menucount++;
            $count++;

             // Break menu cells to start a new row if $count > $scount
            if ( $menucount >= $scount ) {
                echo "</tr>";
                $menucount = 0;
            } 
        } 
        echo "</table><br />";
        unset( $count );
        unset( $menucount );
    } 
    // ###### Output warn messages for security ######
    if ( is_dir( XOOPS_ROOT_PATH . "/modules/" . $xoopsModule -> getVar( 'dirname' ) . "/update/" ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL1, XOOPS_ROOT_PATH . '/modules/' . $xoopsModule -> getVar( 'dirname' ) . '/update/' ) );
        echo '<br />';
    } 

    $_file = XOOPS_ROOT_PATH . "/modules/" . $xoopsModule -> getVar( 'dirname' ) . "/update.php";
    if ( file_exists( $_file ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL2, XOOPS_ROOT_PATH . '/modules/' . $xoopsModule -> getVar( 'dirname' ) . '/update.php' ) );
        echo '<br />';
    }

    $path1 = XOOPS_ROOT_PATH . '/' . $xoopsModuleConfig['mainimagedir'];
    if ( !is_dir( $path1 ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL3, $path1 ) );
        echo '<br />';
    }
    if ( !is_writable( $path1 ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL4, $path1 ) );
        echo '<br />';
    }

    $path1_t = XOOPS_ROOT_PATH . '/' . $xoopsModuleConfig['mainimagedir'] . '/thumbs';
    if ( !is_dir( $path1_t ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL3, $path1_t ) );
        echo '<br />';
    }
    if ( !is_writable( $path1_t ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL4, $path1_t ) );
        echo '<br />';
    }

    $path2 = XOOPS_ROOT_PATH . '/' . $xoopsModuleConfig['screenshots'];
    if ( !is_dir( $path2 ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL3, $path2 ) );
        echo '<br />';
    }
    if ( !is_writable( $path2 ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL4, $path2 ) );
        echo '<br />';
    }

    $path2_t = XOOPS_ROOT_PATH . '/' . $xoopsModuleConfig['screenshots'] . '/thumbs';
    if ( !is_dir( $path2_t ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL3, $path2_t ) );
        echo '<br />';
    }
    if ( !is_writable( $path2_t ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL4, $path2_t ) );
        echo '<br />';
    }

    $path3 = XOOPS_ROOT_PATH . '/' . $xoopsModuleConfig['catimage'];
    if ( !is_dir( $path3 ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL3, $path3 ) );
        echo '<br />';
    }
    if ( !is_writable( $path3 ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL4, $path3 ) );
        echo '<br />';
    }

    $path3_t = XOOPS_ROOT_PATH . '/' . $xoopsModuleConfig['catimage'] . '/thumbs';
    if ( !is_dir( $path3_t ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL3, $path3_t ) );
        echo '<br />';
    }
    if ( !is_writable( $path3_t ) ) {
        xoops_error( sprintf( _AM_WFB_WARNINSTALL4, $path3_t ) );
        echo '<br />';
    }

    echo "<h3 style='color: #2F5376;'>" . $header . "</h3>";
    if ( $extra ) {
        echo "<div>$extra</div>";
    } 
} 

function wfl_getDirSelectOption( $selected, $dirarray, $namearray ) {
    echo "<select size='1' name='workd' onchange='location.href=\"upload.php?rootpath=\"+this.options[this.selectedIndex].value'>";
    echo "<option value=''>--------------------------------------</option>";
    foreach( $namearray as $namearray => $workd ) {
        if ( $workd === $selected ) {
            $opt_selected = "selected";
        } else {
            $opt_selected = "";
        } 
        echo "<option value='" . htmlspecialchars( $namearray, ENT_QUOTES ) . "' $opt_selected>" . $workd . "</option>";
    } 
    echo "</select>";
} 

function wfl_uploading( $_FILES, $uploaddir = "uploads", $allowed_mimetypes = '', $redirecturl = "index.php", $num = 0, $redirect = 0, $usertype = 1 ) {
    global $_FILES, $xoopsConfig, $xoopsModuleConfig, $xoopsModule;

    $down = array();
    include_once XOOPS_ROOT_PATH . "/modules/" . $xoopsModule -> getVar( 'dirname' ) . "/class/uploader.php";
    if ( empty( $allowed_mimetypes ) ) {
        $allowed_mimetypes = wfl_retmime( $_FILES['userfile']['name'], $usertype );
    } 
    $upload_dir = XOOPS_ROOT_PATH . "/" . $uploaddir . "/";

    $maxfilesize = $xoopsModuleConfig['maxfilesize'];
    $maxfilewidth = $xoopsModuleConfig['maximgwidth'];
    $maxfileheight = $xoopsModuleConfig['maximgheight'];

    $uploader = new XoopsMediaUploader( $upload_dir, $allowed_mimetypes, $maxfilesize, $maxfilewidth, $maxfileheight );
    $uploader -> noAdminSizeCheck( 1 );
    if ( $uploader -> fetchMedia( $_POST['xoops_upload_file'][0] ) ) {
        if ( !$uploader -> upload() ) {
            $errors = $uploader -> getErrors();
            redirect_header( $redirecturl, 2, $errors );
        } else {
            if ( $redirect ) {
                redirect_header( $redirecturl, 1 , _AM_PDD_UPLOADFILE );
            } else {
                if ( is_file( $uploader -> savedDestination ) ) {
                    $down['url'] = XOOPS_URL . "/" . $uploaddir . "/" . strtolower( $uploader -> savedFileName );
                    $down['size'] = filesize( XOOPS_ROOT_PATH . "/" . $uploaddir . "/" . strtolower( $uploader -> savedFileName ) );
                } 
                return $down;
            } 
        } 
    } else {
        $errors = $uploader -> getErrors();
        redirect_header( $redirecturl, 1, $errors );
    } 
} 

function wfl_getforum( $forumid ) {
    global $xoopsDB, $xoopsConfig;

    echo "<select name='forumid'>";
    echo "<option value='0'>----------------------</option>";
    $result = $xoopsDB -> query( "SELECT forum_name, forum_id FROM " . $xoopsDB -> prefix( "bb_forums" ) . " ORDER BY forum_id" );
    while ( list( $forum_name, $forum_id ) = $xoopsDB -> fetchRow( $result ) ) {
        if ( $forum_id == $forumid ) {
            $opt_selected = "selected='selected'";
        } else {
            $opt_selected = "";
        } 
        echo "<option value='" . $forum_id . "' $opt_selected>" . $forum_name . "</option>";
    } 
    echo "</select></div>";
    return $forumid;
} 

function wfl_linklistheader( $heading ) {
    echo "
		<h4 style='font-weight: bold; color: #0A3760;'>" . $heading . "</h4>\n
		<table width='100%' cellspacing='1' class='outer' style='font-size: smaller;' summary>\n
		<tr>\n
		<th style='text-align: center;'>" . _AM_WFB_MINDEX_ID . "</th>\n
		<th style='text-align: left;'><b>" . _AM_WFB_MINDEX_TITLE . "</th>\n
		<th style='text-align: left;'><b>" . _AM_WFB_CATTITLE . "</th>\n
		<th style='text-align: center;'>" . _AM_WFB_MINDEX_POSTER . "</th>\n
		<th style='text-align: center;'>" . _AM_WFB_MINDEX_PUBLISH . "</th>\n
		<th style='text-align: center;'>" . _AM_WFB_MINDEX_EXPIRE . "</th>\n
		<th style='text-align: center;'>" . _AM_WFB_MINDEX_ONLINE . "</th>\n
		<th style='text-align: center;'>" . _AM_WFB_MINDEX_ACTION . "</th>\n
		</tr>\n
		";
} 

function wfl_linklistbody( $published ) {
    global $wfmyts, $imagearray, $xoopsModuleConfig, $xoopsModule;

    $lid = $published['lid'];
    $cid = $published['cid'];
    
    $title = "<a href='../singlelink.php?cid=" . $published['cid'] . "&amp;lid=" . $published['lid'] . "'>" . $wfmyts -> htmlSpecialCharsStrip( trim( $published['title'] ) ) . "</a>";;
    $maintitle = urlencode( $wfmyts -> htmlSpecialChars( trim( $published['title'] ) ) );
    $cattitle = wfl_cattitle($published['cid']);
    $submitter = xoops_getLinkedUnameFromId( $published['submitter'] );
    $hwhoisurl = str_replace( 'http://', '', $published['url']);
    $submitted = formatTimestamp( $published['date'], $xoopsModuleConfig['dateformat'] );
    $publish = ( $published['published'] > 0 ) ? formatTimestamp( $published['published'], $xoopsModuleConfig['dateformat'] ): 'Not Published';
    $expires = $published['expired'] ? formatTimestamp( $published['expired'], $xoopsModuleConfig['dateformat'] ): _AM_WFB_MINDEX_NOTSET;
    if ( ( $published['published'] && $published['published'] < time() ) && $published['offline'] == 0 ) {
        $published_status = $imagearray['online'];
    } else {
        $published_status = ( $published['published'] == 0 ) ? "<a href='newlinks.php'>" . $imagearray['offline'] . "</a>" : $imagearray['offline'];
    } 
    $icon = "<a href='index.php?op=edit&amp;lid=" . $lid . "' title='" . _AM_WFB_ICO_EDIT . "'>" . $imagearray['editimg'] . "</a>&nbsp;";
    $icon .= "<a href='index.php?op=delete&amp;lid=" . $lid . "' title='" . _AM_WFB_ICO_DELETE . "'>" . $imagearray['deleteimg'] . "</a>&nbsp;";
    $icon .= "<a href='altcat.php?op=main&amp;cid=" . $cid . "&amp;lid=" . $lid . "&amp;title=" . $published['title'] . "' title='" . _AM_WFB_ALTCAT_CREATEF . "'>" . $imagearray['altcat'] . "</a>&nbsp;";
    $icon .= '<a href="http://whois.domaintools.com/' . $hwhoisurl . '" target="_blank"><img src="' . XOOPS_URL . '/modules/' . $xoopsModule -> getVar( 'dirname' ) . '/images/icon/domaintools.png" alt="WHOIS" title="WHOIS" align="absmiddle"/></a>';

    echo "
		<tr style='text-align: center;'>\n
		<td class='head'>" . $lid . "</td>\n
		<td class='even' style='text-align: left;'>" . $title . "</td>\n
		<td class='even' style='text-align: left;'>" . $cattitle . "</td>\n
		<td class='even'>" . $submitter . "</td>\n
		<td class='even'>" . $publish . "</td>\n
		<td class='even'>" . $expires . "</td>\n
		<td class='even' width='4%'>" . $published_status . "</td>\n
		<td class='even' style='text-align: center; width: 7%; white-space: nowrap;'>$icon</td>\n
		</tr>\n
		";
    unset( $published );
} 

function wfl_cattitle($catt) {
  global $xoopsDB;
  $sql = "SELECT title FROM " . $xoopsDB -> prefix( 'wfbooks_cat' ) . " WHERE cid=" . $catt;
         $result = $xoopsDB -> query( $sql );
         $result = $xoopsDB -> fetchArray( $result );
         return $result['title'];
}

function wfl_linklistfooter() {
    echo "<tr style='text-align: center;'>\n<td class='head' colspan='7'>" . _AM_WFB_MINDEX_NOLINKSFOUND . "</td>\n</tr>\n";
} 

function wfl_linklistpagenav( $pubrowamount, $start, $art = "art", $_this = '' ) {
    global $xoopsModuleConfig;
    echo "</table>\n";
    if ( ( $pubrowamount < $xoopsModuleConfig['admin_perpage'] ) ) {
        return false;
    } 
    // Display Page Nav if published is > total display pages amount.
    include_once XOOPS_ROOT_PATH . '/class/pagenav.php';
    $page = ( $pubrowamount > $xoopsModuleConfig['admin_perpage'] ) ? _AM_WFB_MINDEX_PAGE : '';
    $pagenav = new XoopsPageNav( $pubrowamount, $xoopsModuleConfig['admin_perpage'], $start, 'st' . $art, $_this );
    echo '<div align="right" style="padding: 8px;">' . $page . '' . $pagenav -> renderNav() . '</div>';
} 

function wfl_linklistpagenavleft( $pubrowamount, $start, $art = "art", $_this = '' ) {
    global $xoopsModuleConfig;
//    echo "</table>\n";
    if ( ( $pubrowamount < $xoopsModuleConfig['admin_perpage'] ) ) {
        return false;
    }
    // Display Page Nav if published is > total display pages amount.
    include_once XOOPS_ROOT_PATH . '/class/pagenav.php';
    $page = ( $pubrowamount > $xoopsModuleConfig['admin_perpage'] ) ? _AM_WFB_MINDEX_PAGE : '';
    $pagenav = new XoopsPageNav( $pubrowamount, $xoopsModuleConfig['admin_perpage'], $start, 'st' . $art, $_this );
    echo '<div align="left" style="padding: 8px;">' . $page . '' . $pagenav -> renderNav() . '</div>';
}

 // Retreive an editor according to the module's option "form_options"
function &wfl_getWysiwygForm($caption, $name, $value = "", $width = '100%', $height = '400px', $supplemental='') {
        global $xoopsModuleConfig, $xoopsUser, $xoopsModule;

	$editor = false;
	$x22=false;
	$xv=str_replace('XOOPS ','',XOOPS_VERSION);
	if(substr($xv,2,1)=='2') {
		$x22=true;
	}
	$editor_configs=array();
	$editor_configs["name"] =$name;
	$editor_configs["value"] = $value;
	$editor_configs["rows"] = 35;
	$editor_configs["cols"] = 60;
	$editor_configs["width"] = "100%";
	$editor_configs["height"] = "400px";

	$isadmin = ( ( is_object( $xoopsUser ) && !empty( $xoopsUser ) ) && $xoopsUser -> isAdmin( $xoopsModule -> mid() ) ) ? true : false;
        if ( $isadmin == true ) {
          $formuser = $xoopsModuleConfig['form_options'];
        } else {
          $formuser = $xoopsModuleConfig['form_optionsuser'];
        }

	switch($formuser) {
	case "fck":
		if (!$xoops22) {
			if ( is_readable(XOOPS_ROOT_PATH . "/class/xoopseditor/fckeditor/formfckeditor.php"))	{
				include_once(XOOPS_ROOT_PATH . "/class/xoopseditor/fckeditor/formfckeditor.php");
				$editor = new XoopsFormFckeditor($editor_configs,true);
			} else {
				if ($dhtml) {
					$editor = new XoopsFormDhtmlTextArea($caption, $name, $value, 20, 60);
				} else {
					$editor = new XoopsFormTextArea($caption, $name, $value, 7, 60);
				}
			}
		} else {
			$editor = new XoopsFormEditor($caption, "fckeditor", $editor_configs);
		}
		break;

	case "htmlarea":
		if(!$x22) {
			if ( is_readable(XOOPS_ROOT_PATH . "/class/htmlarea/formhtmlarea.php"))	{
				include_once(XOOPS_ROOT_PATH . "/class/htmlarea/formhtmlarea.php");
				$editor = new XoopsFormHtmlarea($caption, $name, $value);
			}
		} else {
			$editor = new XoopsFormEditor($caption, "htmlarea", $editor_configs);
		}
		break;

	case "dhtml":
		if(!$x22) {
			$editor = new XoopsFormDhtmlTextArea($caption, $name, $value, 10, 50, $supplemental);
		} else {
			$editor = new XoopsFormEditor($caption, "dhtmltextarea", $editor_configs);
		}
		break;

	case "textarea":
		$editor = new XoopsFormTextArea($caption, $name, $value);
		break;

	case "koivi":
		if(!$x22) {
			if ( is_readable(XOOPS_ROOT_PATH . "/class/xoopseditor/koivi/formwysiwygtextarea.php"))	{
				include_once(XOOPS_ROOT_PATH . "/class/xoopseditor/koivi/formwysiwygtextarea.php");
				$editor = new XoopsFormWysiwygTextArea($caption, $name, $value, '100%', '400px');
			} else {
				if ($dhtml) {
					$editor = new XoopsFormDhtmlTextArea($caption, $name, $value, 20, 60);
				} else {
					$editor = new XoopsFormTextArea($caption, $name, $value, 7, 60);
				}
			}
		} else {
			$editor = new XoopsFormEditor($caption, "koivi", $editor_configs);
		}
		break;

	case "tinyeditor":
               if(!$x22) {
			if ( is_readable(XOOPS_ROOT_PATH . "/class/xoopseditor/tinyeditor/formtinyeditortextarea.php"))	{
				include_once(XOOPS_ROOT_PATH . "/class/xoopseditor/tinyeditor/formtinyeditortextarea.php");
				$editor = new XoopsFormTinyeditorTextArea(array('caption'=>$caption, 'name'=>$name, 'value'=>$value, 'width'=>'100%', 'height'=>'400px'));
			} else {
				if ($dhtml) {
					$editor = new XoopsFormDhtmlTextArea($caption, $name, $value, 50, 60);
				} else {
					$editor = new XoopsFormTextArea($caption, $name, $value, 7, 60);
				}
			}
		} else {
			$editor = new XoopsFormEditor($caption, "tinyeditor", $editor_configs);
		}
		break;

	case "dhtmlext":
               if(!$x22) {
			if ( is_readable(XOOPS_ROOT_PATH . "/class/xoopseditor/dhtmlext/dhtmlext.php"))	{
				include_once(XOOPS_ROOT_PATH . "/class/xoopseditor/dhtmlext/dhtmlext.php");
				$editor = new XoopsFormDhtmlTextAreaExtended($caption, $name, $value, 10, 50, $supplemental);
			} else {
				if ($dhtml) {
					$editor = new XoopsFormDhtmlTextArea($caption, $name, $value, 50, 60);
				} else {
					$editor = new XoopsFormTextArea($caption, $name, $value, 7, 60);
				}
			}
		} else {
			$editor = new XoopsFormEditor($caption, "dhtmlext", $editor_configs);
		}
		break;

	case 'tinymce' :
             if (!$x22) {
                       if ( is_readable(XOOPS_ROOT_PATH . "/class/xoopseditor/tinymce/formtinymce.php")) {
                          include_once(XOOPS_ROOT_PATH . "/class/xoopseditor/tinymce/formtinymce.php");
                          $editor = new XoopsFormTinymce(array('caption'=>$caption, 'name'=>$name, 'value'=>$value, 'width'=>'100%', 'height'=>'400px'));
                       } else {
                          if ($dhtml) {
                              $editor = new XoopsFormDhtmlTextArea($caption, $name, $value, 20, 60);
                          } else {
                              $editor = new XoopsFormTextArea($caption, $name, $value, 7, 60);
                          }
                       }
                       } else {
                           $editor = new XoopsFormEditor($caption, "tinymce", $editor_configs);
                       }
                       break;
	               }
	return $editor;
}

function wfl_countryname($countryn) {
     $country_array = array ( ""   => "Unknown",
                              "-"  => "Unknown",
                              "AD" => "Andorra",
                              "AE" => "United Arab Emirates",
                              "AF" => "Afghanistan",
                              "AG" => "Antigua And Barbuda",
                              "AI" => "Anguilla",
                              "AL" => "Albania",
                              "AM" => "Armenia",
                              "AN" => "Netherlands Antilles",
                              "AO" => "Angola",
                              "AQ" => "Antarctica",
                              "AR" => "Argentina",
                              "AS" => "American Samoa",
                              "AT" => "Austria",
                              "AU" => "Australia",
                              "AW" => "Aruba",
                              "AZ" => "Azerbaijan",
                              "BA" => "Bosnia And Herzegovina",
                              "BB" => "Barbados",
                              "BD" => "Bangladesh",
                              "BE" => "Belgium",
                              "BG" => "Bulgaria",
                              "BH" => "Bahrain",
                              "BI" => "Burundi",
                              "BJ" => "Benin",
                              "BM" => "Bermuda",
                              "BN" => "Brunei Darussalam",
                              "BO" => "Bolivia",
                              "BR" => "Brazil",
                              "BS" => "Bahamas",
                              "BT" => "Bhutan",
                              "BW" => "Botswana",
                              "BY" => "Belarus",
                              "BZ" => "Belize",
                              "CA" => "Canada",
                              "CD" => "The Democratic Republic Of The Congo",
                              "CF" => "Central African Republic",
                              "CG" => "Congo",
                              "CH" => "Switzerland",
                              "CI" => "Cote D'Ivoire",
                              "CK" => "Cook Islands",
                              "CL" => "Chile",
                              "CM" => "Cameroon",
                              "CN" => "China",
                              "CO" => "Colombia",
                              "CR" => "Costa Rica",
                              "CS" => "Serbia And Montenegro",
                              "CU" => "Cuba",
                              "CV" => "Cape Verde",
                              "CY" => "Cyprus",
                              "CZ" => "Czech Republic",
                              "DE" => "Germany",
                              "DJ" => "Djibouti",
                              "DK" => "Denmark",
                              "DM" => "Dominica",
                              "DO" => "Dominican Republic",
                              "DZ" => "Algeria",
                              "EC" => "Ecuador",
                              "EE" => "Estonia",
                              "EG" => "Egypt",
                              "ER" => "Eritrea",
                              "ES" => "Spain",
                              "ET" => "Ethiopia",
                              "EU" => "Europe",
                              "FI" => "Finland",
                              "FJ" => "Fiji",
                              "FK" => "Falkland Islands (Malvinas)",
                              "FM" => "Federated States Of Micronesia",
                              "FO" => "Faroe Islands",
                              "FR" => "France",
                              "GA" => "Gabon",
                              "GB" => "United Kingdom",
                              "GD" => "Grenada",
                              "GE" => "Georgia",
                              "GF" => "French Guiana",
                              "GH" => "Ghana",
                              "GI" => "Gibraltar",
                              "GL" => "Greenland",
                              "GM" => "Gambia",
                              "GN" => "Guinea",
                              "GP" => "Guadeloupe",
                              "GQ" => "Equatorial Guinea",
                              "GR" => "Greece",
                              "GT" => "Guatemala",
                              "GU" => "Guam",
                              "GW" => "Guinea-Bissau",
                              "GY" => "Guyana",
                              "HK" => "Hong Kong",
                              "HN" => "Honduras",
                              "HR" => "Croatia",
                              "HT" => "Haiti",
                              "HU" => "Hungary",
                              "ID" => "Indonesia",
                              "IE" => "Ireland",
                              "IL" => "Israel",
                              "IN" => "India",
                              "IO" => "British Indian Ocean Territory",
                              "IQ" => "Iraq",
                              "IR" => "Islamic Republic Of Iran",
                              "IS" => "Iceland",
                              "IT" => "Italy",
                              "JM" => "Jamaica",
                              "JO" => "Jordan",
                              "JP" => "Japan",
                              "KE" => "Kenya",
                              "KG" => "Kyrgyzstan",
                              "KH" => "Cambodia",
                              "KI" => "Kiribati",
                              "KM" => "Comoros",
                              "KN" => "Saint Kitts And Nevis",
                              "KR" => "Republic Of Korea",
                              "KW" => "Kuwait",
                              "KY" => "Cayman Islands",
                              "KZ" => "Kazakhstan",
                              "LA" => "Lao People'S Democratic Republic",
                              "LB" => "Lebanon",
                              "LC" => "Saint Lucia",
                              "LI" => "Liechtenstein",
                              "LK" => "Sri Lanka",
                              "LR" => "Liberia",
                              "LS" => "Lesotho",
                              "LT" => "Lithuania",
                              "LU" => "Luxembourg",
                              "LV" => "Latvia",
                              "LY" => "Libyan Arab Jamahiriya",
                              "MA" => "Morocco",
                              "MC" => "Monaco",
                              "MD" => "Republic Of Moldova",
                              "MG" => "Madagascar",
                              "MH" => "Marshall Islands",
                              "MK" => "The Former Yugoslav Republic Of Macedonia",
                              "ML" => "Mali",
                              "MM" => "Myanmar",
                              "MN" => "Mongolia",
                              "MO" => "Macao",
                              "MP" => "Northern Mariana Islands",
                              "MQ" => "Martinique",
                              "MR" => "Mauritania",
                              "MT" => "Malta",
                              "MU" => "Mauritius",
                              "MV" => "Maldives",
                              "MW" => "Malawi",
                              "MX" => "Mexico",
                              "MY" => "Malaysia",
                              "MZ" => "Mozambique",
                              "NA" => "Namibia",
                              "NC" => "New Caledonia",
                              "NE" => "Niger",
                              "NF" => "Norfolk Island",
                              "NG" => "Nigeria",
                              "NI" => "Nicaragua",
                              "NL" => "Netherlands",
                              "NO" => "Norway",
                              "NP" => "Nepal",
                              "NR" => "Nauru",
                              "NU" => "Niue",
                              "NZ" => "New Zealand",
                              "OM" => "Oman",
                              "PA" => "Panama",
                              "PE" => "Peru",
                              "PF" => "French Polynesia",
                              "PG" => "Papua New Guinea",
                              "PH" => "Philippines",
                              "PK" => "Pakistan",
                              "PL" => "Poland",
                              "PR" => "Puerto Rico",
                              "PS" => "Palestinian Territory, Occupied",
                              "PT" => "Portugal",
                              "PW" => "Palau",
                              "PY" => "Paraguay",
                              "QA" => "Qatar",
                              "RE" => "Reunion",
                              "RO" => "Romania",
                              "RU" => "Russian Federation",
                              "RW" => "Rwanda",
                              "SA" => "Saudi Arabia",
                              "SB" => "Solomon Islands",
                              "SC" => "Seychelles",
                              "SD" => "Sudan",
                              "SE" => "Sweden",
                              "SG" => "Singapore",
                              "SI" => "Slovenia",
                              "SK" => "Slovakia",
                              "SL" => "Sierra Leone",
                              "SM" => "San Marino",
                              "SN" => "Senegal",
                              "SO" => "Somalia",
                              "SR" => "Suriname",
                              "ST" => "Sao Tome And Principe",
                              "SV" => "El Salvador",
                              "SY" => "Syrian Arab Republic",
                              "SZ" => "Swaziland",
                              "TD" => "Chad",
                              "TF" => "French Southern Territories",
                              "TG" => "Togo",
                              "TH" => "Thailand",
                              "TJ" => "Tajikistan",
                              "TK" => "Tokelau",
                              "TL" => "Timor-Leste",
                              "TM" => "Turkmenistan",
                              "TN" => "Tunisia",
                              "TO" => "Tonga",
                              "TR" => "Turkey",
                              "TT" => "Trinidad And Tobago",
                              "TV" => "Tuvalu",
                              "TW" => "Taiwan",
                              "TZ" => "United Republic Of Tanzania",
                              "UA" => "Ukraine",
                              "UG" => "Uganda",
                              "UK" => "United Kingdom",
                              "US" => "United States",
                              "UY" => "Uruguay",
                              "UZ" => "Uzbekistan",
                              "VA" => "Holy See (Vatican City State)",
                              "VC" => "Saint Vincent And The Grenadines",
                              "VE" => "Venezuela",
                              "VG" => "Virgin Islands, British",
                              "VI" => "Virgin Islands, U.S.",
                              "VN" => "Viet Nam",
                              "VU" => "Vanuatu",
                              "WS" => "Samoa",
                              "YE" => "Yemen",
                              "YT" => "Mayotte",
                              "ZA" => "South Africa",
                              "ZM" => "Zambia",
                              "ZW" => "Zimbabwe"
                              );
     return $country_array[$countryn];
}

function wfl_html2text($document) {
         $search = array (
         "'<script[^>]*?>.*?</script>'si",  // Strip out javascript
         "'<img.*?/>'si",                   // Strip out img tags
         "'<[\/\!]*?[^<>]*?>'si",           // Strip out HTML tags
         "'([\r\n])[\s]+'",                 // Strip out white space
         "'&(quot|#34);'i",                 // Replace HTML entities
	 "'&(amp|#38);'i",
	 "'&(lt|#60);'i",
         "'&(gt|#62);'i",
         "'&(nbsp|#160);'i",
         "'&(iexcl|#161);'i",
         "'&(cent|#162);'i",
         "'&(pound|#163);'i",
         "'&(copy|#169);'i",
         //"'&#(\d+);'e"                    // evaluate as php
	);

	 $replace = array (
         "",
         "",
         "",
         "\\1",
         "\"",
         "&",
         "<",
         ">",
         " ",
         chr(161),
         chr(162),
         chr(163),
         chr(169),
         //"chr(\\1)"
	);

	$text = preg_replace($search, $replace, $document);
        return $text;
}

//    Start functions for Google PageRank
//    Source: http://www.sws-tech.com/scripts/googlepagerank.php
//    This code is released under the public domain
function zeroFill($a, $b) {
    $z = hexdec(80000000);
    //echo $z;
        if ($z & $a) {
            $a = ($a>>1);
            $a &= (~$z);
            $a |= 0x40000000;
            $a = ($a>>($b-1));
        } else {
            $a = ($a>>$b);
        }
        return $a;
}

function mix($a,$b,$c) {
  $a -= $b; $a -= $c; $a ^= (zeroFill($c,13));
  $b -= $c; $b -= $a; $b ^= ($a<<8);
  $c -= $a; $c -= $b; $c ^= (zeroFill($b,13));
  $a -= $b; $a -= $c; $a ^= (zeroFill($c,12));
  $b -= $c; $b -= $a; $b ^= ($a<<16);
  $c -= $a; $c -= $b; $c ^= (zeroFill($b,5));
  $a -= $b; $a -= $c; $a ^= (zeroFill($c,3));
  $b -= $c; $b -= $a; $b ^= ($a<<10);
  $c -= $a; $c -= $b; $c ^= (zeroFill($b,15));
  return array($a,$b,$c);
}

function GoogleCH($url, $length=null, $init=0xE6359A60) {
    if(is_null($length)) {
        $length = sizeof($url);
    }
    $a = $b = 0x9E3779B9;
    $c = $init;
    $k = 0;
    $len = $length;
    while($len >= 12) {
        $a += ($url[$k+0] +($url[$k+1]<<8) +($url[$k+2]<<16) +($url[$k+3]<<24));
        $b += ($url[$k+4] +($url[$k+5]<<8) +($url[$k+6]<<16) +($url[$k+7]<<24));
        $c += ($url[$k+8] +($url[$k+9]<<8) +($url[$k+10]<<16)+($url[$k+11]<<24));
        $mix = mix($a,$b,$c);
        $a = $mix[0]; $b = $mix[1]; $c = $mix[2];
        $k += 12;
        $len -= 12;
    }
    $c += $length;
    switch($len)              /* all the case statements fall through */
    {
        case 11: $c+=($url[$k+10]<<24);
        case 10: $c+=($url[$k+9]<<16);
        case 9 : $c+=($url[$k+8]<<8);
          /* the first byte of c is reserved for the length */
        case 8 : $b+=($url[$k+7]<<24);
        case 7 : $b+=($url[$k+6]<<16);
        case 6 : $b+=($url[$k+5]<<8);
        case 5 : $b+=($url[$k+4]);
        case 4 : $a+=($url[$k+3]<<24);
        case 3 : $a+=($url[$k+2]<<16);
        case 2 : $a+=($url[$k+1]<<8);
        case 1 : $a+=($url[$k+0]);
         /* case 0: nothing left to add */
    }
    $mix = mix($a,$b,$c);
    //echo $mix[0];
    /*-------------------------------------------- report the result */
    return $mix[2];
}
//converts a string into an array of integers containing the numeric value of the char
function strord($string) {
    for($i=0;$i<strlen($string);$i++) {
        $result[$i] = ord($string{$i});
    }
    return $result;
}
//  End functions for Google PageRank

// Check if Tag module is installed
function wfl_tag_module_included() {
	static 	$wfl_tag_module_included;
	if (!isset($wfl_tag_module_included)) {
		$modules_handler = xoops_gethandler('module');
		$tag_mod = $modules_handler -> getByDirName('tag');
		if (!$tag_mod) {
			$tag_mod = false;
		} else {
			$wfl_tag_module_included = $tag_mod -> getVar('isactive') == 1;
		}
	}
	return $wfl_tag_module_included;
}
// Add item_tag to Tag-module
function wfl_tagupdate($lid, $item_tag) {
         global $xoopsModule;
         if (wfl_tag_module_included())
         {
            include_once XOOPS_ROOT_PATH . "/modules/tag/include/formtag.php";
            $tag_handler = xoops_getmodulehandler('tag', 'tag');
            $tag_handler -> updateByItem($item_tag, $lid, $xoopsModule -> getVar( 'dirname' ), 0);
         }
}

function wfl_updateCounter($lid) {
         global $xoopsDB;
	 $sql = "UPDATE " . $xoopsDB -> prefix( 'wfbooks_links' ) . " SET hits=hits+1 WHERE lid=" . $lid;
         $result = $xoopsDB -> queryF( $sql );
}

?>