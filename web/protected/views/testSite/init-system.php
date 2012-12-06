<?php
require_once("./include/Membership.php");

$fgmembersite = new Membership();
if (isset($_POST['submitted']))
{
    if ($fgmembersite->initSystem())
    {
        $state = $fgmembersite->getState();
        switch ($state) {
        case 'init required':
            $fgmembersite->redirectToURL("init-system.php");
            break;
        case 'admin required':
            $fgmembersite->redirectToURL("init-register.php");
            break;
        }
        $fgmembersite->redirectToURL("thank-you-system.html");
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
    <title>Cloud Initialization - Admin Setup</title>
    <link rel="STYLESHEET" type="text/css" href="style/fg_membersite.css" />
    <script type='text/javascript' src='scripts/gen_validatorv31.js'></script>
    <link rel="STYLESHEET" type="text/css" href="style/pwdwidget.css" />
    <script src="scripts/pwdwidget.js" type="text/javascript"></script>
</head>
<body>

<!-- Form Code Start -->
<div id='fg_membersite'>
<form id='register' action='<?php echo $fgmembersite->getSelfScript(); ?>' method='post' accept-charset='UTF-8'>
<fieldset >
<legend>System Initialization</legend>

<input type='hidden' name='submitted' id='submitted' value='1'/>

<div class='short_explanation'>* required fields</div>
<input type='text'  class='spmhidip' name='<?php echo $fgmembersite->getSpamTrapInputName(); ?>' />

<div><span class='error'><?php echo $fgmembersite->getErrorMessage(); ?></span></div>
<input type="hidden" name="admin" value="true">

<div class='container'>
    <input type='submit' name='Submit' value='Submit' />
</div>

</fieldset>
</form>
<!--
Form Code End (see html-form-guide.com for more info.)
-->

</body>
</html>