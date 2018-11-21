<?php

define('FAKE_MTURK', 1);
require_once("functions.php");

# Passed from MTurk:
# https://dl.dropboxusercontent.com/u/8859543/build/index.html?url=https%3A%2F%2Fdl.dropboxusercontent.com%2Fu%2F8859543%2Ffishpics%2FChelmonops_truncatus.jpg&assignmentId=3O6CYIULED1J2V0HZWG64GCRLFFWUU&hitId=3R16PJFTS3RR3DFR5A1JFHPKETXK4N&workerId=AKUM1FLS9WBYZ&turkSubmitTo=https%3A%2F%2Fworkersandbox.mturk.com

# workflow
# setup.php
# enroll.php (index.php?)
# redirect.php
# work page
# submit.php
# redirect.php (or nothing)

echo <<<_HTML
<!doctype html> <html> <head>
    <meta charset="utf-8">
    <style>body {font-size: 200%} input, button {font-size: 100%}; </style>
</head> <body>
</body> </html>
_HTML;

?>
