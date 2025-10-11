<?php
require __DIR__ . '/config/auth.php';

session_unset();
session_destroy();

header('Location: profile.php');
exit;
