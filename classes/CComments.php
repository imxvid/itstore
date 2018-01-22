<?php
/**
 *
 * Pinterest-like script - a series of tutorials
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 * 
 * Copyright 2012, Script Tutorials
 * http://www.script-tutorials.com/
 */

/*
* Comments class
*/
class CComments {

    // constructor
    function CComments() {
    }

    // return comments block
    function getComments($i) {
        // draw last 5 comments
        $sComments = '';
        $aComments = $GLOBALS['MySQL']->getAll("SELECT * FROM `pd_items_cmts` WHERE `c_item_id` = '{$i}' ORDER BY `c_when` DESC LIMIT 5");
        foreach ($aComments as $iCmtId => $aCmtsInfo) {
            $sWhen = date('F j, Y H:i', $aCmtsInfo['c_when']);
            $sComments .= <<<EOF
<div class="comment" id="{$aCmtsInfo['c_id']}">
    <p>Comment from {$aCmtsInfo['c_name']} <span>({$sWhen})</span>:</p>
    <p>{$aCmtsInfo['c_text']}</p>
</div>
EOF;
        }
        return $sComments;
    }

    function acceptComment() {
        $iItemId = (int)$_POST['id']; // prepare necessary information
        $sIp = $this->getVisitorIP();

        $aMemberInfo = $GLOBALS['CMembers']->getProfileInfo($_SESSION['member_id']);
        $sName = $GLOBALS['MySQL']->escape(strip_tags($aMemberInfo['first_name']));
        $sText = $GLOBALS['MySQL']->escape(strip_tags($_POST['comment']));

        if ($iItemId && $sName && strlen($sText) > 2) {
            // check - if there is any recent comment from the same person (IP) or not (for last 5 mins)
            $iOldId = $GLOBALS['MySQL']->getOne("SELECT `c_item_id` FROM `pd_items_cmts` WHERE `c_item_id` = '{$iItemId}' AND `c_ip` = '{$sIp}' AND `c_when` >= UNIX_TIMESTAMP() - 300 LIMIT 1");
            if (! $iOldId) {
                // if everything is fine - allow to add comment
                $GLOBALS['MySQL']->res("INSERT INTO `pd_items_cmts` SET `c_item_id` = '{$iItemId}', `c_ip` = '{$sIp}', `c_when` = UNIX_TIMESTAMP(), `c_name` = '{$sName}', `c_text` = '{$sText}'");
                $GLOBALS['MySQL']->res("UPDATE `pd_photos` SET `comments_count` = `comments_count` + 1 WHERE `id` = '{$iItemId}'");

                // and print out last 5 comments
                $sOut = '';
                $aComments = $GLOBALS['MySQL']->getAll("SELECT * FROM `pd_items_cmts` WHERE `c_item_id` = '{$iItemId}' ORDER BY `c_when` DESC LIMIT 5");
                foreach ($aComments as $i => $aCmtsInfo) {
                    $sWhen = date('F j, Y H:i', $aCmtsInfo['c_when']);
                    $sOut .= <<<EOF
<div class="comment" id="{$aCmtsInfo['c_id']}">
    <p>Comment from {$aCmtsInfo['c_name']} <span>({$sWhen})</span>:</p>
    <p>{$aCmtsInfo['c_text']}</p>
</div>
EOF;
                }
                return $sOut;
            }
        }
    }

    // get visitor IP
    function getVisitorIP() {
        $ip = "0.0.0.0";
        if( ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) && ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif( ( isset( $_SERVER['HTTP_CLIENT_IP'])) && (!empty($_SERVER['HTTP_CLIENT_IP'] ) ) ) {
            $ip = explode(".",$_SERVER['HTTP_CLIENT_IP']);
            $ip = $ip[3].".".$ip[2].".".$ip[1].".".$ip[0];
        } elseif((!isset( $_SERVER['HTTP_X_FORWARDED_FOR'])) || (empty($_SERVER['HTTP_X_FORWARDED_FOR']))) {
            if ((!isset( $_SERVER['HTTP_CLIENT_IP'])) && (empty($_SERVER['HTTP_CLIENT_IP']))) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }
        return $ip;
    }
}

$GLOBALS['Comments'] = new CComments();
