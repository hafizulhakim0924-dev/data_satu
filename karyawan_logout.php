<?php
session_start();
session_destroy();
header("Location: karyawan_login.php");
exit;
?>

