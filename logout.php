<?php
// Start session only if it's not already active
if (php_sapi_name() !== 'cli') {
	if (function_exists('session_status')) {
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
	} else {
		if (!isset($_SESSION)) {
			session_start();
		}
	}
}

// Clear and destroy session
if (isset($_SESSION)) {
	$_SESSION = [];
}
if (function_exists('session_unset')) {
	@session_unset();
}
if (function_exists('session_destroy')) {
	@session_destroy();
}

header("Location: index.php");
exit();
?>
