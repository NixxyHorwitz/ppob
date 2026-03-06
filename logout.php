<?php
$timeout = 86400;
ini_set('session.gc_maxlifetime', $timeout);
session_set_cookie_params($timeout);
session_start();
session_unset();
session_destroy();

header("Location: index");
exit();