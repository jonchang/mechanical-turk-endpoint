<?php

define('FAKE_MTURK', 1);
require_once("functions.php");

# Simple form to allow workers to specify their own worker IDs

$trial = preg_replace("/\W/", '', $_GET['p']);

if (!$trial) {
    die("No trial set");
}

$db = new SQLitePDO(preg_replace("/\W/", '', $trial));

$num_plans = $db->count_hits();
if ($num_plans[0] <= 0) {
    die('no plans in trial, please run setup');
}

$url = "//$_SERVER[HTTP_HOST]/redirect.php?p=$trial";

echo <<<_HTML
<!doctype html><html><head>
<meta charset="utf-8">
<style>body {font-size: 200%} input, button{font-size: 100%};</style>
</head>
<body>
$trial experiment: Please enter your email address (or other unique identifier).
<form method="get" action="$url">
<input type="text" name="workerId"></input>
<input type="hidden" name="p" value="$trial">
<input type="submit">
</form>
_HTML;

