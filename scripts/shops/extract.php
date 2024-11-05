<?php

if (count($argv) <= 1) {
    exit("usage: ".$argv[0]." path-to-areas\n");
}

$areapath = $argv[1];

if (!is_dir($areapath)) {
    exit('invalid diretory path: '.$areapath);
}

$files = glob($areapath."/*.are");

echo "found ".count($files)." areas\n";

$objects = array();
$mobiles = array();
$shops = array();
$rooms = array();

for ($i = 0; $i<count($files); ++$i) {
    echo "parsing ".$files[$i]."\n";

    $area = parse_area($files[$i]);

    $objects += $area["objects"];
    $mobiles += $area["mobiles"];
    $shops += $area["shops"];
    $rooms += $area["rooms"];
}

$results = array();

foreach ($shops as $vnum=>$shopkeeper) {
    if (!array_key_exists($vnum, $mobiles)) {
        continue;
    }

    if (!array_key_exists("room", $shopkeeper)) {
        continue;
    }

    $mob_name = explode(
        " ", array_key_exists($vnum, $mobiles) ? $mobiles[$vnum]["name"] : ""
    )[0];

    $mob_short_desc = array_key_exists($vnum, $mobiles) ? (
        $mobiles[$vnum]["short_desc"]
    ) : "";

    $mob_long_desc = array_key_exists($vnum, $mobiles) ? (
        $mobiles[$vnum]["long_desc"]
    ) : "";

    $room_vnum = $shopkeeper["room"]["vnum"];

    $room = (
        array_key_exists($room_vnum, $rooms) ? (
            $rooms[$room_vnum]["name"]
        ) : "#".$room_vnum
    );

    $list = null;

    foreach ($shopkeeper["inventory"] as $obj_vnum=>$count) {
        if (!array_key_exists($obj_vnum, $objects)) {
            continue;
        }

        $obj = $objects[$obj_vnum];

        if ($list === null) {
            $list = array();
        }

        $short_desc = $obj["short_desc"];
        $parts = explode("{", $short_desc);
        $short_desc = $parts[0];

        for ($i=1; $i<count($parts); ++$i) {
            if (strlen($parts[$i] === 0)) {
                $short_desc .= "{";
                continue;
            }

            $short_desc .= substr($parts[$i], 1);
        }

        $list[] = array(
            "count" => $count,
            "short_desc" => $short_desc,
            "vnum" => $obj_vnum
        );
    }

    if ($list === null) {
        continue; // Skip shopkeepers that do not have any inventory.
    }

    $results[$vnum] = array(
        "mob" => array(
            "name" => ucfirst($mob_name),
            "short_desc" => $mob_short_desc,
            "long_desc" => $mob_long_desc
        ),
        "room" => $room,
        "area" => $shopkeeper["area"],
        "list" => $list
    );
}

$arearesults = array();

foreach ($results as $vnum => $result) {
    if (!array_key_exists($result["area"], $arearesults)) {
        $arearesults[$result["area"]] = array();
    }

    $arearesults[$result["area"]][$vnum] = $result;
}

print_markdown($arearesults);

function parse_area($filepath) {
    $objects = parse_objects($filepath);
    $mobiles = parse_mobiles($filepath);
    $shops = parse_shops($filepath);
    $rooms = parse_rooms($filepath);
    $resets = parse_resets($filepath);
    $header = parse_header($filepath);

    foreach ($resets as $room_vnum=>$mobs) {
        foreach ($mobs as $mob_vnum=>$mob) {
            if (!array_key_exists($mob_vnum, $shops)) {
                continue;
            }

            $shops[$mob_vnum] = $mob;
            $shops[$mob_vnum]["room"] = array(
                'vnum' => $room_vnum
            );
            $shops[$mob_vnum]["area"] = $header["title"];
        }
    }

    return array(
        'header' => $header,
        'shops' => $shops,
        'rooms' => $rooms,
        'objects' => $objects,
        'mobiles' => $mobiles
    );
}

function parse_header($filepath) {
    $data = file_get_contents($filepath);

    if ($data === false) {
        echo "failed to read ".$filepath."\n";
        return array();
    }

    $data = str_replace(array("\t", "\r"), array(" ", ""), $data);

    $tag = "#AREA ";
    $header_start = strpos($data, $tag);

    if ($header_start === false) {
        echo "no header found in ".$filepath."\n";
        return array();
    }

    $header_end = strpos($data, "^\n", $header_start);

    if ($header_end === false) {
        echo "no end of header found in ".$filepath."\n";
        return array();
    }

    $start_pos = $header_start + strlen($tag);

    $header_lines = substr(
        $data, $start_pos, max(0, $header_end - $header_start - strlen($tag))
    );

    $header_lines = array_values(
        array_filter(explode("\n", $header_lines), 'strlen')
    );

    $header = array();

    if (count($header_lines) > 0) {
        $parts = explode("(", $header_lines[0]);

        $title = $parts[0];

        $header['title'] = trim($title);

        if (count($parts) > 1) {
            $author_parts = explode(")", $parts[1]);

            if (count($author_parts) > 0) {
                $header['author'] = $author_parts[0];
            }
            else {
                $header['author'] = null;
            }
        }
    }

    return $header;
}

function parse_objects($filepath) {
    $data = file_get_contents($filepath);

    if ($data === false) {
        echo "failed to read ".$filepath."\n";
        return array();
    }

    $data = str_replace(array("\t", "\r"), array(" ", ""), $data);

    $tag = "\n#OBJECTS\n";
    $objects_start = strpos($data, $tag);

    if ($objects_start === false) {
        echo "no items found in ".$filepath."\n";
        return array();
    }

    $objects_end = strpos($data, "\n#0", $objects_start);

    if ($objects_end === false) {
        echo "no end of items found in ".$filepath."\n";
        return array();
    }

    $start_pos = $objects_start + strlen($tag);

    $object_lines = substr(
        $data, $start_pos, max(0, $objects_end - $objects_start - strlen($tag))
    );

    $object_lines = array_values(
        array_filter(explode("\n", $object_lines), 'strlen')
    );

    $objects = array();

    $vnum = null;
    $row = null;

    for ($i=0; $i<count($object_lines); ++$i) {
        $line = $object_lines[$i];

        if ($vnum !== null) {
            $row++;

            if ($row === 2) {
                $objects[$vnum]['short_desc'] = explode("^", $line)[0];
            }
        }

        if (strpos($line, "#") === 0) {
            $vnum = substr($line, 1);

            if (ctype_digit($vnum)) {
                $row = 0;

                $objects[$vnum] = array(
                    'short_desc' => null
                );
            }
            else $vnum = null;

            continue;
        }
    }

    return $objects;
}

function parse_mobiles($filepath) {
    $data = file_get_contents($filepath);

    if ($data === false) {
        echo "failed to read ".$filepath."\n";
        return array();
    }

    $data = str_replace(array("\t", "\r"), array(" ", ""), $data);

    $tag = "\n#MOBILES\n";
    $mobiles_start = strpos($data, $tag);

    if ($mobiles_start === false) {
        echo "no mobiles found in ".$filepath."\n";
        return array();
    }

    $mobiles_end = strpos($data, "\n#0", $mobiles_start);

    if ($mobiles_end === false) {
        echo "no end of mobiles found in ".$filepath."\n";
        return array();
    }

    $start_pos = $mobiles_start + strlen($tag);

    $mobile_lines = substr(
        $data, $start_pos, max(0, $mobiles_end - $mobiles_start - strlen($tag))
    );

    $mobile_lines = array_values(
        array_filter(explode("\n", $mobile_lines), 'strlen')
    );

    $mobiles = array();

    $vnum = null;
    $row = null;

    for ($i=0; $i<count($mobile_lines); ++$i) {
        $line = $mobile_lines[$i];

        if ($vnum !== null) {
            $row++;

            if ($row === 1) {
                $mobiles[$vnum]['name'] = explode("^", $line)[0];
            }
            else if ($row === 2) {
                $mobiles[$vnum]['short_desc'] = explode("^", $line)[0];
            }
            else if ($row === 3) {
                $mobiles[$vnum]['long_desc'] = $line;
            }
        }

        if (strpos($line, "#") === 0) {
            $vnum = substr($line, 1);

            if (ctype_digit($vnum)) {
                $row = 0;

                $mobiles[$vnum] = array(
                    'name' => null,
                    'short_desc' => null,
                    'long_desc' => null
                );
            }
            else $vnum = null;

            continue;
        }
    }

    return $mobiles;
}

function parse_rooms($filepath) {
    $data = file_get_contents($filepath);

    if ($data === false) {
        echo "failed to read ".$filepath."\n";
        return array();
    }

    $data = str_replace(array("\t", "\r"), array(" ", ""), $data);

    $tag = "\n#ROOMS\n";
    $rooms_start = strpos($data, $tag);

    if ($rooms_start === false) {
        echo "no rooms found in ".$filepath."\n";
        return array();
    }

    $rooms_end = strpos($data, "\n#0", $rooms_start);

    if ($rooms_end === false) {
        echo "no end of rooms found in ".$filepath."\n";
        return array();
    }

    $start_pos = $rooms_start + strlen($tag);

    $room_lines = substr(
        $data, $start_pos, max(0, $rooms_end - $rooms_start - strlen($tag))
    );

    $room_lines = array_values(
        array_filter(explode("\n", $room_lines), 'strlen')
    );

    $rooms = array();

    $vnum = null;
    $row = null;

    for ($i=0; $i<count($room_lines); ++$i) {
        $line = $room_lines[$i];

        if ($vnum !== null) {
            $row++;

            if ($row === 1) {
                $rooms[$vnum]['name'] = explode("^", $line)[0];
            }
        }

        if (strpos($line, "#") === 0) {
            $vnum = substr($line, 1);

            if (ctype_digit($vnum)) {
                $row = 0;

                $rooms[$vnum] = array(
                    'name' => null
                );
            }
            else $vnum = null;

            continue;
        }
    }

    return $rooms;
}

function parse_shops($filepath) {
    $data = file_get_contents($filepath);

    if ($data === false) {
        echo "failed to read ".$filepath."\n";
        return array();
    }

    $data = str_replace(array("\t", "\r"), array(" ", ""), $data);

    $tag = "\n#SHOPS\n";
    $shops_start = strpos($data, $tag);

    if ($shops_start === false) {
        echo "no shops found in ".$filepath."\n";
        return array();
    }

    $shops_end = strpos($data, "\n0", $shops_start);

    if ($shops_end === false) {
        echo "no end of shops found in ".$filepath."\n";
        return array();
    }

    $start_pos = $shops_start + strlen($tag);

    $shop_lines = substr(
        $data, $start_pos, max(0, $shops_end - $shops_start - strlen($tag))
    );

    $shop_lines = array_values(
        array_filter(explode("\n", $shop_lines), 'strlen')
    );

    $shops = array();

    for ($i=0; $i<count($shop_lines); ++$i) {
        $parts = array_values(
            array_filter(explode(" ", $shop_lines[$i]), 'strlen')
        );

        if (count($parts) > 0 && ctype_digit($parts[0])) {
            if ($parts[0] == "0") echo $filepath."\n";
            $shops[$parts[0]] = array();
        }
    }

    return $shops;
}

function parse_resets($filepath) {
    $data = file_get_contents($filepath);

    if ($data === false) {
        echo "failed to read ".$filepath."\n";
        return array();
    }

    $data = str_replace(array("\t", "\r"), array(" ", ""), $data);

    $tag = "\n#RESETS\n";
    $resets_start = strpos($data, $tag);

    if ($resets_start === false) {
        echo "no resets found in ".$filepath."\n";
        return array();
    }

    $resets_end = strpos($data, "\nS", $resets_start);

    if ($resets_end === false) {
        echo "no end of resets found in ".$filepath."\n";
        return array();
    }

    $start_pos = $resets_start + strlen($tag);
    $reset_lines = substr($data, $start_pos, $resets_end - $start_pos);
    $reset_lines = array_values(
        array_filter(explode("\n", $reset_lines), 'strlen')
    );

    $resets = array();
    $mob_vnum = null;
    $room_vnum = null;

    for ($i=0; $i<count($reset_lines); ++$i) {
        $parts = array_values(
            array_filter(explode(" ", $reset_lines[$i]), 'strlen')
        );

        if (count($parts) > 1 && ctype_digit($parts[1])) {
            if ($parts[0] === "M") {
                if (count($parts) >= 5
                && ctype_digit($parts[4])) {
                    $mob_vnum = $parts[1];
                    $room_vnum = $parts[4];

                    if (!array_key_exists($room_vnum, $resets)) {
                        $resets[$room_vnum] = array();
                    }

                    if (!array_key_exists($mob_vnum, $resets[$room_vnum])) {
                        $resets[$room_vnum][$mob_vnum] = array(
                            'inventory' => array()
                        );
                    }
                }
                else {
                    $mob_vnum = null;
                    $room_vnum = null;
                }
            }

            if ($parts[0] === "G"
            &&  $mob_vnum !== null
            &&  $room_vnum !== null) {
                $item_vnum = $parts[1];

                $key_found = array_key_exists(
                    $item_vnum, $resets[$room_vnum][$mob_vnum]['inventory']
                );

                if ($key_found) {
                    $resets[$room_vnum][$mob_vnum]['inventory'][$item_vnum]++;
                }
                else {
                    $resets[$room_vnum][$mob_vnum]['inventory'][$item_vnum] = 1;
                }
            }
        }
    }

    return $resets;
}

function print_markdown($data) {
    $headings = array(
        "Count"
            => ":----",
        "Item"
            => ":----------------------------------------------------------",
        "Vnum"
            => ":-----"
    );

    $fmt1 = "%-".strlen($headings['Count']).".".strlen($headings['Count'])."s";
    $fmt2 = "%-".strlen($headings['Item']).".".strlen($headings['Item'])."s";
    $fmt3 = "%-".strlen($headings['Vnum']).".".strlen($headings['Vnum'])."s";

    print("# Shops ".str_repeat("#", 72)."\n\n");

    foreach ($data as $area => $shops) {
        print("## ".$area." ".str_repeat("#", 76 - strlen($area))."\n\n");

        foreach ($shops as $shop) {
            $mob = $shop["mob"];
            $mob_name = $mob["name"];
            $mob_long_desc = $mob["long_desc"];

            $title = $mob_name." in ".$shop["room"];

            print(
                "### ".$title." ".str_repeat("#", 75 - strlen($title))."\n\n"
            );

            print($mob_long_desc."\n\n");

            foreach ($headings as $heading => $underline) {
                print(
                    ($heading === "Count" ? "| " : " | ").
                    sprintf(
                        "%".strlen($underline)."s", $heading
                    )
                );
            }

            print(" |\n");

            foreach ($headings as $heading => $underline) {
                print(($heading === "Count" ? "| " : " | ").$underline);
            }

            print(" |\n");

            foreach ($shop["list"] as $item) {
                print(
                    "| ".sprintf($fmt1, $item["count"]).
                    " | ".sprintf($fmt2, $item["short_desc"]).
                    " | ".sprintf($fmt3, "#".$item["vnum"])." |\n"
                );
            }

            print("\n\n");
        }
    }
}
