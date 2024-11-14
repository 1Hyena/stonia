<?php

require('../utils.php');

if (count($argv) <= 1) {
    exit("usage: ".$argv[0]." path-to-areas\n");
}

$areapath = $argv[1];

if (!is_dir($areapath)) {
    exit('invalid diretory path: '.$areapath."\n");
}

$files = glob($areapath."/*.are");

echo "found ".count($files)." areas\n";

$areas = array();
$rooms = array();

for ($i = 0; $i<count($files); ++$i) {
    echo "parsing ".$files[$i]."\n";

    $area = parse_area($files[$i]);

    if (!array_key_exists("title", $area["header"])) {
        continue;
    }

    $exit = array();
    $vnums = array();
    $filename = basename($files[$i]);

    foreach ($area["rooms"] as $vnum=>$room) {
        $vnums[] = $vnum;

        if (!array_key_exists("exits", $room)) {
            echo("no exits in room #".$vnum." (".$filename.")\n");
            continue;
        }

        foreach ($room["exits"] as $dir=>$to_vnum) {
            if (array_key_exists($to_vnum, $area["rooms"])) {
                continue;
            }

            $exit[$to_vnum] = null;
        }
    }

    for ($j = 0; $j<count($vnums); ++$j) {
        $area["rooms"][$vnums[$j]]["area"] = $area["header"]["title"];
    }

    $areas[$filename] = array(
        "title" => $area["header"]["title"],
        "exits" => $exit
    );

    $rooms += $area["rooms"];
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

function parse_area($filepath) {
    $rooms = parse_rooms($filepath);
    $header = parse_header($filepath);

    return array(
        'header' => $header,
        'rooms' => $rooms
    );
}
