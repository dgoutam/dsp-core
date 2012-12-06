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

if (isset($_POST['submitted'])) {
    if ($fgmembersite->registerApp()) {
        $fgmembersite->redirectToURL("manage-apps.php");
        exit;
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Add an Application</title>
        <link href="style/main.css" rel="stylesheet" type="text/css" />
        <link href="style/fg_membersite.css" rel="stylesheet" type="text/css" />
       
    </head>
    <body>
        <!-- Form Code Start -->
        <div id='fg_membersite'>
        <form id='app' action='<?php echo $fgmembersite->getSelfScript(); ?>' method='post' accept-charset='UTF-8' enctype="multipart/form-data">
            <fieldset >
                <legend>Add Application</legend>

                <input type='hidden' name='submitted' id='submitted' value='1'/>

                <div class='short_explanation'>* required fields</div>
                <input type='text'  class='spmhidip' name='<?php echo $fgmembersite->getSpamTrapInputName(); ?>' />

                <div><span class='error'><?php echo $fgmembersite->getErrorMessage(); ?></span></div>
                <div class='container'>
                    <label for='label' >Label (Displayed Name): *</label><br/>
                    <input type='text' name='label' id='name' value='<?php echo $fgmembersite->safeDisplay('label') ?>' maxlength="40" /><br/>
                    <span id='register_label_errorloc' class='error'></span>
                </div>
                <div class='container'>
                    <label for='name' >Name (Root Directory Name): *</label><br/>
                    <input type='text' name='name' id='name' value='<?php echo $fgmembersite->safeDisplay('name') ?>' maxlength="32" /><br/>
                    <span id='register_name_errorloc' class='error'></span>
                </div>
                <div class='container'>
                    <label for='desc' >Description: </label><br/>
                    <textarea name="desc" rows="2" cols="20" id="MessageTextBox" class="field" maxlength="128" ><?php echo $fgmembersite->safeDisplay('desc') ?></textarea>
                    <span id='register_desc_errorloc' class='error'></span>
                </div>
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

<!-- client-side Form Validations:
Uses the excellent form validation script from JavaScript-coder.com-->

<script type='text/javascript'>
// <![CDATA[
    var frmvalidator  = new Validator("app");
    frmvalidator.EnableOnPageErrorDisplay();
    frmvalidator.EnableMsgsTogether();
    frmvalidator.addValidation("name","req","Please provide your app name");

    frmvalidator.addValidation("label","req","Please provide your app label");

    frmvalidator.addValidation("name","alnum_u","Please provide a valid app name with alpha-numeric and underscores");

// ]]>
</script>

<!--
Form Code End (see html-form-guide.com for more info.)
-->

        <br><br><br>
        <p><a href='manage-apps.php'>Back to Apps</a></p>
        <p><a href='admin-login-home.php'>Home</a></p>
    </body>
</html>
