<?php
require_once("./include/Membership.php");

$fgmembersite = new Membership();
if(!$fgmembersite->checkLogin())
{
    $fgmembersite->redirectToURL("login.php");
    exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
      <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
      <title>Home page</title>
      <link rel="STYLESHEET" type="text/css" href="style/fg_membersite.css">
</head>
<body>
<div id='fg_membersite_content'>
<h2>Admin Home Page</h2>
Welcome back <?php echo $fgmembersite->userFullName(); ?>!

<p><a href='change-pwd.php'>Change password</a></p>
<p><a href='manage-users.php'>Manage Users</a></p>
<p><a href='manage-apps.php'>Manage Apps</a></p>
<p><a href='environment.php'>Display Environment</a></p>
<br><br><br>
<p><a href='logout.php'>Logout</a></p>
</div>
</body>
</html>
