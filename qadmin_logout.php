<?php
require_once "db.php";
session_destroy();
header("Location: qadmin_login.php");
