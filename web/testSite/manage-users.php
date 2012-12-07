<?php
require_once("./include/Membership.php");

$fgmembersite = new Membership();
if (!$fgmembersite->checkLogin())
{
    $fgmembersite->redirectToURL("login.php");
    exit;
}

if (!$fgmembersite->userIsAdmin())
{
    echo 'DENIED ACCESS: Only administrator accounts are allowed access to this page.' . "<br />";
    echo "<p><a href='login-home.php'>Home</a></p>";
    exit;
}

// User wishes to delete something
if (isset($_GET['Delete'])) {
    $fgmembersite->deleteUser($_GET['Delete']);
}

// Get all the guest book entries for display
$entries = $fgmembersite->getUsers();

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
    <title>Users</title>
    <link href="style/main.css" rel="stylesheet" type="text/css" />
   
</head>
<body>
     <div class="general">
        <div class="title">
            <h1>
                Users
            </h1>
        </div>
                
        <div><span class='error'><?php echo $fgmembersite->getErrorMessage(); ?></span></div>
        <div id="theResults">
        
            <div ID="UpdatePanel" >
                <table id="gbEntryTable">
                    <tr>
                        <th>Full Name</th><th>UserName</th><th>Email</th><th>Phone</th>
                        <th>IsActive</th><th>IsAdmin</th><th>RoleIds</th>
                        <th>Created Date</th><th>Last Modified Date</th>
                    </tr>
                    <?php
                        foreach($entries AS $e) {
                            echo "\n<tr>";
                            echo "\n\t<td><strong>" . $e['FullName'] . "</strong><br/><a href=\"?Delete=" . $e['Id'] . "\">Delete</a></td>";
                            echo "\n\t<td>" . $e['UserName'] . "</td><td>" . $e['Email'] . "</td><td>" . $e['Phone'] . "</td>";
                            echo "\n\t<td>" . printbool($e['IsActive']) . "</td><td>" . printbool($e['IsSysAdmin']) . "</td>";
                            echo "\n\t<td>" . $e['RoleIds'] . "</td><td>" . $e['CreatedDate'] . "</td><td>" . $e['LastModifiedDate'] . "</td>";
                            echo "\n</tr>";
                        }
                    ?>
                </table>
            </div><!-- update panel -->
            
        </div>
            
    </div>
    <br><br><br>
    <p><a href='admin-login-home.php'>Home</a></p>
    <p><a href='logout.php'>Logout</a></p>
   </body>
</html>
