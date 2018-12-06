<?php

define('FAKE_MTURK', 1);
require_once('functions.php');

if (!isset($_GET['p'])) {
    die('No trial set.');
}

$path = $_GET['p'];
$trial = preg_replace("/\W/", '', $path);

if (!preg_match('/^\w+$/', $trial)) {
    die('bad trial specified');
}


$db = new SQLitePDO($trial);
$res = $db->get_all_results();
$db = null; // close connection

$files = array();

foreach ($res as $result) {
    $img = imagecreatefromjpeg($result["url"]);
    $m1 = $result["marks"]["1"];
    $m2 = $result["marks"]["2"];
    $box = $result["marks"]["B"];
    $distance = sqrt(($m1[0] - $m2[0]) ** 2 + ($m1[1] - $m2[1]) ** 2);
    $boxwidth = $distance * 0.125;
    $img2 = imagecrop($img, array('x' => $box[0] - $boxwidth / 2, 'y' => $box[0] - $boxwidth / 2, 'width' => $boxwidth, 'height' => $boxwidth));
    ob_start();
    imagejpeg($img2);
    $bin = ob_get_clean();
    $b64 = base64_encode($bin);
    $files[] = array('filename' => $result["assignment"], 'image' => $bin);
}

$zipname = $tmpfname = tempnam(sys_get_temp_dir(), 'FOO');
$zip = new ZipArchive();
$zip->open($zipname, ZipArchive::OVERWRITE);

foreach ($files as $file) {
    $zip->addFromString($file["filename"] . '.jpg', $file["image"]);
}

$zip->addFromString("manifest.json", json_encode($res));
$zip->close();

header('Content-Type: application/zip');
header('Content-Length: ' . filesize($zipname));
header('Content-Disposition: attachment; filename="' . $trial . '.zip"');

readfile($zipname);
unlink($zipname);

?>
