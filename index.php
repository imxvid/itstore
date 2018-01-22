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

// get search keyword (if provided)
$sSearchParam = strip_tags($_GET['q']);

// get all photos
$sPhotos = $GLOBALS['CPhotos']->getAllPhotos(0, $sSearchParam);

if ($sSearchParam) {
    $sExtra .= '<h2 class="pname">Search results for <strong>'.$sSearchParam.'</strong></h2>';
}

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
  <a href="index.php?page={$sPage}&per_page={$sPerpage}"></a>
</nav>
EOF;
    }
    exit;
}

$sInfinite = ($sPhotos == '') ? '' : <<<EOF
<nav id="page-nav">
  <a href="index.php?page=2&per_page={$sPerpage}"></a>
</nav>
EOF;

// draw common page
$aKeys = array(
    '{menu_elements}' => $sLoginMenu,
    '{extra_data}' => $sExtra,
    '{images_set}' => $sPhotos,
    '{infinite}' => $sInfinite
);
echo strtr(file_get_contents('templates/index.html'), $aKeys);