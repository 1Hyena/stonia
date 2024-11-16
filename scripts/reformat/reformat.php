<?php

require('../db.php');

if (count($argv) <= 1) {
    exit("usage: ".$argv[0]." path-to-areas\n");
}

$areapath = $argv[1];
$files = array();

if (is_dir($areapath)) {
    $listpath = $areapath."/area.lst";

    if (!file_exists($listpath)) {
        echo($listpath." does not exist\n");

        $files = glob($areapath."/*.are");
    }
    else {
        $files = explode("\n", file_get_contents($listpath));

        for ($file_index = 0; $file_index < count($files); ++$file_index) {
            if ($files[$file_index] === "$"
            || !strlen($files[$file_index])) {
                $files[$file_index] = "";
            }
            else {
                $files[$file_index] = $areapath."/".$files[$file_index];
            }
        }
    }
}
else {
    if (!file_exists($areapath)) {
        exit('invalid diretory path: '.$areapath."\n");
    }

    $files[] = $areapath;
}

for ($file_index = 0; $file_index < count($files); ++$file_index) {
    if (!strlen($files[$file_index])) {
        continue;
    }

    if (!file_exists($files[$file_index])) {
        echo("area not found: ".basename($files[$file_index])."\n");
        $files[$file_index] = "";
    }
}

$files = array_values(array_filter($files, 'strlen'));

echo "found ".count($files)." areas\n";

$rooms = array();
$areas = array();

for ($file_index = 0; $file_index < count($files); ++$file_index) {
    $filename = basename($files[$file_index]);

    echo "loading ".$filename."\n";

    $data = load($files[$file_index], $STONIA);

    if ($data === null) {
        echo "failed to load ".$filename."\n";
        continue;
    }

    echo "saving ".$filename."\n";

    save($filename, $data);
}
