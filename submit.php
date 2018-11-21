<?php

define('FAKE_MTURK', 1);
require_once("functions.php");

# Access the database and input file to figure out
$protected = explode(' ', 'p workerId assignmentId hitId turkSubmitTo');
$result = json_encode(array_diff_key($_POST, array_flip($protected)));

$trial = preg_replace('/\/mturk\/externalSubmit$/', '', $_GET['p']);
$trial = preg_replace("/\W/", '', $trial);
$worker = preg_replace("/\W/", '', $_POST['workerId']);
$assignment = preg_replace("/\W/", '', $_POST['assignmentId']);
$hit = preg_replace("/\W/", '', $_POST['hitId']);


$db = new SQLitePDO(preg_replace("/\W/", '', $trial));

$db->add_result($assignment, $worker, $result);

header("Location: redirect.php?p=$trial&worker=$worker", true, 303);
exit();

?>
