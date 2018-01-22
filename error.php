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

// login system init and generation code
list ($sLoginMenu, $sExtra) = $GLOBALS['CMembers']->getLoginData();

// draw common page
$aKeys = array(
    '{menu_elements}' => $sLoginMenu,
    '{extra_data}' => $sExtra,
    '{images_set}' => '<center><h1>Error Occurred, please try again</h1></center>'
);
echo strtr(file_get_contents('templates/index.html'), $aKeys);