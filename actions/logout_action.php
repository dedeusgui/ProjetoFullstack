<?php
require_once '../config/bootstrap.php';
bootApp(false);

session_unset();
session_destroy();

actionRedirect('../public/login.php');
