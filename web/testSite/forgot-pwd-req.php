<?php
require_once("./include/Membership.php");

$fgmembersite = new Membership();
if(isset($_POST['submitted'])) {
    $result = $fgmembersite->forgotPassword();
    if (is_string($result)) { // security question returned
        $fgmembersite->redirectToURL("security-question.php");
        exit;
    }
    else if (true === $result) {
        $fgmembersite->redirectToURL("reset-pwd-link-sent.html");
        exit;
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
      <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
      <title>Forgot Password</title>
      <link rel="STYLESHEET" type="text/css" href="style/fg_membersite.css" />
      <script type='text/javascript' src='scripts/gen_validatorv31.js'></script>
</head>
<body>
<!-- Form Code Start -->
<div id='fg_membersite'>
<form id='resetreq' action='<?php echo $fgmembersite->getSelfScript(); ?>' method='post' accept-charset='UTF-8'>
<fieldset >
<legend>Forgot Password</legend>

<input type='hidden' name='submitted' id='submitted' value='1'/>

<div class='short_explanation'>* required fields</div>

<div><span class='error'><?php echo $fgmembersite->getErrorMessage(); ?></span></div>
<div class='container'>
    <label for='username' >UserName*:</label><br/>
    <input type='text' name='username' id='email' value='<?php echo $fgmembersite->safeDisplay('username') ?>' maxlength="50" /><br/>
    <span id='resetreq_username_errorloc' class='error'></span>
</div>
<div class='short_explanation'>
Either a you will be prompted with a security question, if provisioned, 
or a link to reset your password will be sent to the email address configured for the provided username.</div>
<div class='container'>
    <input type='submit' name='Submit' value='Submit' />
</div>

</fieldset>
</form>
<!-- client-side Form Validations:
Uses the excellent form validation script from JavaScript-coder.com-->

<script type='text/javascript'>
// <![CDATA[

    var frmvalidator  = new Validator("resetreq");
    frmvalidator.EnableOnPageErrorDisplay();
    frmvalidator.EnableMsgsTogether();

    frmvalidator.addValidation("username","req","Please provide the username used to log in.");

// ]]>
</script>

</div>
<!--
Form Code End (see html-form-guide.com for more info.)
-->

</body>
</html>