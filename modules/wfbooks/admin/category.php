<?php
/**
 * $Id: category.php v 1.00 21 June 2005 John N Exp $
 * Module: WF-Links
 * Version: v1.0.3
 * Release Date: 21 June 2005
 * Developer: John N
 * Team: WF-Projects
 * Licence: GNU
 */
 
include 'admin_header.php';
include_once XOOPS_ROOT_PATH . '/class/xoopsform/grouppermform.php';

$op = '';

if (isset($_POST)) {
    foreach ($_POST as $k => $v) {
        ${$k} = $v;
    } 
} 

if (isset($_GET)) {
    foreach ($_GET as $k => $v) {
        ${$k} = $v;
    } 
}

function createcat($cid = 0)
{
    include_once '../class/wfl_lists.php';
    include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

    global $xoopsDB, $wfmyts, $xoopsModuleConfig, $totalcats, $xoopsModule;

    $lid = 0;
    $title = '';
    $imgurl = '';
    $description = '';
    $pid = '';
    $weight = 0;
    $nohtml = 0;
    $nosmiley = 0;
    $noxcodes = 0;
    $noimages = 0;
    $nobreak = 1;
    $spotlighttop = 0;
    $spotlighthis = 0;
    $heading = _AM_WFB_CCATEGORY_CREATENEW;
    $totalcats = wfl_totalcategory();

    if ($cid) {
        $sql = "SELECT * FROM " . $xoopsDB -> prefix('wfbooks_cat') . " WHERE cid=$cid";
        $cat_arr = $xoopsDB -> fetchArray($xoopsDB -> query($sql));
        $title = $wfmyts -> htmlSpecialChars($cat_arr['title']);
        $imgurl = $wfmyts -> htmlSpecialChars($cat_arr['imgurl']);
        $description = $wfmyts -> htmlSpecialChars($cat_arr['description']);
        $nohtml = intval($cat_arr['nohtml']);
        $nosmiley = intval($cat_arr['nosmiley']);
        $noxcodes = intval($cat_arr['noxcodes']);
        $noimages = intval($cat_arr['noimages']);
        $nobreak = intval($cat_arr['nobreak']);
        $spotlighthis = intval($cat_arr['spotlighthis']);
        $spotlighttop = intval($cat_arr['spotlighttop']);
        $weight = $cat_arr['weight'];
        $heading = _AM_WFB_CCATEGORY_MODIFY;

        $member_handler = & xoops_gethandler('member');
        $group_list = & $member_handler -> getGroupList();

        $gperm_handler = & xoops_gethandler('groupperm');
        $groups = $gperm_handler -> getGroupIds('WFBookCatPerm', $cid, $xoopsModule -> getVar('mid'));
        $groups = $groups;
    } else {
	$groups = true;
    }

    $sform = new XoopsThemeForm($heading, "op", xoops_getenv('PHP_SELF'));
    $sform -> setExtra('enctype="multipart/form-data"');

    $sform -> addElement(new XoopsFormText(_AM_WFB_FCATEGORY_TITLE, 'title', 50, 80, $title), true);
    $sform -> addElement(new XoopsFormText(_AM_WFB_FCATEGORY_WEIGHT, 'weight', 10, 80, $weight), false);
    
    if ($totalcats > 0) {
       $mytreechose = new XoopsTree($xoopsDB -> prefix('wfbooks_cat'), "cid", "pid");
       ob_start();
          $mytreechose -> makeMySelBox("title", "title", 0 , 1, "pid");
          $sform -> addElement(new XoopsFormLabel(_AM_WFB_FCATEGORY_SUBCATEGORY, ob_get_contents()));
       ob_end_clean();
    }

    $graph_array = & wflLists :: getListTypeAsArray(XOOPS_ROOT_PATH . "/" . $xoopsModuleConfig['catimage'], $type = "images");
    $indeximage_select = new XoopsFormSelect('', 'imgurl', $imgurl);
    $indeximage_select -> addOptionArray($graph_array);
    $indeximage_select -> setExtra("onchange='showImgSelected(\"image\", \"imgurl\", \"" . $xoopsModuleConfig['catimage'] . "\", \"\", \"" . XOOPS_URL . "\")'");
    $indeximage_tray = new XoopsFormElementTray(_AM_WFB_FCATEGORY_CIMAGE, '&nbsp;');
    $indeximage_tray -> addElement($indeximage_select);
    if (!empty($imgurl)) {
        $indeximage_tray -> addElement(new XoopsFormLabel('', "<br /><br /><img src='" . XOOPS_URL . "/" . $xoopsModuleConfig['catimage'] . "/" . $imgurl . "' name='image' id='image' alt='' />"));
    } else {
        $indeximage_tray -> addElement(new XoopsFormLabel('', "<br /><br /><img src='" . XOOPS_URL . "/uploads/blank.gif' name='image' id='image' alt='' />"));
    } 
    $sform -> addElement($indeximage_tray);

    $editor = wfl_getWysiwygForm( _AM_WFB_FCATEGORY_DESCRIPTION, 'description', $description, 15, 60, '');
    $sform -> addElement( $editor, false );

    $options_tray = new XoopsFormElementTray(_AM_WFB_TEXTOPTIONS, '<br />');

    $html_checkbox = new XoopsFormCheckBox('', 'nohtml', $nohtml);
    $html_checkbox -> addOption(1, _AM_WFB_DISABLEHTML);
    $options_tray -> addElement($html_checkbox);

    $smiley_checkbox = new XoopsFormCheckBox('', 'nosmiley', $nosmiley);
    $smiley_checkbox -> addOption(1, _AM_WFB_DISABLESMILEY);
    $options_tray -> addElement($smiley_checkbox);

    $xcodes_checkbox = new XoopsFormCheckBox('', 'noxcodes', $noxcodes);
    $xcodes_checkbox -> addOption(1, _AM_WFB_DISABLEXCODE);
    $options_tray -> addElement($xcodes_checkbox);

    $noimages_checkbox = new XoopsFormCheckBox('', 'noimages', $noimages);
    $noimages_checkbox -> addOption(1, _AM_WFB_DISABLEIMAGES);
    $options_tray -> addElement($noimages_checkbox);

    $breaks_checkbox = new XoopsFormCheckBox('', 'nobreak', $nobreak);
    $breaks_checkbox -> addOption(1, _AM_WFB_DISABLEBREAK);
    $options_tray -> addElement($breaks_checkbox);
    $sform -> addElement($options_tray);
    
//    $sform -> addElement(new XoopsFormSelectGroup(_AM_WFB_FCATEGORY_GROUPPROMPT, "groups", true, $groups, 5, true));

    $sform -> addElement(new XoopsFormHidden('cid', $cid));

    $sform -> addElement(new XoopsFormHidden('spotlighttop', $cid));

    $button_tray = new XoopsFormElementTray('', '');
    $hidden = new XoopsFormHidden('op', 'save');
    $button_tray -> addElement($hidden);

    if (!$cid) {
        $butt_create = new XoopsFormButton('', '', _AM_WFB_BSAVE, 'submit');
        $butt_create -> setExtra('onclick="this.form.elements.op.value=\'addCat\'"');
        $button_tray -> addElement($butt_create);

        $butt_clear = new XoopsFormButton('', '', _AM_WFB_BRESET, 'reset');
        $button_tray -> addElement($butt_clear);

        $butt_cancel = new XoopsFormButton('', '', _AM_WFB_BCANCEL, 'button');
        $butt_cancel -> setExtra('onclick="history.go(-1)"');
        $button_tray -> addElement($butt_cancel);
    } else {
        $butt_create = new XoopsFormButton('', '', _AM_WFB_BMODIFY, 'submit');
        $butt_create -> setExtra('onclick="this.form.elements.op.value=\'addCat\'"');
        $button_tray -> addElement($butt_create);

        $butt_delete = new XoopsFormButton('', '', _AM_WFB_BDELETE, 'submit');
        $butt_delete -> setExtra('onclick="this.form.elements.op.value=\'delCat\'"');
        $button_tray -> addElement($butt_delete);

        $butt_cancel = new XoopsFormButton('', '', _AM_WFB_BCANCEL, 'button');
        $butt_cancel -> setExtra('onclick="history.go(-1)"');
        $button_tray -> addElement($butt_cancel);
    } 
    $sform -> addElement($button_tray);
    $sform -> display();

    $result2 = $xoopsDB -> query("SELECT COUNT(*) FROM " . $xoopsDB -> prefix('wfbooks_cat') . "");
    list($numrows) = $xoopsDB -> fetchRow($result2);
} 

if (!isset($_POST['op'])) {
    $op = isset($_GET['op']) ? $_GET['op'] : 'main';
   } else {
    $op = isset($_POST['op']) ? $_POST['op'] : 'main';
   }

switch ($op)
{
    case "move":
        if (!isset($_POST['ok'])) {
            $cid = (isset($_POST['cid'])) ? $_POST['cid'] : $_GET['cid'];

            xoops_cp_header();
            wfl_adminmenu(_AM_WFB_MCATEGORY);

            include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';
            $mytree = new XoopsTree($xoopsDB -> prefix('wfbooks_cat'), "cid", "pid");
            $sform = new XoopsThemeForm( _AM_WFB_CCATEGORY_MOVE, "move", xoops_getenv('PHP_SELF'));
            ob_start();
              $mytree -> makeMySelBox( "title", "title", 0 , 0, "target" );
              $sform -> addElement(new XoopsFormLabel(_AM_WFB_BMODIFY, ob_get_contents()));
            ob_end_clean();
            $create_tray = new XoopsFormElementTray('', '');
            $create_tray -> addElement(new XoopsFormHidden('source', $cid));
            $create_tray -> addElement(new XoopsFormHidden('ok', 1));
            $create_tray -> addElement(new XoopsFormHidden('op', 'move'));
            $butt_save = new XoopsFormButton('', '', _AM_WFB_BMOVE, 'submit');
            $butt_save -> setExtra('onclick="this.form.elements.op.value=\'move\'"');
            $create_tray -> addElement($butt_save);
            $butt_cancel = new XoopsFormButton('', '', _AM_WFB_BCANCEL, 'submit');
            $butt_cancel -> setExtra('onclick="this.form.elements.op.value=\'cancel\'"');
            $create_tray -> addElement($butt_cancel);
            $sform -> addElement($create_tray);
            $sform -> display();
            xoops_cp_footer();
        } else {
            global $xoopsDB;

            $source = $_POST['source'];
            $target = $_POST['target'];
            if ($target == $source) {
                redirect_header("category.php?op=move&ok=0&cid=$source", 5, _AM_WFB_CCATEGORY_MODIFY_FAILED);
            }
            if (!$target) {
                redirect_header("category.php?op=move&ok=0&cid=$source", 5, _AM_WFB_CCATEGORY_MODIFY_FAILEDT);
            } 
            $sql = "UPDATE " . $xoopsDB -> prefix('wfbooks_links') . " set cid = " . $target . " WHERE cid =" . $source;
            $result = $xoopsDB -> queryF($sql);
            $error = _AM_WFB_DBERROR . ": <br /><br />" . $sql;
            if (!$result) {
                trigger_error($error, E_USER_ERROR);
            } 
            redirect_header("category.php?op=default", 1, _AM_WFB_CCATEGORY_MODIFY_MOVED);
            exit();
        } 
        break;

    case "addCat":

        $groups = isset( $_REQUEST['groups'] ) ? $_REQUEST['groups'] : array();
        $cid = ( isset( $_REQUEST["cid"] ) ) ? $_REQUEST["cid"] : 0;
        $pid = ( isset( $_REQUEST["pid"] ) ) ? $_REQUEST["pid"] : 0;
        $weight = ( isset( $_REQUEST["weight"] ) && $_REQUEST["weight"] > 0 ) ? $_REQUEST["weight"] : 0;
        $spotlighthis = ( isset( $_REQUEST["lid"] ) ) ? $_REQUEST["lid"] : 0;
        $spotlighttop = ( $_REQUEST["spotlighttop"] == 1 ) ? 1 : 0;
        $title = $wfmyts -> addslashes( $_REQUEST["title"] );
        $descriptionb = $wfmyts -> addslashes( $_REQUEST["description"] );
        $imgurl = ( $_REQUEST["imgurl"] && $_REQUEST["imgurl"] != "blank.gif" ) ? $wfmyts -> addslashes( $_REQUEST["imgurl"] ) : "";

        $nohtml = isset( $_REQUEST['nohtml'] );
        $nosmiley = isset( $_REQUEST['nosmiley'] );
        $noxcodes = isset( $_REQUEST['noxcodes'] );
        $noimages = isset( $_REQUEST['noimages'] );
        $nobreak = isset( $_REQUEST['nobreak'] );

        if ( !$cid ) {
            $cid = 0;
            $sql = "INSERT INTO " . $xoopsDB -> prefix( 'wfbooks_cat' ) . " (cid, pid, title, imgurl, description, nohtml, nosmiley, noxcodes, noimages, nobreak, weight, spotlighttop, spotlighthis ) VALUES ('', $pid, '$title', '$imgurl', '$descriptionb', '$nohtml', '$nosmiley', '$noxcodes', '$noimages', '$nobreak', '$weight',  $spotlighttop, $spotlighthis )";
            if ( $cid == 0 ) {
                $newid = $xoopsDB -> getInsertId();
            }

            // Notify of new category
           
            global $xoopsModule;
            $tags = array();
            $tags['CATEGORY_NAME'] = $title;
            $tags['CATEGORY_URL'] = XOOPS_URL . '/modules/' . $xoopsModule -> getVar( 'dirname' ) . '/viewcat.php?cid=' . $newid;
            $notification_handler = &xoops_gethandler( 'notification' );
            $notification_handler -> triggerEvent( 'global', 0, 'new_category', $tags );
            $database_mess = _AM_WFB_CCATEGORY_CREATED;
        } else {
            $sql = "UPDATE " . $xoopsDB -> prefix( 'wfbooks_cat' ) . " SET title ='$title', imgurl='$imgurl', pid =$pid, description='$descriptionb', spotlighthis='$spotlighthis' , spotlighttop='$spotlighttop', nohtml='$nohtml', nosmiley='$nosmiley', noxcodes='$noxcodes', noimages='$noimages', nobreak='$nobreak', weight='$weight' WHERE cid=" . $cid;
            $database_mess = _AM_WFB_CCATEGORY_MODIFIED;
        } 
        if ( !$result = $xoopsDB -> query( $sql ) ) {
            XoopsErrorHandler_HandleError( E_USER_WARNING, $sql, __FILE__, __LINE__ );
            return false;
        } 
        redirect_header( "category.php", 1, $database_mess );
        break;

    case "del":

        global $xoopsDB, $xoopsModule;

        $cid = (isset($_POST['cid']) && is_numeric($_POST['cid'])) ? intval($_POST['cid']) : intval($_GET['cid']);
        $ok = (isset($_POST['ok']) && $_POST['ok'] == 1) ? intval($_POST['ok']) : 0;
        $mytree = new XoopsTree($xoopsDB -> prefix('wfbooks_cat'), "cid", "pid");

        if ($ok == 1) {
            // get all subcategories under the specified category
            $arr = $mytree -> getAllChildId($cid);
            $lcount = count($arr);

            for ($i = 0; $i < $lcount; $i++) {
                // get all links in each subcategory
                $result = $xoopsDB -> query("SELECT lid FROM " . $xoopsDB -> prefix('wfbooks_links') . " WHERE cid=" . $arr[$i] . ""); 
                // now for each linkload, delete the text data and vote ata associated with the linkload
                while (list($lid) = $xoopsDB -> fetchRow($result)) {
                    $sql = sprintf("DELETE FROM %s WHERE lid = %u", $xoopsDB -> prefix('wfbooks_votedata'), $lid);
                    $xoopsDB -> query($sql);
                    $sql = sprintf("DELETE FROM %s WHERE lid = %u", $xoopsDB -> prefix('wfbooks_links'), $lid);
                    $xoopsDB -> query($sql); 

                    // delete comments
                    xoops_comment_delete($xoopsModule -> getVar('mid'), $lid);
                } 
                // all links for each subcategory is deleted, now delete the subcategory data
                $sql = sprintf("DELETE FROM %s WHERE cid = %u", $xoopsDB -> prefix('wfbooks_cat'), $arr[$i]);
                $xoopsDB -> query($sql);
            } 
            // all subcategory and associated data are deleted, now delete category data and its associated data
            $result = $xoopsDB -> query("SELECT lid FROM " . $xoopsDB -> prefix('wfbooks_links') . " WHERE cid=" . $cid . "");
            while (list($lid) = $xoopsDB -> fetchRow($result)) {
                $sql = sprintf("DELETE FROM %s WHERE lid = %u", $xoopsDB -> prefix('wfbooks_links'), $lid);
                $xoopsDB -> query($sql); 
                // delete comments
                xoops_comment_delete($xoopsModule -> getVar('mid'), $lid);
                $sql = sprintf("DELETE FROM %s WHERE lid = %u", $xoopsDB -> prefix('wfbooks_votedata'), $lid);
                $xoopsDB -> query($sql);
            } 
            $sql = sprintf("DELETE FROM %s WHERE cid = %u", $xoopsDB -> prefix('wfbooks_cat'), $cid);
            $error = _AM_WFB_DBERROR . ": <br /><br />" . $sql;
            xoops_groupperm_deletebymoditem ($xoopsModule -> getVar('mid'), 'WFBookCatPerm', $cid);
            if (!$result = $xoopsDB -> query($sql)) {
                trigger_error($error, E_USER_ERROR);
            } 
            redirect_header("category.php", 1, _AM_WFB_CCATEGORY_DELETED);
            exit();
        } else {
            xoops_cp_header();
            xoops_confirm(array('op' => 'del', 'cid' => $cid, 'ok' => 1), 'category.php', _AM_WFB_CCATEGORY_AREUSURE);
            xoops_cp_footer();
        } 
        break;

    case "modCat":
        $cid = (isset($_POST['cid'])) ? $_POST['cid'] : 0;
        xoops_cp_header();
        wfl_adminmenu(_AM_WFB_MCATEGORY);
        createcat($cid);
        xoops_cp_footer();
        break;

    case 'main':
        default:
        xoops_cp_header();
        wfl_adminmenu(_AM_WFB_MCATEGORY);

        include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';
        $mytree = new XoopsTree($xoopsDB -> prefix('wfbooks_cat'), "cid", "pid");
        $sform = new XoopsThemeForm(_AM_WFB_CCATEGORY_MODIFY, "category", xoops_getenv('PHP_SELF'));
        $totalcats = wfl_totalcategory();

        if ($totalcats > 0) {
            ob_start();
            $mytree -> makeMySelBox("title", "title");
            $sform -> addElement(new XoopsFormLabel(_AM_WFB_CCATEGORY_MODIFY_TITLE, ob_get_contents()));
            ob_end_clean();
            $dup_tray = new XoopsFormElementTray('', '');
            $dup_tray -> addElement(new XoopsFormHidden('op', 'modCat'));
            $butt_dup = new XoopsFormButton('', '', _AM_WFB_BMODIFY, 'submit');
            $butt_dup -> setExtra('onclick="this.form.elements.op.value=\'modCat\'"');
            $dup_tray -> addElement($butt_dup);
            $butt_move = new XoopsFormButton('', '', _AM_WFB_BMOVE, 'submit');
            $butt_move -> setExtra('onclick="this.form.elements.op.value=\'move\'"');
            $dup_tray -> addElement($butt_move);
            $butt_dupct = new XoopsFormButton('', '', _AM_WFB_BDELETE, 'submit');
            $butt_dupct -> setExtra('onclick="this.form.elements.op.value=\'del\'"');
            $dup_tray -> addElement($butt_dupct);
            $sform -> addElement($dup_tray);
            $sform -> display();
        } 
        createcat(0);
        xoops_cp_footer();
        break;
}
?>