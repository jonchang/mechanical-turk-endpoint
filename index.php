<?php

header('Content-type: text/plain');

function parse_tsv_file ($filename) {
    $rows = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $result = array();
    if ($rows) {
        $columns = explode("\t", array_shift($rows));
        foreach ($rows as $row) {
            $split = explode("\t", $row);
            $result[] = array_combine($columns, explode("\t", $row));
        }
    }
    return $result;
}

# Passed from MTurk:
# www.example.com/?hitId=2384239&assignmentId=ASD98ASDFADJKH&workerId=ASDFASD8

$trials = parse_ini_file("config.ini", true, INI_SCANNER_RAW);

if (isset($_GET['hitId']) && isset($_GET['assignmentId']) && isset($_GET['workerId'])) {
    # this is an Mturk assignment
    # sanitize workerId
    print_r(preg_replace('/\W/', "_", $_GET['workerId']));
    # log data (sqlite?)
}


if (isset($_GET['trial']) and isset($trials[$_GET['trial']])) {
    $current = $trials[$_GET['trial']];
    $params = parse_tsv_file($current['file']);
    $to_redirect = $current['endpoint'] . '?' . http_build_query($params[0]);
} else {
    echo "No trial set.";
}

echo <<< _HTML
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title></title>
</head>
<body>
</body>
</html>
_HTML;
?>
