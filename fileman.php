<?php 
	require('api/dbconn.php');
	require('api/functions.php');

	if (!isset($_FILES['f'])) {
		header("HTTP/1.1 400 Bad Request");
		die("No file given?");
	}

	$retval = "1";

	$expireAfterDownload = '0';
	if (isset($_GET['expireAfterDownload']) && $_GET['expireAfterDownload'] == 'true') {
		$expireAfterDownload = '1';
	}

	if (empty($_GET['secret'])) {
		if ($_SERVER['HTTP_USER_AGENT'] == 'cli' || $_SERVER['HTTP_USER_AGENT'] == 'dro.pm-androidapp') {
			list($secret, $key) = allocate();
			$retval = "$secret dro.pm/$key";
		}
	}
	else {
		$secret = $db->escape_string($_GET['secret']);
		
		$key = $db->query('SELECT `key` FROM `shorts` WHERE `secret` = "' . $secret . '"') or die('Database error 3984');
		if ($key->num_rows != 1) {
			die("?secret=$secret not found");
		}
		$key = $key->fetch_row()[0];
	}

	$success = move_uploaded_file($_FILES['f']['tmp_name'], 'uploads/' . $key);
	if (!$success) {
		die('Error moving file.<script>alert("Error 500 uploading file.");</script>');
	}

	$data = array('original_filename' => $_FILES['f']['name'], 'filename' => $key);
	$data = $db->escape_string(serialize($data));
	$db->query('UPDATE `shorts` SET `expires` = ' . (time() + (12*3600)) . ', `type` = 2, `value` = "' . $data . '", expireAfterDownload = ' . $expireAfterDownload . ' WHERE `secret` = "' . $secret . '"') or die('Database error 29348');

	die($retval);

