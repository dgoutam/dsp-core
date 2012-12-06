<?php
require_once("./include/Membership.php");

$fgmembersite = new Membership();
if(isset($_POST['submitted'])) {
    if ($fgmembersite->securityAnswer()) {
        $fgmembersite->redirectToURL("change-pwd.php");
        exit;
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
      <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
      <title>Security Question</title>
      <link rel="STYLESHEET" type="text/css" href="style/fg_membersite.css" />
      <script type='text/javascript' src='scripts/gen_validatorv31.js'></script>
</head>
<body>
<!-- Form Code Start -->
<div id='fg_membersite'>
<form id='resetreq' action='<?php echo $fgmembersite->getSelfScript(); ?>' method='post' accept-charset='UTF-8'>
<fieldset >
<legend>Please answer the following security question...</legend>

<input type='hidden' name='submitted' id='submitted' value='1'/>

<div class='short_explanation'>* required fields</div>

<div><span class='error'><?php echo $fgmembersite->getErrorMessage(); ?></span></div>
<div class='container'>
    <label for='answer' ><?php echo $question; ?></label><br/>
    <input type='text' name='answer' id='answer' value='<?php echo $fgmembersite->safeDisplay('answer') ?>' maxlength="80" /><br/>
    <span id='resetreq_answer_errorloc' class='error'></span>
</div>
<div class='short_explanation'>
If answered correctly, your password will be reset and must be changed to regain access to your account.</div>
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