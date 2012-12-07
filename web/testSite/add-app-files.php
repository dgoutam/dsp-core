<?php
require_once("./include/Membership.php");

$fgmembersite = new Membership();
if (!$fgmembersite->checkLogin()) {
    $fgmembersite->redirectToURL("login.php");
    exit;
}

if (!$fgmembersite->userIsAdmin()) {
    echo 'DENIED ACCESS: Only administrator accounts are allowed access to this page.' . "<br />";
    echo "<p><a href='login-home.php'>Home</a></p>";
    exit;
}
$appname = (isset($_GET['App'])) ? $_GET['App'] : '';

if (isset($_POST['submitted'])) {
    if ($fgmembersite->addAppFiles($appname)) {
        $fgmembersite->redirectToURL("manage-app-files.php?App=$appname");
        exit;
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Add Application Files</title>
        <link href="style/main.css" rel="stylesheet" type="text/css" />
        <link href="style/fg_membersite.css" rel="stylesheet" type="text/css" />
       
    </head>
    <body>
        <!-- Form Code Start -->
        <div id='fg_membersite'>
        <form id='app' action='<?php echo $fgmembersite->getSelfScript() . "?App=$appname"; ?>' method='post' accept-charset='UTF-8' enctype="multipart/form-data">
            <fieldset >
                <legend>Add Application Files</legend>

                <input type='hidden' name='submitted' id='submitted' value='1'/>

                <div class='short_explanation'>* required fields</div>
                <input type='text'  class='spmhidip' name='<?php echo $fgmembersite->getSpamTrapInputName(); ?>'/>

                <div><span class='error'><?php echo $fgmembersite->getErrorMessage(); ?></span></div>
                <div class='container'>
                    <label for='files' >Local Application Files: *</label><br/>
                    <input type='file' name='files[]' id='files' size="80" maxlength="128" multiple="true" min="1" /><br/>
                    <span id='register_files_errorloc' class='error'></span>
                </div>

                <div class='container'>
                    <input type='submit' name='Submit' value='Submit' />
                </div>

            </fieldset>
        </form>


<!--
Form Code End (see html-form-guide.com for more info.)
-->

        <br><br><br>
        <p><a href='manage-app-files.php?App=<?php echo $appname; ?>'>Manage Files in <?php echo $appname; ?></a></p>
        <p><a href='manage-apps.php'>Manage Apps</a></p>
        <p><a href='admin-login-home.php'>Home</a></p>
    </body>
</html>
