<?php
define('FAKE_MTURK', 1);
require_once("functions.php");

# Sets up a trial. Posted from setup.html
$trial = preg_replace("/\W/", '', $_POST['trial']);

if (!preg_match('/^\w+$/', $trial)) {
    die('bad trial specified');
}

$replicates = intval($_POST['replicates']);

if ($replicates <= 0) {
    die('bad replicates specified');
}

$params = parse_tsv_file($trial . ".input");

if (empty($params)) {
    die('no input file for trial');
}

# Sanitize URL based on RFC 3986
$endpoint = preg_replace("/[^\w\d.~!*'();:@&=+$,\/%#?[\]\-]/", "", $_POST['endpoint']);
if (!$endpoint) {
    die('please specify an endpoint');
}


# Instantiate new SQLite connection.
$db = new SQLitePDO($trial);

# For each HIT, generate a unique HIT ID

# Make it more unique by making a salt.
$salt = $trial . time();

foreach ($params as $row) {
    # Hash against the parameters. This is an implementation detail and
    # should not be used to verify any particular task.
    $hitid = sha1($salt . serialize($row));
    # Add each HIT into the database.
    $db->add_hit($hitid, $replicates, $endpoint, $row);
}

# We merely insert new rows, and don't truncate the database, in case
# there are results stored that haven't been saved. So report the total
# number of items in case the administrator wants to delete them later.
$count = count($params);
$db_counts = $db->count_hits();

$url = "$_SERVER[HTTP_HOST]/enroll.php?p=$trial";

echo <<<_HTML
<!doctype html><html><head>
<meta charset="utf-8">
<style>body {font-size: 200%} input, button{font-size: 100%};</style>
</head>
<body>
Successfully added $count HITs. Database now holds $db_counts[0] HITs, $db_counts[1] unique HITs.
<p>
Enroll users with this link:
<a href="//$url">$url</a>
</form>
_HTML;

?>
