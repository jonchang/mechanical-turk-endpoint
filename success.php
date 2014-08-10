<?php

# simple success script

define('FAKE_MTURK', 1);
require_once("config.php");

$subject = "Turk submission";
if (!empty($_POST['workerId'])) {
    $worker = filter_var($_POST["workerId"], FILTER_SANITIZE_EMAIL);
    $subject .= " from $worker";
} else {
    $worker = "anonymous";
}
$hash = md5(date('r', time())) . "XXXXXXXX";
$headers = "From: Fake MTurk Robot <$from_email>";
$headers .= "\r\nContent-Type: multipart/mixed; boundary=$hash";
$results = chunk_split(base64_encode($_POST['__allresults']));
$plan = chunk_split(base64_encode($_POST['__plan']));
$output = <<<_EMAIL
--$hash
Content-Type: text/plain; charset=UTF-8

Dear requester,

A turker's completed work is attached to this email.

Assignment ID: ${_POST['assignmentId']}
Worker ID: $worker
HIT ID: ${_POST['hitId']}

-- 
I'm a robot, lol

--$hash
Content-Type: application/json;
  name=$worker.json
Content-Transfer-Encoding: base64
Content-Disposition: attachment;
  filename=$worker.json

$results

--$hash
Content-Type: application/json;
  name=${worker}_plan.json
Content-Transfer-Encoding: base64
Content-Disposition: attachment;
  filename=${worker}_plan.json

$plan

--$hash--

_EMAIL;

$res = mail($email, $subject, $output, $headers);
if ($res) {
    echo "Submission successful! Thanks!!";
} else {
    $echostr = <<<HTML
Oh noe! Something went wrong; please <a href="data:application/json;base64,%s" download='%s.json'>save this file</a> and email it to the study requester, or refresh the page and try to resubmit!";
HTML;
    printf($echostr, base64_encode($_POST['__allresults']), $worker);
}

?>
