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
* Members class
*/
class CMembers {

    // constructor
    function CMembers() {
        session_start();
    }

    // get login box function
    function getLoginData() {
        if (isset($_GET['logout'])) { // logout process
            if (isset($_SESSION['member_email']) && isset($_SESSION['member_pass']))
                $this->performLogout();
        }

        if ($_POST && $_POST['email'] && $_POST['password']) { // login process
            if ($this->checkLogin($_POST['email'], $_POST['password'], false)) { // successful login
                $this->performLogin($_POST['email'], $_POST['password']);
                header('Location: index.php');
                exit;
            }
        } else { // in case if we are already logged in
            if (isset($_SESSION['member_email']) && $_SESSION['member_email'] && $_SESSION['member_pass']) {
                $aReplaces = array(
                    '{name}' => $_SESSION['member_email'],
                    '{status}' => $_SESSION['member_status'],
                    '{role}' => $_SESSION['member_role'],
                );

                $iPid = $_SESSION['member_id'];
                // display Profiles menu and Logout
                $sLoginMenu = <<<EOF
<li><a href="#add_form" id="add_pop">Add +</a></li>
<li>
    <a href="profile.php?id={$iPid}">Profile<span></span></a>
    <ul>
        <li><a href="#">Invite Friends</a></li>
        <li><a href="#">Find Friends</a></li>
        <li class="div"><a href="#">Boards</a></li>
        <li><a href="#">Pins</a></li>
        <li><a href="#">Likes</a></li>
        <li class="div"><a href="#">Settings</a></li>
        <li><a href="index.php?logout">Logout</a></li>
    </ul>
</li>
<li><a href="index.php?logout">Logout</a></li>
EOF;
        $sExtra = <<<EOF
<!-- upload form -->
<a href="#x" class="overlay" id="add_form"></a>
<div class="popup">
    <div class="header">
        <a class="close" href="#close">x</a>
        <h2>Upload a Pin</h2>
    </div>
    <form id="upload_form">
        <input type="file" name="image_file" id="image_file" onchange="" />
    </form>
    <div id="upload_result"></div>
</div>
EOF;
                return array($sLoginMenu, $sExtra);
            }
        }

        // display Join and Login menu buttons
        $sLoginMenu = <<<EOF
<li><a href="#join_form" id="join_pop">Unirse</a></li>
<li><a href="#login_form" id="login_pop">Iniciar Session</a></li>
EOF;
        $sExtra = <<<EOF
<!-- join form -->
<a href="#x" class="overlay2" id="join_form"></a>
<div class="popup">
    <div class="header">
        <a class="close" href="#close">x</a>
        <h2>Create your account</h2>
    </div>

    <form method="POST" action="service.php">
        <ul class="ctrl_grp">
            <li>
                <input type="text" name="email" />
                <label>Email Address</label>
                <span class="fff"></span>
            </li>
            <li>
                <input type="password" name="password" />
                <label>Password</label>
                <span class="fff"></span>
            </li>
            <li>
                <input type="text" name="first_name" />
                <label>First Name</label>
                <span class="fff"></span>
            </li>
            <li>
                <input type="text" name="last_name" />
                <label>Last Name</label>
                <span class="fff"></span>
            </li>
        </ul>
        <div>
            <input type="hidden" name="Join" value="Join" />
            <button class="submit_button" type="submit">Create Account</button>
        </div>
    </form>
</div>

<!-- login form -->
<a href="#x" class="overlay3" id="login_form"></a>
<div class="popup">
    <div class="header">
        <a class="close" href="#close">x</a>
        <h2>Login</h2>
    </div>

    <form method="POST" action="index.php">
        <ul class="ctrl_grp">
            <li>
                <input type="text" name="email" id="id_email">
                <label>Email</label>
                <span class="fff"></span>
            </li>
            <li>
                <input type="password" name="password" id="id_password">
                <label>Password</label>
                <span class="fff"></span>
            </li>
        </ul>
        <div>
            <button class="submit_button" type="submit">Login</button>
        </div>
    </form>
</div>
EOF;
        return array($sLoginMenu, $sExtra);
    }

    // perform login
    function performLogin($sEmail, $sPass) {
        $this->performLogout();

        // make variables safe
        $sEmail = $GLOBALS['MySQL']->escape($sEmail);

        $aProfile = $GLOBALS['MySQL']->getRow("SELECT * FROM `pd_profiles` WHERE `email`='{$sEmail}'");
        // $sPassEn = $aProfile['password'];
        $iPid = $aProfile['id'];
        $sSalt = $aProfile['salt'];
        $sStatus = $aProfile['status'];
        $sRole = $aProfile['role'];

        $sPass = sha1(md5($sPass) . $sSalt);

        $_SESSION['member_id'] = $iPid;
        $_SESSION['member_email'] = $sEmail;
        $_SESSION['member_pass'] = $sPass;
        $_SESSION['member_status'] = $sStatus;
        $_SESSION['member_role'] = $sRole;
    }

    // perform logout
    function performLogout() { 
        unset($_SESSION['member_id']);
        unset($_SESSION['member_email']);
        unset($_SESSION['member_pass']);
        unset($_SESSION['member_status']);
        unset($_SESSION['member_role']);
    }

    // check login
    function checkLogin($sEmail, $sPass, $isHash = true) {
        // escape variables to make them self
        $sEmail = $GLOBALS['MySQL']->escape($sEmail);
        $sPass = $GLOBALS['MySQL']->escape($sPass);

        $aProfile = $GLOBALS['MySQL']->getRow("SELECT * FROM `pd_profiles` WHERE `email`='{$sEmail}'");
        $sPassEn = $aProfile['password'];

        if ($sEmail && $sPass && $sPassEn) {
            if (! $isHash) {
                $sSalt = $aProfile['salt'];
                $sPass = sha1(md5($sPass) . $sSalt);
            }
            return ($sPass == $sPassEn);
        }
        return false;
    }

    // profile registration
    function registerProfile() {
        $sFirstname = $GLOBALS['MySQL']->escape($_POST['first_name']);
        $sLastname = $GLOBALS['MySQL']->escape($_POST['last_name']);
        $sEmail = $GLOBALS['MySQL']->escape($_POST['email']);
        $sPassword = $GLOBALS['MySQL']->escape($_POST['password']);

        if ($sEmail && $sPassword) {
            // check if email is already exists
            $aProfile = $GLOBALS['MySQL']->getRow("SELECT * FROM `pd_profiles` WHERE `email`='{$sEmail}'");
            if ($aProfile['id'] > 0) {
                // relocate to 'error' page
                header('Location: error.php');
            } else {
                // generate Salt and Cached password
                $sSalt = $this->getRandSaltCode();
                $sPass = sha1(md5($sPassword) . $sSalt);

                // add new member into database
                $sSQL = "
                    INSERT INTO `pd_profiles` SET 
                    `first_name` = '{$sFirstname}',
                    `last_name` = '{$sLastname}',
                    `email` = '{$sEmail}',
                    `password` = '{$sPass}',
                    `salt` = '{$sSalt}',
                    `status` = 'active',
                    `role` = '1',
                    `date_reg` = NOW();
                ";
                $GLOBALS['MySQL']->res($sSQL);

                // autologin
                $this->performLogin($sEmail, $sPassword);

                // relocate back to index page
                header('Location: index.php');
                exit;
            }
        } else {
            // otherwise - relocate to error page
            header('Location: error.php');
        }
    }

    // get random salt code
    function getRandSaltCode($iLen = 8) {
        $sRes = '';

        $sChars = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        for ($i = 0; $i < $iLen; $i++) {
            $z = rand(0, strlen($sChars) -1);
            $sRes .= $sChars[$z];
        }
        return $sRes;
    }

    // get certain member info
    function getProfileInfo($i) {
        $sSQL = "
            SELECT * 
            FROM `pd_profiles`
            WHERE `id` = '{$i}'
        ";
        $aInfos = $GLOBALS['MySQL']->getAll($sSQL);
        return $aInfos[0];
    }
}

$GLOBALS['CMembers'] = new CMembers();