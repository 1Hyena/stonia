<?php

require('../db.php');

if (count($argv) <= 1) {
    exit("usage: ".$argv[0]." path-to-areas\n");
}

$areapath = $argv[1];

if (!is_dir($areapath)) {
    exit('invalid diretory path: '.$areapath."\n");
}

$listpath = $areapath."/area.lst";
$files = array();

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

    echo "parsing ".$filename."\n";

    $data = load($files[$file_index], $STONIA);

    if ($data === null || $data["area"] === null) {
        continue;
    }

    $name_parts = explode("(", $data["area"]["name"]);
    $area_title = trim($name_parts[0]);
    $area_rooms = $data["rooms"];

    if (is_string($area_rooms)) {
        exit($filename.": ROOMS: ".$value."\n");
    }

    $collisions = vnums_to_dictionary($area_rooms, $rooms);

    if (is_string($collisions)) {
        exit($filename.": ROOMS: ".$collisions."\n");
    }

    foreach ($collisions as $vnum=>$value) {
        echo($filename.": ROOMS: duplicate vnum #".$vnum."\n");
    }

    $area_exits = array();
    $area_vnums = array();

    $vnum_dictionary = array();
    vnums_to_dictionary($area_rooms, $vnum_dictionary);

    $area_rooms = $vnum_dictionary;

    foreach ($area_rooms as $vnum=>$room) {
        $area_vnums[] = $vnum;

        if (!count($room["exits"])) {
            if (!is_set($room["flags"], "ROOM_DEATH", $STONIA['room_flags'])) {
                echo("no exits in room #".$vnum." (".$filename.")\n");
            }

            continue;
        }

        foreach ($room["exits"] as $index=>$exit) {
            $to_vnum = $exit["to_room"];

            if (array_key_exists($to_vnum, $area_rooms)) {
                continue;
            }

            $area_exits[$to_vnum] = null;
        }
    }

    for ($j = 0; $j<count($area_vnums); ++$j) {
        $rooms[$area_vnums[$j]]["area"] = $area_title;
    }

    $areas[$filename] = array(
        "title" => $area_title,
        "exits" => $area_exits
    );
}

$world = array();

foreach ($areas as $filename=>$area) {
    $hood = array();
    $exits = 0;

    foreach ($area["exits"] as $to_vnum=>$fn) {
        if (!array_key_exists($to_vnum, $rooms)) {
            echo "exit to room #".$to_vnum." in ".$filename." not resolved\n";
            continue;
        }

        $neighbour = $rooms[$to_vnum]["area"];

        if (!array_key_exists($neighbour, $hood)) {
            $hood[$neighbour] = 0;
        }

        $hood[$neighbour]++;
        $exits++;
    }

    if ($exits > 0) {
        $world[$area["title"]] = $hood;
    }
}

$written = file_put_contents(
    "data.json", json_encode($world, JSON_PRETTY_PRINT)
);

if ($written === false) {
    echo("failed to write data.json\n");
}
else {
    echo($written." bytes written into data.json\n");
}
