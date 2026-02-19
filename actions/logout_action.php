<?php
require_once '../config/bootstrap.php';
bootApp(false);

session_unset();
session_destroy();

header('Location: ../public/login.php');
exit;
