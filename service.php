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

// set warning level
if (version_compare(phpversion(), '5.3.0', '>=')  == 1)
  error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
else
  error_reporting(E_ALL & ~E_NOTICE); 

require_once('classes/CMySQL.php');
require_once('classes/CMembers.php');
require_once('classes/CPhotos.php');
require_once('classes/CComments.php');

if (! isset($_SESSION['member_id']) && $_POST['Join'] == 'Join') {
    $GLOBALS['CMembers']->registerProfile();
}

$i = (int)$_GET['id'];

if ($_GET && $_GET['get'] == 'comments') {
    header('Content-Type: text/html; charset=utf-8');
    echo $GLOBALS['Comments']->getComments($i);
    exit;
}
if ($_POST) {
    header('Content-Type: text/html; charset=utf-8');
    if ($_SESSION['member_id'] && $_SESSION['member_status'] == 'active' && $_SESSION['member_role']) {
        switch($_POST['add']) {
            case 'comment':
                echo $GLOBALS['Comments']->acceptComment(); exit;
                break;
            case 'like':
                echo $GLOBALS['CPhotos']->acceptLike(); exit;
                break;
            case 'repin':
                echo $GLOBALS['CPhotos']->acceptRepin(); exit;
                break;
        }
    }
    echo '<h3>Please login first</h3>';
    exit;
}

if (! $i) { // if something is wrong - relocate to error page
    header('Location: error.php');
    exit;
}

$aPhotoInfo = $GLOBALS['CPhotos']->getPhotoInfo($i);
$aOwnerInfo = $GLOBALS['CMembers']->getProfileInfo($aPhotoInfo['owner']);

$sOwnerName = ($aOwnerInfo['first_name']) ? $aOwnerInfo['first_name'] : $aOwnerInfo['email'];
$sPhotoTitle = $aPhotoInfo['title'];
$sPhotoDate = ($aPhotoInfo['repin_id'] == 0) ? 'Uploaded on ' : 'Repinned on ';
$sPhotoDate .= $GLOBALS['CPhotos']->formatTime($aPhotoInfo['when']);

$sFolder = 'photos/';
$sFullImgPath = $sFolder . 'f_' . $aPhotoInfo['filename'];

// display a blank image for not existing photos
$sFullImgPath = (file_exists($sFullImgPath)) ? $sFullImgPath : $sFolder . 'blank_photo.jpg';

$aSize = getimagesize($sFullImgPath); // get image info
$iWidth = $aSize[0];
$iHeight = $aSize[1];

// repin possibility to logged members
$iLoggId = (int)$_SESSION['member_id'];
$sActions = ($iLoggId && $aPhotoInfo['owner'] != $iLoggId) ? '<a href="#" class="button repinbutton" onclick="return repinPhoto(this);">Repin</a>' : '';

?>
<div class="pin bigpin" bpin_id="<?= $i ?>">
    <div class="owner">
        <a href="#" class="button follow_button">Follow</a>
        <a class="owner_img" href="profile.php?id=<?= $aOwnerInfo['id'] ?>">
            <img alt="<?= $sOwnerName ?>" src="images/avatar.jpg" />
        </a>
        <p class="owner_name"><a href="profile.php?id=<?= $aOwnerInfo['id'] ?>"><?= $sOwnerName ?></a></p>
        <p class="owner_when"><?= $sPhotoDate ?></p>
    </div>
    <div class="holder">
        <div class="actions">
            <?= $sActions ?>
        </div>
        <a class="image" href="#" title="<?= $sPhotoTitle ?>">
            <img alt="<?= $sPhotoTitle ?>" src="<?= $sFullImgPath ?>" style="width:<?= $iWidth ?>px;height:<?= $iHeight ?>px;" />
        </a>
    </div>

    <p class="desc"><?= $sPhotoTitle ?></p>

    <div class="comments"></div>

    <script>
    function submitCommentAjx() {
        $.ajax({ 
          type: 'POST',
          url: 'service.php',
          data: 'add=comment&id=' + <?= $i ?> + '&comment=' + $('#pcomment').val(),
          cache: false, 
          success: function(html){
            if (html) {
              $('.comments').html(html);
              $(this).colorbox.resize();
            }
          } 
        });
    }
    function repinPhoto(obj) {
        var iPinId = $(obj).parent().parent().parent().attr('bpin_id');
        $.ajax({ 
          url: 'service.php',
          type: 'POST',
          data: 'add=repin&id=' + iPinId,
          cache: false, 
          success: function(res){
            window.location.href = 'profile.php?id=' + res;
          } 
        });
        return false;
    }
    </script>
    <form class="comment" method="post" action="#">
        <textarea placeholder="Add a comment..." maxlength="255" id="pcomment"></textarea>
        <button type="button" class="button" onclick="return submitCommentAjx()">Comment</button>
    </form>
</div>