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

// get login data
list ($sLoginMenu, $sExtra) = $GLOBALS['CMembers']->getLoginData();

// profile id
$i = (int)$_GET['id'];
if ($i) {
    $aMemberInfo = $GLOBALS['CMembers']->getProfileInfo($i);
    if ($aMemberInfo) {

        // get all photos by profile
        $sPhotos = $GLOBALS['CPhotos']->getAllPhotos($i);

        // infinite scroll
        $sPerpage = 20;

        if($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') { // ajax
            if($sPhotos) {
                $sPage = (int)$_GET['page'] + 1;
                echo <<<EOF
<div class="main_container">
{$sPhotos}
</div>
<nav id="page-nav">
  <a href="profile.php?id={$i}&page={$sPage}&per_page={$sPerpage}"></a>
</nav>
EOF;
            }
            exit;
        }

        $sInfinite = ($sPhotos == '') ? '' : <<<EOF
<nav id="page-nav">
  <a href="profile.php?id={$i}&page=2&per_page={$sPerpage}"></a>
</nav>
EOF;

        // draw profile page
        $aKeys = array(
            '{menu_elements}' => $sLoginMenu,
            '{extra_data}' => $sExtra,
            '{images_set}' => $sPhotos,
            '{profile_name}' => $aMemberInfo['first_name'],
            '{infinite}' => $sInfinite
        );
        echo strtr(file_get_contents('templates/profile.html'), $aKeys);
        exit;
    }
}

header('Location: error.php');