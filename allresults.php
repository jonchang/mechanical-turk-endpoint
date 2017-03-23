<?php

# script to get all results for a given trial

define('FAKE_MTURK', 1);
require_once("functions.php");
require_once("config.php");

if (!isset($_GET['p'])) {
    die("No trial set.");
}

$path = $_GET['p'];
$trial = preg_replace('/\/mturk\/externalSubmit$/', '', $path);
$trial = preg_replace("/\W/", '', $trial);

if (!preg_match('/^\w+$/', $trial)) {
    die('bad trial specified');
}

if (!isset($trials[$trial])) {
    die("trial doesn't exist in config");
}

$current = $trials[$trial];

$db = new SQLitePDO(preg_replace("/\W/", '', $trial));

$db->exec1('SELECT workerId, sequence, result FROM log NATURAL JOIN plan WHERE type="completed" GROUP BY workerId', array(), PDO::FETCH_ASSOC);



?>
