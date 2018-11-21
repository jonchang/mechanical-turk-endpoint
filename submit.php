<?php

define('FAKE_MTURK', 1);
require_once("functions.php");

# Access the database and input file to figure out
$protected = explode(' ', 'assignmentId hitId turkSubmitTo');
$result = json_encode(array_diff_key($_POST, array_flip($protected)));

$trial = preg_replace('/\/mturk\/externalSubmit$/', '', $_GET['p']);
$trial = preg_replace("/\W/", '', $trial);
$assignment = preg_replace("/\W/", '', $_POST['assignmentId']);
$hit = preg_replace("/\W/", '', $_POST['hitId']);


$db = new SQLitePDO(preg_replace("/\W/", '', $trial));

$db->add_result($assignment, $result);
$worker = $db->get_assigned_worker($assignment);

if (empty($worker)) {
    die "Couldn't find matching worker ID for assignment ID = $assignment>. Your result has been recorded but please contact the organizer with this assignment number.";
}

header("Location: redirect.php?p=$trial&workerId=$worker", true, 303);
exit();

?>
