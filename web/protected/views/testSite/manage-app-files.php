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

// User wishes to delete something
if (isset($_GET['Delete'])) {
    $results = $fgmembersite->deleteAppFile($appname, $_GET['Delete']);
}

// Get all the guest book entries for display
$entries = $fgmembersite->getAppFiles($appname);
$entries = $entries['file'];

function printbool($val)
{
    $val = mb_strtolower($val);
    switch ($val) {
    case '1':
    case 'true':
    case 'on':
    case 'yes':
    case 'y':
        return 'true';
    default: 
        return 'false';
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Manage Application Files</title>
    <link href="style/main.css" rel="stylesheet" type="text/css" />
   
</head>
<body>
     <div class="general">
        <div class="title">
            <h1>
                Application Files
            </h1>
        </div>        
                
        <div><span class='error'><?php echo $fgmembersite->getErrorMessage(); ?></span></div>
        <div id="theResults">
        
            <div id="UpdatePanel" >
                <table id="gbEntryTable">
                    <tr>
                        <th>File Name</th><th>Last Modified</th>
                    </tr>
                    <?php
                        foreach($entries AS $e) {
                            echo "\n<tr>";
                            echo "\n\t<td><strong>" . $e['name'] . "</strong><br/><a href=\"?App=$appname&Delete=" . $e['name'] . "\">Delete</a></td>";
                            echo "\n\t<td>" . $e['lastModified'] . "</td>";
                            echo "\n</tr>";
                        }
                    ?>
                </table>
            </div><!-- update panel -->

        </div>
         
    </div>
    <br><br><br>
    <p><a href='add-app-files.php?App=<?php echo $appname; ?>'>Add or update files for <?php echo $appname; ?></a></p>
    <p><a href='manage-apps.php'>Manage Apps</a></p>
    <p><a href='admin-login-home.php'>Home</a></p>
    <p><a href='logout.php'>Logout</a></p>
    </body>
</html>
