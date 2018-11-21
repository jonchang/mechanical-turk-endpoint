<?php

define('FAKE_MTURK', 1);
require_once("functions.php");

# Access the database and input file to figure out

$trial = preg_replace("/\W/", '', $_GET['p']);
$worker = preg_replace("/\W/", '', $_GET['workerId']);
$db = new SQLitePDO(preg_replace("/\W/", '', $trial));

$res = $db->assign_work($worker);

var_dump($res);
exit();

$assignment = $res['assignmentId'];
$hit = $res['hitId'];
$endpoint = $res['endpoint'];
$payload = $res['list'];

# Bail out if for some reason the input file has an assignmentId or a hitId as a parameter!
if (array_key_exists('hitId', $payload) or array_key_exists('assignmentId', $payload) or array_key_exists('workerId', $payload)) {
    die('duplicate hit or assignment array key in json payload');
}

# Load up the http query payload
$payload['assignmentId'] = $assignment;
$payload['workerId'] = $worker;
$payload['hitId'] = $hit;
$payload['turkSubmitTo'] = "http://$_SERVER[HTTP_HOST]/submit.php?p=$trial";

$url = $endpoint . '?' . http_build_query($payload);

header('Location: ' . $url, true, 303);
exit();

?>
