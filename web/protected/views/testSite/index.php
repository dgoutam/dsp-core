<?php
require_once("./include/Membership.php");
use CloudServicesPlatform\Utilities\Config;

$fgmembersite = new Membership();
if ('ready' !== $fgmembersite->getState())
{
    $fgmembersite->redirectToURL("init-login.php");
    exit;
}

$label = Config::getConfigValue('CompanyLabel');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
      <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
      <title><?php echo $label; ?></title>
      <link rel="STYLESHEET" type="text/css" href="style/fg_membersite.css">
</head>
<body>
<div id='fg_membersite_content'>
<h2>Welcome to <?php echo $label; ?></h2>
<ul>
<li><a href='login.php'>Test Login</a></li>
<?php
    if ('true' == Config::getConfigValue('AllowOpenRegistration'))
        echo "<li><a href='register.php'>Register</a> as a new user</li>";
        echo "<li><a href='confirmreg.php'>Confirm</a> an earlier registration</li>";
?>
</ul>
</div>
</body>
</html>
