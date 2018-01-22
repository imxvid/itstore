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

function uploadImageFile() { // Note: GD library is required for this upload function

    $iDefWidth = 192; // default photos width (in case of resize)
    $iFDefWidth = 556; // full default photos width (in case of resize)

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $iWidth = $iHeight = $iFDefWidth; // desired image dimensions
        $iJpgQuality = 75;

        if ($_FILES) {

            // if there are no errors and filesize less than 400kb
            if (! $_FILES['image_file']['error'] && $_FILES['image_file']['size'] < 400 * 1024) {
                if (is_uploaded_file($_FILES['image_file']['tmp_name'])) {

                    // new unique filename
                    $sTempFileName = 'photos/' . md5(time().rand());

                    // move uploaded file into cache folder
                    move_uploaded_file($_FILES['image_file']['tmp_name'], $sTempFileName);

                    // change file permission to 644
                    @chmod($sTempFileName, 0644);

                    // if temp file exists
                    if (file_exists($sTempFileName) && filesize($sTempFileName) > 0) {
                        $aSize = getimagesize($sTempFileName); // obtain image info
                        if (!$aSize) {
                            @unlink($sTempFileName);
                            return;
                        }

                        // check for image type and create a new image from file 
                        switch($aSize[2]) {
                            case IMAGETYPE_JPEG:
                                $sExt = '.jpg';
                                $vImg = @imagecreatefromjpeg($sTempFileName);
                                break;
                            case IMAGETYPE_PNG:
                                $sExt = '.png';
                                $vImg = @imagecreatefrompng($sTempFileName);
                                break;
                            default:
                                @unlink($sTempFileName);
                                return;
                        }

                        // get source image width and height
                        $iSrcWidth = imagesx($vImg);
                        $iSrcHeight = imagesy($vImg);

                        // recalculate height (depends on width)
                        $iHeight = $iSrcHeight * $iWidth / $iSrcWidth;

                        // create a new true color image
                        $vDstImg = @imagecreatetruecolor($iWidth, $iHeight);

                        // copy and resize
                        imagecopyresampled($vDstImg, $vImg, 0, 0, 0, 0, $iWidth, $iHeight, $iSrcWidth, $iSrcHeight);

                        // add a blank image object into DB
                        $iLastId = $GLOBALS['CPhotos']->insertBlankPhoto($_FILES['image_file']['name'], $_SESSION['member_id']);

                        // define a result image filename
                        $sResultFileName = 'photos/f_pic' . $iLastId . $sExt;

                        // update filename for our object
                        $GLOBALS['CPhotos']->updateFilename($iLastId, 'pic' . $iLastId . $sExt);

                        // output image to file and set permission 644
                        imagejpeg($vDstImg, $sResultFileName, $iJpgQuality);
                        @chmod($sResultFileName, 0644);

                        // and, prepare a thumbnail as well
                        $iWidth = $iDefWidth;
                        $iHeight = $iSrcHeight * $iWidth / $iSrcWidth;
                        $vDstThImg = @imagecreatetruecolor($iWidth, $iHeight);
                        imagecopyresampled($vDstThImg, $vImg, 0, 0, 0, 0, $iWidth, $iHeight, $iSrcWidth, $iSrcHeight);
                        $sResultThumnName = 'photos/pic' . $iLastId . $sExt;
                        imagejpeg($vDstThImg, $sResultThumnName, $iJpgQuality);
                        @chmod($sResultThumnName, 0644);

                        // unlink temp file
                        @unlink($sTempFileName);
                        return $sResultFileName;
                    }
                }
            }
        }
    }
}

// upload available only for logged in members
if ($_SESSION['member_id'] && $_SESSION['member_status'] == 'active' && $_SESSION['member_role']) {
    $sImage = uploadImageFile();
    echo '1';
}