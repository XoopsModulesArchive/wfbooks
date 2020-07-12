<?php
/**
 * $Id: broken.php v 1.00 21 June 2005 John N Exp $
 * Module: WF-Links
 * Version: v1.0.3
 * Release Date: 21 June 2005
 * Developer: John N
 * Team: WF-Projects
 * Licence: GNU
 */

include 'header.php';

$op = wfl_cleanRequestVars( $_REQUEST, 'op', '' );
$lid = wfl_cleanRequestVars( $_REQUEST, 'lid', 0 );
$lid = intval($lid);
$buttonn = _MD_WFB_SUBMITBROKEN;
$buttonn = strtolower($buttonn);

switch ( strtolower($op) )
{
    case $buttonn:
        global $xoopsUser;

        $sender = ( is_object( $xoopsUser ) && !empty( $xoopsUser ) ) ? $xoopsUser -> getVar( 'uid' ) : 0;
        $ip = getenv( "REMOTE_ADDR" );
        $title = wfl_cleanRequestVars( $_REQUEST, 'title', '' );
        $title = $wfmyts -> addslashes( $title );
        $time = time();

// Check if REG user is trying to report twice.
        $result = $xoopsDB -> query( 'SELECT COUNT(*) FROM ' . $xoopsDB -> prefix( 'wfbooks_broken' ) . ' WHERE lid=' . $lid );
        list ( $count ) = $xoopsDB -> fetchRow( $result );
        if ( $count > 0 ) {
            redirect_header( 'index.php', 2, _MD_WFB_ALREADYREPORTED );
            exit();
        } else {
            $reportid = 0;
            $sql = sprintf( "INSERT INTO %s (reportid, lid, sender, ip, date, confirmed, acknowledged, title ) VALUES ( %u, %u, %u, %s, %u, %u, %u, %s)", $xoopsDB -> prefix( 'wfbooks_broken' ), $reportid, $lid, $sender, $xoopsDB -> quoteString( $ip ), $time, 0, 0, $xoopsDB -> quoteString( $title ) );
            if ( ! $result = $xoopsDB -> query( $sql ) ) {
                $error[] = _MD_WFB_ERROR;
            } 
            $newid = $xoopsDB -> getInsertId();

// Send notifications
            $tags = array();
            $tags['BROKENREPORTS_URL'] = XOOPS_URL . '/modules/' . $xoopsModule -> getVar( 'dirname' ) . '/admin/index.php?op=listBrokenlinks';
            $notification_handler = &xoops_gethandler( 'notification' );
            $notification_handler -> triggerEvent( 'global', 0, 'link_broken', $tags );

// Send email to the owner of the linkload stating that it is broken
            $sql = "SELECT * FROM " . $xoopsDB -> prefix( 'wfbooks_links' ) . " WHERE lid=" . $lid . " AND published > 0 AND published <= " . time() . " AND (expired = 0 OR expired > " . time() . ")";
            $link_arr = $xoopsDB -> fetchArray( $xoopsDB -> query( $sql ) );
            unset( $sql );

            $member_handler = &xoops_gethandler( 'member' );
            $submit_user = &$member_handler -> getUser( $link_arr['submitter'] );
            if ( is_object( $submit_user ) && !empty( $submit_user ) ) {
                $subdate = formatTimestamp( $link_arr['date'], $xoopsModuleConfig['dateformat'] );
                $cid = $link_arr['cid'];
                $title = $wfmyts -> htmlSpecialCharsStrip( $link_arr['title'] );
                $subject = _MD_WFB_BROKENREPORTED;

                $xoopsMailer = &getMailer();
                $xoopsMailer -> useMail();
                $template_dir = XOOPS_ROOT_PATH . "/modules/" . $xoopsModule -> getVar( 'dirname' ) . "/language/" . $xoopsConfig['language'] . "/mail_template";

                $xoopsMailer -> setTemplateDir( $template_dir );
                $xoopsMailer -> setTemplate( 'linkbroken_notify.tpl' );
                $xoopsMailer -> setToEmails( $submit_user -> getVar( 'email' ) );
                $xoopsMailer -> setFromEmail( $xoopsConfig['adminmail'] );
                $xoopsMailer -> setFromName( $xoopsConfig['sitename'] );
                $xoopsMailer -> assign( "X_UNAME", $submit_user -> getVar( 'uname' ) );
                $xoopsMailer -> assign( "SITENAME", $xoopsConfig['sitename'] );
                $xoopsMailer -> assign( "X_ADMINMAIL", $xoopsConfig['adminmail'] );
                $xoopsMailer -> assign( 'X_SITEURL', XOOPS_URL . '/' );
                $xoopsMailer -> assign( "X_TITLE", $title );
                $xoopsMailer -> assign( "X_SUB_DATE", $subdate );
                $xoopsMailer -> assign( 'X_LINKLOAD', XOOPS_URL . '/modules/' . $xoopsModule -> getVar( 'dirname' ) . '/singlelink.php?cid=' . $cid . '&amp;lid=' . $lid );
                $xoopsMailer -> setSubject( $subject );
                $message = ( $xoopsMailer -> send() ) ? _MD_WFB_BROKENREPORTED : _MD_WFB_ERRORSENDEMAIL;
            } else {
                $message = _MD_WFB_ERRORSENDEMAIL;
            } 
            redirect_header( 'index.php', 2, $message );
        } 
        break;

    default:

        $xoopsOption['template_main'] = 'wfbooks_brokenlink.html';
        include XOOPS_ROOT_PATH . '/header.php';

        $catarray['imageheader'] = wfl_imageheader();
        $catarray['letters'] = wfl_letters();
        $catarray['toolbar'] = wfl_toolbar();
        $xoopsTpl -> assign( 'catarray', $catarray );

        $sql = 'SELECT * FROM ' . $xoopsDB -> prefix( 'wfbooks_links' ) . ' WHERE lid =' . $lid;
        $link_arr = $xoopsDB -> fetchArray( $xoopsDB -> query( $sql ) );
        unset( $sql );

        $sql = 'SELECT * FROM ' . $xoopsDB -> prefix( 'wfbooks_broken' ) . ' WHERE lid =' . $lid;
        $broke_arr = $xoopsDB -> fetchArray( $xoopsDB -> query( $sql ) );

        if ( is_array( $broke_arr ) ) {
            $broken['title'] = $wfmyts -> htmlSpecialCharsStrip( $link_arr['title'] );
            $broken['id'] = $broke_arr['reportid'];
            $broken['reporter'] = xoops_getLinkedUnameFromId( $broke_arr['sender']  );
            $broken['date'] = formatTimestamp( $broke_arr['date'], $xoopsModuleConfig['dateformat'] );
            $broken['acknowledged'] = ( $broke_arr['acknowledged'] == 1 ) ? _YES : _NO ;
            $broken['confirmed'] = ( $broke_arr['confirmed'] == 1 ) ? _YES : _NO ;
            $xoopsTpl -> assign( 'broken', $broken );
            $xoopsTpl -> assign( 'brokenreport', true );
        } else {
            if ( !is_array( $link_arr ) || empty($link_arr) ) {
                redirect_header( 'index.php', 0 , _MD_WFB_THISFILEDOESNOTEXIST );
                exit();
            } 

// file info
            $link['title'] = $wfmyts -> htmlSpecialCharsStrip( $link_arr['title'] );
            $time = ( $link_arr['published'] > 0 ) ? $link_arr['published'] : $link_arr['updated'];
            $link['updated'] = formatTimestamp( $time, $xoopsModuleConfig['dateformat'] );
            $is_updated = ( $link_arr['updated'] != 0 ) ? _MD_WFB_UPDATEDON : _MD_WFB_SUBMITDATE;
            $link['publisher'] = xoops_getLinkedUnameFromId($link_arr['submitter']  );

            $xoopsTpl -> assign( 'link_id', $lid );
            $xoopsTpl -> assign( 'lang_subdate' , $is_updated );
            $xoopsTpl -> assign( 'link', $link );
        } 
		$xoopsTpl -> assign( 'module_dir', $xoopsModule -> getVar( 'dirname' ) );
        include XOOPS_ROOT_PATH . '/footer.php';
        break;
} // switch

?>