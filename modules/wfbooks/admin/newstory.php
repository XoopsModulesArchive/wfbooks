<?php
/**
 * $Id: newsstory.php v 1.00 21 June 2005 John N Exp $
 * Module: WF-Links
 * Version: v1.0.3
 * Release Date: 21 June 2005
 * Developer: John N
 * Team: WF-Projects
 * Licence: GNU
 */

include_once XOOPS_ROOT_PATH . '/modules/news/class/class.newsstory.php';

$story = new NewsStory();
$story -> setUid( $xoopsUser -> uid() );
$story -> setPublished( time() );
$story -> setExpired( 0 );
$story -> setType( "admin" );
$story -> setHostname( getenv( "REMOTE_ADDR" ) );
$story -> setApproved( 1 );
$topicid = intval($_REQUEST["newstopicid"]);
$story -> setTopicId( $topicid );
$story -> setTitle( $title );
$_linkid = ( isset( $lid ) && $lid > 0 ) ? $lid : $newid;
$_link = $_REQUEST["description"] . "<br /><div><a href=" . XOOPS_URL . "/modules/".$xoopsModule->getVar('dirname')."/singlelink.php?cid=" . $cid . "&amp;lid=" . $_linkid . ">" . $title . "</a></div>";

$description = $wfmyts -> addslashes( trim( $_link ) );
$story -> setHometext( $description );
$story -> setBodytext( '' );
$nohtml = ( empty( $nohtml ) ) ? 0 : 1;
$nosmiley = ( empty( $nosmiley ) ) ? 0 : 1;
$story -> setNohtml( $nohtml );
$story -> setNosmiley( $nosmiley );
$story -> store();
$notification_handler = &xoops_gethandler( 'notification' );

$tags = array();
$tags['STORY_NAME'] = $story -> title();
$modhandler = &xoops_gethandler( 'module' );
$newsModule = &$modhandler -> getByDirname( "news" );
$tags['STORY_URL'] = XOOPS_URL . '/modules/news/article.php?storyid=' . $story -> storyid();
if ( !empty( $isnew ) ) {
    $notification_handler -> triggerEvent( 'story', $story -> storyid(), 'approve', $tags );
} 
$notification_handler -> triggerEvent( 'global', 0, 'new_story', $tags );
unset( $xoopsModule );

?>