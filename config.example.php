<?php

if (!defined('FAKE_MTURK')) {
    die("This is not a valid entry point.");
}

$trials["test"] = array(
    "file" => "test.input",
    "count" => 2,
    "replicates" => 2,
    "endpoint" => "http://example.com/",
);
