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
* Photos class
*/
class CPhotos {

    // constructor
    function CPhotos() {
    }

    // get all photos
    function getAllPhotos($iPid = 0, $sKeyPar = '') {

        // prepare WHERE filter
        $aWhere = array();
        if ($iPid) {
            $aWhere[] = "`owner` = '{$iPid}'";
        }
        if ($sKeyPar != '') {
            $sKeyword = $GLOBALS['MySQL']->escape($sKeyPar);
            $aWhere[] = "`title` LIKE '%{$sKeyword}%'";
        }
        $sFilter = (count($aWhere)) ? 'WHERE ' . implode(' AND ', $aWhere) : '';

        // pagination
        $iPage = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
        $iPerPage = (isset($_GET['per_page'])) ? (int)$_GET['per_page'] : 20;
        $iPage = ($iPage < 1) ? 1 : $iPage;
        $iFrom = ($iPage - 1) * $iPerPage;
        $iFrom = ($iFrom < 1) ? 0 : $iFrom;
        $sLimit = "LIMIT {$iFrom}, {$iPerPage}";

        $sSQL = "
            SELECT * 
            FROM `pd_photos`
            {$sFilter}
            ORDER BY `when` DESC
            {$sLimit}
        ";

        $aPhotos = $GLOBALS['MySQL']->getAll($sSQL);

        $sImages = '';
        $sFolder = 'photos/';
        foreach ($aPhotos as $i => $aPhoto) {

            $iPhotoId = (int)$aPhoto['id'];
            $sFile = $aPhoto['filename'];
            $sTitle = $aPhoto['title'];
            $iCmts = (int)$aPhoto['comments_count'];

            $iLoggId = (int)$_SESSION['member_id'];
            $iOwner = (int)$aPhoto['owner'];
            $iRepins = (int)$aPhoto['repin_count'];
            $iLikes = (int)$aPhoto['like_count'];
            $sActions = ($iLoggId && $iOwner != $iLoggId) ? '<a href="#" class="button repinbutton">Repin</a><a href="#" class="button likebutton">Like</a>' : '';

            // display a blank image for not existing photos
            $sFile = (file_exists($sFolder . $sFile)) ? $sFile : 'blank_photo.jpg';

            $aPathInfo = pathinfo($sFolder . $sFile);
            $sExt = strtolower($aPathInfo['extension']);

            $sImages .= <<<EOL
<!-- pin element {$iPhotoId} -->
<div class="pin" pin_id="{$iPhotoId}">
    <div class="holder">
        <div class="actions">
            {$sActions}
            <a href="#" class="button comment_tr">Comment</a>
        </div>
        <a class="image ajax" href="service.php?id={$iPhotoId}" title="{$sTitle}">
            <img alt="{$sTitle}" src="{$sFolder}{$sFile}">
        </a>
    </div>
    <p class="desc">{$sTitle}</p>
    <p class="info">
        <span class="LikesCount"><strong>{$iLikes}</strong> likes</span>
        <span>{$iRepins} repins</span>
        <span>{$iCmts} comments</span>
    </p>
    <form class="comment" method="post" action="" style="display: none" onsubmit="return submitComment(this, {$iPhotoId})">
        <textarea placeholder="Add a comment..." maxlength="255" name="comment"></textarea>
        <input type="submit" class="button" value="Comment" />
    </form>
</div>
EOL;
        }
        return $sImages;
    }

    // get certain photo info
    function getPhotoInfo($i) {
        $sSQL = "SELECT * FROM `pd_photos` WHERE `id` = '{$i}'";
        $aInfos = $GLOBALS['MySQL']->getAll($sSQL);
        return $aInfos[0];
    }

    // format time by timestamp
    function formatTime($iSec) {
        $sFormat = 'j F Y';
        return gmdate($sFormat, $iSec);
    }

    // insert a new blank photo into DB
    function insertBlankPhoto($sTitle, $iOwner) {
        $sTitle = $GLOBALS['MySQL']->escape($sTitle);
        $iOwner = (int)$iOwner;

        $sSQL = "INSERT INTO `pd_photos` SET `title` = '{$sTitle}', `owner` = '{$iOwner}', `when` = UNIX_TIMESTAMP()";
        $GLOBALS['MySQL']->res($sSQL);
        return $GLOBALS['MySQL']->lastId();
    }

    // update filename
    function updateFilename($i, $sFilename) {
        $sFilename = $GLOBALS['MySQL']->escape($sFilename);

        $sSQL = "UPDATE `pd_photos` SET `filename` = '{$sFilename}' WHERE `id`='{$i}'";
        return $GLOBALS['MySQL']->res($sSQL);
    }

    function acceptLike() {
        $iItemId = (int)$_POST['id']; // prepare necessary information
        $iLoggId = (int)$_SESSION['member_id'];

        if ($iItemId && $iLoggId) {
            // check - if there is any recent record from the same person for last 1 hour
            $iOldId = $GLOBALS['MySQL']->getOne("SELECT `l_item_id` FROM `pd_items_likes` WHERE `l_item_id` = '{$iItemId}' AND `l_pid` = '{$iLoggId}' AND `l_when` >= UNIX_TIMESTAMP() - 3600 LIMIT 1");
            if (! $iOldId) {
                // if everything is fine - we can add a new like
                $GLOBALS['MySQL']->res("INSERT INTO `pd_items_likes` SET `l_item_id` = '{$iItemId}', `l_pid` = '{$iLoggId}', `l_when` = UNIX_TIMESTAMP()");
                // and update total amount of likes
                $GLOBALS['MySQL']->res("UPDATE `pd_photos` SET `like_count` = `like_count` + 1 WHERE `id` = '{$iItemId}'");
            }
            // and return total amount of likes
            return (int)$GLOBALS['MySQL']->getOne("SELECT `like_count` FROM `pd_photos` WHERE `id` = '{$iItemId}'");
        }
    }
    function acceptRepin() {
        $iItemId = (int)$_POST['id']; // prepare necessary information
        $iLoggId = (int)$_SESSION['member_id'];

        if ($iItemId && $iLoggId) {
            $aPhotoInfo = $this->getPhotoInfo($iItemId);

            // check - for already repinned element
            $iOldId = $GLOBALS['MySQL']->getOne("SELECT `id` FROM `pd_photos` WHERE `owner` = '{$iLoggId}' AND `repin_id` = '{$iItemId}'");
            if (! $iOldId) {
                // if everything is fine - add a copy of photo as own photo (repin)
                $sSQL = "INSERT INTO `pd_photos` SET
                            `title` = '{$aPhotoInfo['title']}',
                            `filename` = '{$aPhotoInfo['filename']}',
                            `owner` = '{$iLoggId}',
                            `when` = UNIX_TIMESTAMP(),
                            `repin_id` = '{$iItemId}'
                ";
                $GLOBALS['MySQL']->res($sSQL);

                // update repin count for original photo
                $GLOBALS['MySQL']->res("UPDATE `pd_photos` SET `repin_count` = `repin_count` + 1 WHERE `id` = '{$iItemId}'");
            }
            // and return current member id
            return $iLoggId;
        }
    }

}

$GLOBALS['CPhotos'] = new CPhotos();