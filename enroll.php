<?php

define('FAKE_MTURK', 1);
require_once("functions.php");
require_once("config.php");

# Simple form to allow workers to specify their own worker IDs

if (!isset($_GET['p'])) {
    die("No trial set");
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

$url = "http://$_SERVER[HTTP_HOST]/$trial";

echo <<<_HTML
<!doctype html><html><head>
<meta charset="utf-8">
<style>body {font-size: 200%} input, button{font-size: 100%};</style>
</head>
<body>
$trial experiment: Please enter your email address.
<form method="get" action="$url">
<input type="text" name="workerId"></input>
<input type="submit">
</form>
_HTML;

