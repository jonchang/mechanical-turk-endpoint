<?php

function parse_config_file ($filename) {
    $trials = array();
    if (($handle = fopen('config.txt', 'r') !== false) {
        # Name, Path, How many iterations, how many replicates
        # Total number of hits will be #iterations * #replicates
        while (($row = fgetcsv($handle, 1000, "\t")) !== false) {
            $trials[$row[0]] = array(
                'loc' => realpath($row[1]),
                'cnt' => intval($row[2]),
                'rep' => intval($row[3]),
            );
        }
        return $trials;
    }
    return false;
}

$trials = parse_config_file("config.txt");

if (isset($_GET['trial']) and isset($trials[$_GET['trial']])) {
    $current = $trials[$_GET['trial']];
    echo file_get_contents($current['loc']);
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
