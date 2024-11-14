<?php

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

    $header_end = strpos($data, "^", $header_start);

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

    $tag = "\n#OBJECTS";
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
            else if ($row === 6) {
                $parts = array_values(
                    array_filter(explode(" ", $line), 'strlen')
                );

                if (count($parts) > 2 && ctype_digit($parts[2])) {
                    $objects[$vnum]['worth'] = intval($parts[2], 10);
                }
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

    $tag = "\n#MOBILES";
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

    $tag = "\n#ROOMS";
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

    $directions = array(
        "D0" => "north",
        "D1" => "east",
        "D2" => "south",
        "D3" => "west",
        "D4" => "up",
        "D5" => "down"
    );
    $rooms = array();

    $vnum = null;
    $row = null;
    $dir = null;
    $buf = null;

    $expecting_vnum = false;

    for ($i=0; $i<count($room_lines); ++$i) {
        $line = $room_lines[$i];

        if ($vnum !== null) {
            $row++;

            if ($row === 1) {
                $rooms[$vnum]['name'] = explode("^", $line)[0];
                $buf = "";
            }
            else if ($buf !== null) {
                $l = implode(
                    "", array_values(
                        array_filter(explode(" ", trim($line)), 'strlen')
                    )
                );

                if ($l === "D0"
                ||  $l === "D1"
                ||  $l === "D2"
                ||  $l === "D3"
                ||  $l === "D4"
                ||  $l === "D5"
                ||  $l === "S") {
                    if ($dir === "S") {
                        echo(
                            "countering S twice when reading exits from #".
                            $vnum."\n"
                        );
                    }

                    $next_dir = $l;

                    if ($dir !== null) {
                        $fields = explode("^", $buf);

                        if (count($fields) >= 3) {
                            $fields[2] = trim($fields[2]);

                            $fields = array_values(
                                array_filter(explode(" ", $fields[2]), 'strlen')
                            );

                            if (!array_key_exists("exits", $rooms[$vnum])) {
                                $rooms[$vnum]["exits"] = array();
                            }

                            if (array_key_exists($dir, $directions)) {
                                $rooms[$vnum]["exits"][$directions[$dir]] = (
                                    trim(explode("\n", $fields[2])[0])
                                );
                            }
                            else {
                                echo(
                                    "unknown direction '".$dir.
                                    "' in room #".$vnum."\n"
                                );
                            }
                        }
                        else echo "failed to read exit of room #".$vnum."\n";

                        $buf = "";
                    }

                    $dir = $next_dir;

                    if ($dir === "S") {
                        $buf = null;
                    }
                }
                else if ($dir !== null) {
                    if (strlen($buf) === 0) $buf = $line;
                    else {
                        $buf .= "\n".$line;
                    }
                }
            }
        }

        if (strpos($line, "#") === 0 || $expecting_vnum) {
            if (strpos($line, "#") === 0) {
                $vnum = explode(" ", substr($line, 1))[0];
            }
            else {
                $vnum = explode(" ", $line)[0];
            }

            $dir = null;

            if (ctype_digit($vnum)) {
                $expecting_vnum = false;
                $row = 0;

                $rooms[$vnum] = array(
                    'name' => null
                );
            }
            else {
                $vnum = null;
                $expecting_vnum = true;
            }

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

    $tag = "\n#SHOPS";
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
            $shops[$parts[0]] = array();

            if (count($parts) > 7
            && ctype_digit($parts[6])
            && ctype_digit($parts[7])) {
                $shops[$parts[0]]["sell"] = intval($parts[6], 10);
                $shops[$parts[0]]["buy"] = intval($parts[7], 10);
            }
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

    $tag = "\n#RESETS";
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
