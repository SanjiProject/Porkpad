<?php
require_once 'classes/User.php';

$user = new User();
$user->logout();

// Redirect to home page
header("Location: index.php");
exit;
?>

