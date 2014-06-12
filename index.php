<?php

define('FAKE_MTURK', 1);
require_once("functions.php");

# comment this out in production, lol
error_reporting(E_ALL|E_STRICT);

# Passed from MTurk:
# https://dl.dropboxusercontent.com/u/8859543/build/index.html?url=https%3A%2F%2Fdl.dropboxusercontent.com%2Fu%2F8859543%2Ffishpics%2FChelmonops_truncatus.jpg&assignmentId=3O6CYIULED1J2V0HZWG64GCRLFFWUU&hitId=3R16PJFTS3RR3DFR5A1JFHPKETXK4N&workerId=AKUM1FLS9WBYZ&turkSubmitTo=https%3A%2F%2Fworkersandbox.mturk.com

# comment this out later, lol
#header('Content-type: text/plain');

if (!isset($_GET['p'])) {
    die("No trial set.");
}

$path = $_GET['p'];
$trial = preg_replace('/\/mturk\/externalSubmit$/', '', $path);
$trial = preg_replace("/\W/", '', $trial);

if (!preg_match('/^\w+$/', $trial)) {
    die('bad trial specified');
}

$trials = parse_ini_file('config.ini', true, INI_SCANNER_RAW);
if (!isset($trials[$trial])) {
    die("trial doesn't exist in config");
}

$current = $trials[$trial];

msg_array($_GET, 'GET: ');
msg_array($_POST, 'POST: ');

# Instantiate new SQLite connection.
$db = new SQLitePDO(preg_replace("/\W/", '', $trial));

if (isset($_POST['assignmentId'])) {
    # This is an MTurk postback to us. Unmangle the sequence ID.
    preg_match("/(\w+)__(\d+)/", $_POST['assignmentId'], $matches);
    unset($_POST['assignmentId']);
    list(, $assignment, $sequence) = $matches;
    $answers = json_encode($_POST);

    $db->add_result($assignment, $answers, $sequence);
} else if (isset($_GET['hitId']) && isset($_GET['assignmentId']) && isset($_GET['workerId']) && isset($_GET['turkSubmitTo'])) {
    # This is an initial MTurk assignment. So we need to potentially generate a new plan.
    # sanitize all inputs
    $worker = preg_replace("/\W/", '', $_GET['workerId']);
    $assignment = preg_replace("/\W/", '', $_GET['assignmentId']);
    $hit = preg_replace("/\W/", "", $_GET['hitId']);
    # Sanitize URL based on RFC 3986
    $submitto = preg_replace("/[^\w\d.~!*'();:@&=+$,\/%#?[\]\-]/", "", $_GET['turkSubmitTo']);

    $params = parse_tsv_file($current['file']);

    # Is there a plan available?
    $plan = $db->get_plan($assignment);
    if (!$plan) {
        # We don't have a plan, so generate a plan.
        $shuffled = array_slice_assoc($params, array_rand($params, $current['count']));
        $plan = array_repeat($shuffled, $current['replicates']);

        $db->add_plan($assignment, $trial, $hit, $worker, $submitto, $plan);
    }
} else {
    die("assignment id not set");
}

# Direct people to the next step.

# Fetch the plan that should exist.
if (!isset($plan)) {
    $plan = $db->get_plan($assignment);
}

$n_plan = count($plan);

# If we don't have workerId etc. information, fetch this from the database.
list($worker, $hit, $submitto) = $db->get_assignment_info($assignment);

# Initialize empty "content" variables.
$head = $body = '';

$next_plan_seq = $db->next_sequence_id($assignment);

if (array_key_exists($next_plan_seq, $plan)) {
    $next_plan = (array) $plan[$next_plan_seq];
    # Mangle the sequence ID with the assignment ID because (1) that is
    # the only parameter guaranteed to be POSTed back to us, and (2) we
    # would otherwise need to track which is the "active" subassignment
    # that has been farmed out. This method is thus resistant to e.g.,
    # the user pressing the back button and resubmitting a stale task.
    $qsa = array(
        'assignmentId' => "${assignment}__$next_plan_seq",
        'hitId' => $hit,
        'workerId' => $worker,
        'turkSubmitTo' => "http://$_SERVER[HTTP_HOST]/$trial"
    );
    $qsa = array_merge($qsa, $next_plan);
    $qst = http_build_query($qsa);
    $next_url = "$current[endpoint]?$qst";
    $db->append_log($assignment, $next_plan_seq, 'assigned', null);
    # Use a meta refresh so people can watch their progress.
    $head .= '<meta http-equiv="refresh" content="2; url=' . htmlspecialchars($next_url, ENT_QUOTES) . '">';
    $body .= "Assigning task $next_plan_seq of $n_plan, redirecting you in 2 seconds...<br>";
    $body .= '<a href="' . htmlspecialchars($next_url, ENT_QUOTES) . '">click here if you are not redirected</a>';
} else {
    # Nothing left in our plan, so let's batch it up and post it to Amazon.
    $allresults = $db->get_all_results($assignment);

    # Check that the submit URL goes to the right place.
    if (!preg_match('/\/mturk\/externalSubmit$/', $submitto)) {
        $submitto .= '/mturk/externalSubmit';
    }

    $body .= "All $n_plan tasks complete!";
    $body .= '<form method="post" action="' . htmlspecialchars($submitto, ENT_QUOTES) . '">';
    $body .= '<input type="hidden" name="allresults" value="' . htmlspecialchars($allresults, ENT_QUOTES) . '">';
    $body .= '<input type="hidden" name="assignmentId" value="' . htmlspecialchars($assignment, ENT_QUOTES) . '">';
    $body .= '<input type="submit" value="Submit my results">';
}


echo <<<_HTML
<!doctype html> <html> <head>
    <meta charset="utf-8">
    <style>body {font-size: 200%} input, button {font-size: 100%}; </style>
    $head
</head> <body>
$body
</body> </html>
_HTML;

?>
