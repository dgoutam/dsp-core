<<<<<<< HEAD
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <script type="text/javascript">var NREUMQ = NREUMQ || [];
        NREUMQ.push(["mark", "firstbyte", new Date().getTime()]);</script>
    <script type="text/javascript" src="/public/assets/80309833/jquery.min.js"></script>
    <title>DreamFactory Services Platform&trade;</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="DreamFactory Software, Inc.">
    <meta name="language" content="en"/>
    <link rel="shortcut icon" href="/public/images/logo-32x32.png"/>
    <style>
        body {
            padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
        }
    </style>
    <link rel="stylesheet" type="text/css" href="/public/vendor/bootstrap/css/bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="/public/vendor/bootstrap/css/bootstrap-responsive.min.css"/>
    <link rel="stylesheet" type="text/css" href="/public/css/initial.css"/>
    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->    <!--[if lt IE 9]>
    <script type="text/javascript" src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>    <![endif]-->
</head>
<body>
<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container">

            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"> <span class="icon-bar"></span> <span
                    class="icon-bar"></span> <span class="icon-bar"></span> </a> <img id="logo-img"
                                                                                      src="/public/images/logo-48x48.png"><a
                class="brand" href="#">DreamFactory Powers Activate!</a>

            <div class="nav-collapse collapse">
                <ul class="nav"></ul>
            </div>
        </div>
    </div>
</div>
<div class="container main-content step1">
    <h2 class="headline">Activate Your New DSP!</h2>

    <p>In order to activate this DSP, you must enter your <strong>DreamFactory.com</strong> site credentials.</p>

    <p>Please enter the email address and
        password you used to register on the <strong>DreamFactory.com</strong> web site. If you have not yet registered,
        you may <a
            href="//dfnew.piuid.com/user/register"
            target="_blank">click here</a> to do so
        now.</p>

    <div class="spacer"></div>

    <form id="login-form" method="POST">
        <div class="control-group"><label for="LoginForm_username">Email Address</label>

            <div class="controls"><input id="LoginForm_username" name="LoginForm[username]" class="email required"
                                         type="text"></input></div>
        </div>
        <div class="control-group"><label for="LoginForm_password">Password</label>

            <div class="controls"><input id="LoginForm_password" name="LoginForm[password]" class="password required"
                                         type="password"></input></div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-success btn-primary">Activate!</button>
        </div>
    </form>
    <footer>
        <p>&copy; DreamFactory Software, Inc. 2013. All Rights Reserved.</p>
    </footer>
</div>
<!-- /container -->
<script src="/public/vendor/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script type="text/javascript" src="//ajax.aspnetcdn.com/ajax/jquery.validate/1.10.0/jquery.validate.min.js"></script>
<script type="text/javascript"
        src="//ajax.aspnetcdn.com/ajax/jquery.validate/1.10.0/additional-methods.min.js"></script>
<script type="text/javascript">
    /*<![CDATA[*/
    jQuery(function ($) {
        jQuery.validator.addMethod(
            "phoneUS",
            function (phone_number, element) {
                phone_number = phone_number.replace(/\s+/g, "");
                return this.optional(element) || phone_number.length > 9 && phone_number.match(/^(1[\s\.-]?)?(\([2-9]\d{2}\)|[2-9]\d{2})[\s\.-]?[2-9]\d{2}[\s\.-]?\d{4}$/);
            },
            "Please specify a valid phone number"
        );

        jQuery.validator.addMethod(
            "postalCode",
            function (postalcode, element) {
                return this.optional(element) || postalcode.match(/(^\d{5}(-\d{4})?$)|(^[ABCEGHJKLMNPRSTVXYabceghjklmnpstvxy]{1}\d{1}[A-Za-z]{1} ?\d{1}[A-Za-z]{1}\d{1})$/);
            },
            "Please specify a valid postal/zip code"
        );

        var _validator = $("form#login-form").validate({"ignoreTitle": true, "errorClass": "error", "errorPlacement": function (error, element) {
            error.appendTo(element.parent("div"));
            error.css("margin", "-10px 0 0");
        }, "error_placement": function (error, element) {
            error.appendTo(element.parent("div"));
        }, "highlight": function (element, errorClass) {
            $(element).closest('div.control-group').addClass('error');
            $(element).addClass(errorClass);
        }, "unhighlight": function (element, errorClass) {
            $(element).closest('div.control-group').removeClass('error');
            $(element).removeClass(errorClass);
        }});
    });
    /*]]>*/
</script>
</html>
=======
<?php
/**
 * BE AWARE...
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
 * @var $this  SiteController
 * @var $model LoginForm
 */
use Kisma\Core\Utility\Bootstrap;

Validate::register(
	'form#login-form',
	array(
		'ignoreTitle'    => true,
		'errorClass'     => 'error',
		'errorPlacement' => 'function(error,element){error.appendTo(element.parent("div"));error.css("margin","-10px 0 0");}',
	)
);
?>
<h2 class="headline">Activate Your New DSP!</h2>
<p>In order to activate this DSP, you must enter your <strong>DreamFactory.com</strong> site credentials.</p><p>Please enter the email address and
	password you used to register on the <strong>DreamFactory.com</strong> web site. If you have not yet registered, you may <a
		href="https://www.dreamfactory.com/user/register"
		target="_blank">click here</a> to do so
	now.</p>
<div class="spacer"></div>
<form id="login-form" method="POST">
	<?php
	echo '<div class="control-group">' . Bootstrap::label( array('for' => 'LoginForm_username'), 'Email Address' );
	echo '<div class="controls">' . Bootstrap::text( array('id' => 'LoginForm_username', 'name' => 'LoginForm[username]', 'class' => 'email required') ) .
		'</div></div>';
	echo '<div class="control-group">' . Bootstrap::label( array('for' => 'LoginForm_password'), 'Password' );
	echo '<div class="controls">' .
		Bootstrap::password( array('id' => 'LoginForm_password', 'name' => 'LoginForm[password]', 'class' => 'password required') ) . '</div></div>';
	?>

	<div class="form-actions">
		<button type="submit" class="btn btn-success btn-primary">Activate!</button>
	</div>
</form>
>>>>>>> 0302f7c94ce152ba0e610393161320d616f81950
