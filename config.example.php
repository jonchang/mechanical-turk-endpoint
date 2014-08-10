<?php

if (!defined('FAKE_MTURK')) {
    die("This is not a valid entry point.");
}

$email = "myemail@example.com";
$from_email = "no-reply@example.com";

$trials["test"] = array(
    "file" => "test.input",
    "count" => 2,
    "replicates" => 2,
    "endpoint" => "http://example.com/",
);
