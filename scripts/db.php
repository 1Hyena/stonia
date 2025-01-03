<?php

function POW2($c) {
    $flags = array(
        "A" => 1,
        "B" => 2,
        "C" => 4,
        "D" => 8,
        "E" => 16,
        "F" => 32,
        "G" => 64,
        "H" => 128,
        "I" => 256,
        "J" => 512,
        "K" => 1024,
        "L" => 2048,
        "M" => 4096,
        "N" => 8192,
        "O" => 16384,
        "P" => 32768,
        "Q" => 65536,
        "R" => 131072,
        "S" => 262144,
        "T" => 524288,
        "U" => 1048576,
        "V" => 2097152,
        "W" => 4194304,
        "X" => 8388608,
        "Y" => 16777216,
        "Z" => 33554432,
        "aa" => 67108864,
        "bb" => 134217728,
        "cc" => 268435456,
        "dd" => 536870912,
        "ee" => 1073741824
    );

    if (array_key_exists($c, $flags)) {
        return $flags[$c];
    }

    exit("invalid flag '".$c."'\n");
}

$STONIA = array(
    "exit_info" => array(
        "EX_ISDOOR"         => 1,
        "EX_CLOSED"         => 2,
        "EX_LOCKED"         => 4,
        "EX_PICKPROOF"      => 8,
        "EX_JAMMED"         => 16,
        "EX_HIDED"          => 32,
        "EX_RUINED"         => 64,
        "EX_CLIMB"          => 128,
        "EX_MOVE"           => 256,
        "EX_NO_PASS_DOOR"   => 512,
        "EX_NOSCAN"         => 1024
    ),
    "room_flags" => array(
        "ROOM_DARK"         => POW2('A'),
        "ROOM_NO_SUN"       => POW2('B'),
        "ROOM_NO_MOB"       => POW2('C'),
        "ROOM_INDOORS"      => POW2('D'),
        "ROOM_ANTI_MAGIC"   => POW2('I'),
        "ROOM_PRIVATE"      => POW2('J'),
        "ROOM_SAFE"         => POW2('K'),
        "ROOM_SOLITARY"     => POW2('L'),
        "ROOM_PET_SHOP"     => POW2('M'),
        "ROOM_NO_RECALL"    => POW2('N'),
        "ROOM_IMP_ONLY"     => POW2('O'),
        "ROOM_GODS_ONLY"    => POW2('P'),
        "ROOM_HEROES_ONLY"  => POW2('Q'),
        "ROOM_NEWBIES_ONLY" => POW2('R'),
        "ROOM_LAW"          => POW2('S'),
        "ROOM_DEATH"        => POW2('T'),
        "ROOM_IMMDARK"      => POW2('U'),
        "ROOM_IMMLIGHT"     => POW2('V'),
        "ROOM_FOG"          => POW2('W'),
        "ROOM_NO_TELEPORT"  => POW2('X'),
        "ROOM_SILENCE"      => POW2('Y'),
        "ROOM_NO_DEATH"     => POW2('Z'),
        "ROOM_ARENA"        => POW2('aa')
    ),
    "MAX_TRADE" => 5
);

function load($filepath, &$stonia) {
    $filename = basename($filepath);

    if (!file_exists($filepath) || is_dir($filepath)) {
        echo "cannot load file: ".$filename."\n";
        return null;
    }

    $contents = file_get_contents($filepath);

    $fh = array(
        "filename" => $filename,
        "content" => $contents,
        "position" => 0,
        "length" => strlen($contents)
    );

    if ($fh["content"] === false) {
        echo "failed to open file for reading: ".$filename."\n";
        return null;
    }

    $data = array(
        "area" => null,
        "helps" => null,
        "mobiles" => null,
        "objects" => null,
        "resets" => null,
        "rooms" => null,
        "shops" => null,
        "socials" => null,
        "specials" => null,
        "mobprogs" => null
    );

    while (true) {
        $letter = read_letter($fh);

        if ($letter !== '#') {
            echo("# not found in ".$filename." (got '".$letter."' instead)\n");
            break;
        }

        $word = read_word($fh);

        if ($word === null) {
            echo("failed to read from ".$filename."\n");
            break;
        }

        if ($word[0] === '$') {
            break;
        }
        else if (!str_cmp($word, "AREA")) {
            $data["area"] = load_area($fh, $stonia);
        }
        else if (!str_cmp($word, "HELPS")) {
            $data["helps"] = load_helps($fh, $stonia);
        }
        else if (!str_cmp($word, "MOBILES" )) {
            $data["mobiles"] = load_mobiles($fh, $stonia);
        }
        else if (!str_cmp($word, "OBJECTS")) {
            $data["objects"] = load_objects($fh, $stonia);
        }
        else if (!str_cmp($word, "RESETS"  )) {
            $data["resets"] = load_resets($fh, $stonia);
        }
        else if (!str_cmp($word, "ROOMS")) {
            $data["rooms"] = load_rooms($fh, $stonia);
        }
        else if (!str_cmp($word, "SHOPS")) {
            $data["shops"] = load_shops($fh, $stonia);
        }
        else if (!str_cmp($word, "SOCIALS")) {
            $data["socials"] = load_socials($fh, $stonia);
        }
        else if (!str_cmp($word, "SPECIALS")) {
            $data["specials"] = load_specials($fh, $stonia);
        }
        else if (!str_cmp($word, "MOBPROGS")) {
            $data["mobprogs"] = load_mobprogs($fh, $stonia);
        }
        else {
            echo("bad section name '".$word."' in ".$filename."\n");
            break;
        }
    }

    return $data;
}

function load_area(&$fh, &$stonia) {
    $name  = read_string($fh);
    $repop = read_string($fh);

    $wp_white = read_number($fh);
    $wp_black = read_number($fh);
    $wp_brown = read_number($fh);
    $wp_misty = read_number($fh);

    return array(
        "name" => $name,
        "repop" => $repop,
        "warpoints" => array(
            "white" => $wp_white,
            "black" => $wp_black,
            "brown" => $wp_brown,
            "misty" => $wp_misty
        )
    );
}

function load_helps(&$fh, &$stonia) {
    $helps = array();

    while (true) {
        $level = read_number($fh);
        $keyword = read_string($fh);

        if ($keyword[0] == '$') {
            break;
        }

        $text = read_string($fh);

        $helps[] = array(
            "level" => $level,
            "keyword" => $keyword,
            "text" => $text
        );
    }

    return $helps;
}

function load_mobiles(&$fh, &$stonia) {
    $mobiles = array();

    while (true) {
        $letter = read_letter($fh);

        if ($letter !== '#') {
            return "# not found (got '".$letter."' instead)";
        }

        $vnum = read_number($fh);

        if ($vnum === 0) {
            break;
        }

        $mob = array(
            "vnum" => $vnum
        );

        $mob["name"]        = read_string($fh);
        $mob["short"]       = read_string($fh);
        $mob["long"]        = read_string($fh);
        $mob["desc"]        = read_string($fh);
        $mob["race"]        = read_string($fh);
        $mob["act"]         = read_flag($fh);
        $mob["affected_by"] = read_flag($fh);
        $mob["alignment"]   = read_number($fh);
        $mob["level"]       = read_number($fh);
        $mob["hitroll"]     = read_number($fh);
        $mob["hit"] = (
            read_number($fh).read_letter($fh).read_number($fh).
            read_letter($fh).read_number($fh)
        );
        $mob["mana"] = (
            read_number($fh).read_letter($fh).read_number($fh).
            read_letter($fh).read_number($fh)
        );
        $mob["dam"] = (
            read_number($fh).read_letter($fh).read_number($fh).
            read_letter($fh).read_number($fh)
        );
        $mob["dam_type"]   = read_number($fh);
        $mob["ac_pierce"]  = read_number($fh);
        $mob["ac_bash"]    = read_number($fh);
        $mob["ac_slash"]   = read_number($fh);
        $mob["ac_exotic"]  = read_number($fh);
        $mob["offensive"]  = read_flag($fh);
        $mob["immune"]     = read_flag($fh);
        $mob["resistant"]  = read_flag($fh);
        $mob["vulnerable"] = read_flag($fh);
        $mob["start_pos"]  = read_number($fh);
        $mob["default_pos"]= read_number($fh);
        $mob["sex"]        = read_number($fh);
        $mob["gold"]       = read_number($fh);
        $mob["form"]       = read_flag($fh);
        $mob["parts"]      = read_flag($fh);
        $mob["size"]       = read_letter($fh);
        $mob["exp"]        = read_number($fh);

        while (true) {
            $letter = read_letter($fh);

            if ($letter === 'T') {
                if (!array_key_exists("sn", $mob)) {
                    $mob["sn"] = array();
                }

                $mob["sn"][] = read_number($fh);
            }
            else {
                unread_char($fh);

                break;
            }
        }

        $letter = read_letter($fh);

        if ($letter === '>' ) {
            unread_char($fh);
            $mprog = mprog_read_programs($fh);

            if (is_string($mprog)) {
                return "mob #".$vnum.": ".$mprog;
            }

            $mob["mprog"] = $mprog;
        }
        else {
            unread_char($fh);
        }

        $mobiles[] = $mob;
    }

    return $mobiles;
}

function mprog_read_programs(&$fh) {
    $programs = array();
    $done = false;
    $letter = read_letter($fh);

    if ($letter !== '>') {
        return "unexpected letter '".$letter."'";
    }

    while (!$done) {
        $name = read_word($fh);

        $program = array(
            "name" => $name
        );

        if (!str_cmp($name, "in_file_prog")) {
            $file = read_string($fh);
            read_to_eol($fh);

            $program["file"] = $file;

            $letter = read_letter($fh);

            switch ($letter) {
                case '>': {
                    break;
                }
                case '|': {
                    read_to_eol( fp );
                    $done = true;
                    break;
                }
                default: {
                    return "bad mobprog";
                }
            }
        }
        else {
            $arglist = read_string($fh);
            read_to_eol($fh);

            $comlist = read_space($fh).read_string($fh);
            read_to_eol($fh);

            $program["arglist"] = $arglist;
            $program["comlist"] = $comlist;

            $letter = read_letter($fh);

            switch ($letter) {
                case '>': {
                    break;
                }
                case '|': {
                    read_to_eol($fh);
                    $done = true;
                    break;
                }
                default: {
                    return "bad mobprog";
                }
            }
        }

        $programs[] = $program;
    }

    return $programs;
}

function load_objects(&$fh, &$stonia) {
    $objects = array();

    while (true) {
        $letter = read_letter($fh);

        if ($letter !== '#') {
            return "# not found";
        }

        $vnum = read_number($fh);

        if ($vnum === 0) {
            break;
        }

        $obj = array(
            "vnum" => $vnum
        );

        $obj["name"]  = read_string($fh);
        $obj["short"] = read_string($fh);
        $obj["description"] = read_string($fh);
        $obj["material"] = read_number($fh);
        $obj["item_type"] = read_number($fh);
        $obj["extra_flags"] = read_flag($fh);
        $obj["wear_flags"] = read_flag($fh);
        $obj["v0"] = read_flag($fh);
        $obj["v1"] = read_flag($fh);
        $obj["v2"] = read_flag($fh);
        $obj["v3"] = read_flag($fh);
        $obj["limit"]  = read_flag($fh);
        $obj["level"]  = read_number($fh);
        $obj["weight"] = read_number($fh);
        $obj["cost"]   = read_number($fh);
        $obj["condition"] = read_letter($fh);

        while (true) {
            $letter = read_letter($fh);

            if ($letter === 'A') {
                if (!array_key_exists("affects", $obj)) {
                    $obj["affects"] = array();
                }

                $aff = array();
                $aff["location"] = read_number($fh);
                $aff["modifier"] = read_number($fh);

                $obj["affects"][] = $aff;
            }
            else if ($letter === 'E') {
                if (!array_key_exists("extra_desc", $obj)) {
                    $obj["extra_desc"] = array();
                }

                $desc = array();

                $desc["keyword"] = read_string($fh);
                $desc["content"] = read_string($fh);

                $obj["extra_desc"][] = $desc;
            }
            else if ($letter == 'D') {
                $obj["dp"] = read_number($fh);

                break;
            }
            else {
                unread_char($fh);

                break;
            }
        }

        $objects[] = $obj;
    }

    return $objects;
}

function load_resets(&$fh, &$stonia) {
    $resets = array();

    while (true) {
        $letter = read_letter($fh);

        if ($letter === 'S') {
            break;
        }

        if ($letter === '*') {
            read_to_eol($fh);
            continue;
        }

        $reset = array(
            "command" => $letter
        );

        $reset["arg1"]   = read_number($fh);
        $reset["chance"] = read_number($fh);
        $reset["arg2"]   = read_number($fh);
        $reset["arg3"]   = (
            ($letter === 'G' || $letter === 'R') ? 0 : read_number($fh)
        );

        if ($letter == 'D') {
            $reset["arg4"] = read_number($fh);
            $reset["arg5"] = read_number($fh);
        }

        read_to_eol($fh);

        $resets[] = $reset;
    }

    return $resets;
}

function load_rooms(&$fh, &$stonia) {
    $rooms = array();

    while (true) {
        $letter = read_letter($fh);

        if ($letter !== '#') {
            return "# not found";
        }

        $vnum = read_number($fh);

        if ($vnum === 0) {
            break;
        }

        $room = array(
            "vnum" => $vnum
        );

        $room["name"]        = read_string($fh);
        $room["description"] = read_string($fh);
        $room["area_index"]  = read_number($fh);
        $room["flags"]       = read_flag($fh);
        $room["sector_type"] = read_number($fh);
        $room["exits"]       = array();
        $room["extra_desc"]  = array();

        while (true) {
            $letter = read_letter($fh);

            if ($letter === 'S') {
                break;
            }

            if ($letter === 'D') {
                $door = read_number($fh);

                $exit = array();

                $exit["direction"]    = $door;
                $exit["description"]  = read_string($fh);
                $exit["keyword"]      = read_string($fh);
                $exit["info"]         = read_flag($fh);
                $exit["key"]          = read_number($fh);
                $exit["to_room"]      = read_number($fh);

                if (is_set($exit["info"], "EX_MOVE", $stonia["exit_info"])) {
                    $exit["move"] = array();
                    $exit["move"]["percent"] = read_number($fh);
                    $exit["move"]["name"]    = read_string($fh);
                }

                $room["exits"][] = $exit;
            }
            else if ($letter === 'E') {
                $desc = array();

                $desc["keyword"]     = read_string($fh);
                $desc["description"] = read_string($fh);

                $room["extra_desc"][] = $desc;
            }
            else {
                return "room #".$vnum." has unknown letter '".$letter."'";
            }
        }

        $rooms[] = $room;
    }

    return $rooms;
}

function load_shops(&$fh, &$stonia) {
    $shops = array();

    while (true) {
        $shop = array(
            "vnum" => read_number($fh)
        );

        if ($shop["vnum"] === 0) {
            break;
        }

        $shop["buy_type"] = array();

        for ($iTrade = 0; $iTrade < $stonia["MAX_TRADE"]; $iTrade++) {
            $shop["buy_type"][] = read_number($fh);
        }

        $shop["profit_buy"]  = read_number($fh);
        $shop["profit_sell"] = read_number($fh);
        $shop["open_hour"]   = read_number($fh);
        $shop["close_hour"]  = read_number($fh);

        read_to_eol($fh);

        $shops[] = $shop;
    }

    return $shops;
}

function load_socials(&$fh, &$stonia) {
    $socials = array();

    while (true) {
        $temp = read_word($fh);

        if (!strcmp($temp, "#0")) {
            break;
        }

        $social = array(
            "name" => $temp
        );

        $social["after_name"] = read_to_eol($fh);

        $temp = read_string_eol($fh);

        if (!strcmp($temp, "$")) {
            $social["char_no_arg"] = null;
        }
        else if (!strcmp($temp, "#")) {
            $socials[] = $social;
            continue;
        }
        else {
            $social['char_no_arg'] = $temp;
        }

        $temp = read_string_eol($fh);

        if (!strcmp($temp, "$")) {
            $social['others_no_arg'] = null;
        }
        else if (!strcmp($temp, "#")) {
            $socials[] = $social;
            continue;
        }
        else {
            $social['others_no_arg'] = $temp;
        }

        $temp = read_string_eol($fh);

        if (!strcmp($temp, "$")) {
            $social['char_found'] = null;
        }
        else if (!strcmp($temp, "#")) {
            $socials[] = $social;
            continue;
        }
        else {
            $social['char_found'] = $temp;
        }

        $temp = read_string_eol($fh);

        if (!strcmp($temp, "$")) {
            $social['others_found'] = null;
        }
        else if (!strcmp($temp, "#")) {
            $socials[] = $social;
            continue;
        }
        else {
            $social['others_found'] = $temp;
        }

        $temp = read_string_eol($fh);

        if (!strcmp($temp, "$")) {
            $social['vict_found'] = null;
        }
        else if (!strcmp($temp, "#")) {
            $socials[] = $social;
            continue;
        }
        else {
            $social['vict_found'] = $temp;
        }

        $temp = read_string_eol($fh);

        if (!strcmp($temp, "$")) {
            $social['char_not_found'] = null;
        }
        else if (!strcmp($temp, "#")) {
            $socials[] = $social;
            continue;
        }
        else {
            $social['char_not_found'] = $temp;
        }

        $temp = read_string_eol($fh);

        if (!strcmp($temp, "$")) {
            $social['char_auto'] = null;
        }
        else if (!strcmp($temp, "#")) {
            $socials[] = $social;
            continue;
        }
        else {
            $social['char_auto'] = $temp;
        }

        $temp = read_string_eol($fh);

        if (!strcmp($temp, "$")) {
            $social['others_auto'] = null;
        }
        else if (!strcmp($temp, "#")) {
            $socials[] = $social;
            continue;
        }
        else {
            $social['others_auto'] = $temp;
        }

        $socials[] = $social;
    }

    return $socials;
}

function load_specials(&$fh, &$stonia) {
    $specials = array();

    while (true) {
        $letter = read_letter($fh);

        switch ($letter) {
            default: {
                return "unexpected letter '".$letter."'";
            }
            case 'S': {
                return $specials;
            }
            case '*': {
                break;
            }
            case 'M': {
                $vnum = read_number($fh);

                $special = array(
                    "type" => $letter,
                    "vnum" => $vnum
                );

                $special["fun"] = read_word($fh);

                $specials[] = $special;

                break;
            }
            case 'O': {
                $vnum = read_number($fh);

                $special = array(
                    "type" => $letter,
                    "vnum" => $vnum
                );

                $special["fun"] = read_word($fh);

                $specials[] = $special;

                break;
            }
            case 'R': {
                $vnum = read_number($fh);

                $special = array(
                    "type" => $letter,
                    "vnum" => $vnum
                );

                $special["fun"] = read_word($fh);

                $specials[] = $special;

                break;
            }
        }

        read_to_eol($fh);
    }

    return $specials;
}

function load_mobprogs(&$fh, &$stonia) {
    $mobprogs = array();

    while (true) {
        $letter = read_letter($fh);

        switch ($letter) {
            default: {
                return "bad command '".$letter."'";
            }
            case 'S':
            case 's': {
                read_to_eol($fh);
                return $mobprogs;
            }
            case '*': {
                read_to_eol($fh);
                break;
            }
            case 'M':
            case 'm': {
                $vnum = read_number($fh);
                $file = read_word($fh);

                $mobprog = array(
                    "vnum" => $vnum,
                    "file" => $file
                );

                $mobprogs[] = $mobprog;

                read_to_eol($fh);

                break;
            }
        }
    }

    return $mobprogs;
}

function read_char(&$fh) {
    $char = substr($fh["content"], $fh["position"], 1);
    $fh["position"] = min($fh["position"] + 1, $fh["length"]);
    return $char;
}

function unread_char(&$fh) {
    if ($fh["position"] > 0) {
        $fh["position"]--;
    }
}

function read_letter(&$fh) {
    $c = 0;

    do {
	    $c = read_char($fh);
    }
    while (ctype_space($c) && !is_eof($fh));

    return $c;
}

function is_eof(&$fh) {
    return $fh["position"] > $fh["length"];
}

function read_space(&$fh) {
    $space = '';
    $char = '';

    do {
        $space.=$char;
        $char = read_char($fh);
    } while (ctype_space($char) && !is_eof($fh));

    if (!is_eof($fh)) {
        unread_char($fh);
    }

    return $space;
}

function read_string(&$fh) {
    $char = '';

    do {
        $char = read_char($fh);
    } while (ctype_space($char) && !is_eof($fh));

    if ($char === '^') {
        return "";
    }

    $str = $char;

    do {
        $char = read_char($fh);

        if ($char === '^') {
            return $str;
        }

        $str.=$char;
    } while (!is_eof($fh));

    exit("premature EOF\n");
}

function read_word(&$fh) {
    $word="";
    $cEnd="";

    do {
        $cEnd = read_char($fh);
    } while (ctype_space($cEnd) && !is_eof($fh));

    if ($cEnd !== '\'' && $cEnd !== '"') {
        $word.=$cEnd;
        $cEnd = ' ';
    }

    do {
        $char = read_char($fh);

        if ($cEnd === ' ' ? ctype_space($char) : $char === $cEnd) {
            if ($cEnd === ' ') {
                unread_char($fh);
            }

            return $word;
        }

        $word.=$char;
    } while ( !is_eof($fh) );

    if (strncmp($word, "#END", 4 )) {
        return $word;
    }

    exit("read_word: #END not found\n");
}

function read_number(&$fh) {
    $c = '';

    do {
        $c = read_char($fh);
    } while (ctype_space($c) && !is_eof($fh));

    $number = 0;
    $sign = false;

    if ($c === '+') {
        $c = read_char($fh);
    }
    else if ($c === '-') {
        $sign = true;
        $c = read_char($fh);
    }

    if (is_eof($fh)) {
        exit("read_number: premature EOF\n");
    }

    if (!ctype_digit($c)) {
        exit(
            "read_number: bad format (in ".
            $fh["filename"]." before '".substr(
                $fh["content"], $fh["position"], 20
            )."')\n"
        );
    }

    while (ctype_digit($c)) {
        $number = $number * 10 + intval($c);
        $c = read_char($fh);
    }

    if ($sign) {
        $number = 0 - $number;
    }

    if ($c === '|' ) {
        $number += read_number($fh);
    }
    else if ($c != ' ') {
        unread_char($fh);
    }

    return $number;
}

function read_flag(&$fh) {
    $c = '';

    do {
        $c = read_char($fh);
    }
    while (ctype_space($c) && !is_eof($fh));

    $number = 0;

    if (!ctype_digit($c)) {
        while (
            (ord('A') <= ord($c) && ord($c) <= ord('Z')) ||
            (ord('a') <= ord($c) && ord($c) <= ord('z'))
        ) {
            $number |= flag_convert($c);
            $c = read_char($fh);
        }
    }
    else {
        while (ctype_digit($c)) {
            $number = $number * 10 + intval($c);
            $c = read_char($fh);
        }
    }

    if ($c === '|') {
        $number |= read_flag($fh);
    }
    else if ($c !== ' ') {
        unread_char($fh);
    }

    return $number;
}

function flag_convert($letter) {
    $bitsum = 0;

    if (ord('A') <= ord($letter) && ord($letter) <= ord('Z')) {
        $bitsum = 1;

        for ($i = ord($letter); $i > ord('A'); $i--) {
            $bitsum *= 2;
        }
    }
    else if (ord('a') <= ord($letter) && ord($letter) <= ord('z')) {
        $bitsum = 67108864; // 2^26

        for ($i = ord($letter); $i > ord('a'); $i--) {
            $bitsum *= 2;
        }
    }

    return $bitsum;
}

function read_to_eol(&$fh) {
    $str = "";
    $c = "";

    do {
        $str.=$c;
        $c = read_char($fh);
    }
    while ($c != "\n" && $c != "\r" && !is_eof($fh) );

    do {
        $c = read_char($fh);
    }
    while (($c == "\n" || $c == "\r") && !is_eof($fh));

    if (is_eof($fh)) {
        return $str;
    }

    unread_char($fh);

    return $str;
}

function read_string_eol(&$fh) {
    $c = '';

    do {
        $c = read_char($fh);
    }
    while (ctype_space($c) && !is_eof($fh));

    if ($c === "\n" || is_eof($fh)) {
        return "";
    }

    $string = $c;

    while (true) {
        $c = read_char($fh);

        if ($c === "\n" || $c === "\r") {
            break;
        }

        if (is_eof($fh)) {
            exit("read_string_eol: unexpected EOF\n");
        }
        else {
            $string.=$c;
        }
    }

    return $string;
}

function is_set($flags, $flag, $dictionary) {
    if (!array_key_exists($flag, $dictionary)) {
        exit("dictionary does not contain ".$flag."\n");
    }

    return $flags & $dictionary[$flag];
}

function str_cmp($astr, $bstr) {
    return !strcasecmp($astr, $bstr) ? false : true;
}

function vnums_to_dictionary($array, &$dictionary) {
    $collisions = array();

    for ($i=0; $i<count($array); ++$i) {
        if (!array_key_exists("vnum", $array[$i])) {
            return "vnums_to_dictionary: vnum missing at index ".$i;
        }

        $vnum = $array[$i]["vnum"];

        if (array_key_exists($vnum, $dictionary)) {
            $collisions[$vnum] = true;

            continue;
        }

        $dictionary[$vnum] = $array[$i];
    }

    return $collisions;
}

function get_vnums_as_dictionary($array) {
    $dictionary = array();

    $result = vnums_to_dictionary($array, $dictionary);

    if (is_string($result)) {
        exit($result."\n");
    }

    return $dictionary;
}

function save($filepath, $data) {
    $content = "";

    if ($data["area"] !== null) {
        $content .= serialize_area($data["area"]);
    }

    if ($data["helps"] !== null) {
        $content .= serialize_helps($data["helps"]);
    }

    if ($data["socials"] !== null) {
        $content .= serialize_socials($data["socials"]);
    }

    if ($data["mobiles"] !== null) {
        $content .= serialize_mobiles($data["mobiles"]);
    }

    if ($data["mobprogs"] !== null) {
        $content .= serialize_mobprogs($data["mobprogs"]);
    }

    if ($data["objects"] !== null) {
        $content .= serialize_objects($data["objects"]);
    }

    if ($data["rooms"] !== null) {
        $content .= serialize_rooms($data["rooms"]);
    }

    if ($data["resets"] !== null) {
        $content .= serialize_resets($data["resets"]);
    }

    if ($data["shops"] !== null) {
        $content .= serialize_shops($data["shops"]);
    }

    if ($data["specials"] !== null) {
        $content .= serialize_specials($data["specials"]);
    }

    $content .= "#$\n";

    return file_put_contents($filepath, $content);
}

function serialize_area($data) {
    return (
        "#AREA ".$data["name"]."^\n".$data["repop"]."^\n".
        $data["warpoints"]["white"]." ".$data["warpoints"]["black"]." ".
        $data["warpoints"]["brown"]." ".$data["warpoints"]["misty"]."\n\n"
    );
}

function serialize_mobiles($data) {
    $content = "#MOBILES\n\n";
    $mobiles = get_vnums_as_dictionary($data);

    ksort($mobiles);

    foreach ($mobiles as $vnum=>$mob) {
        $content .= serialize_mobile($mob);
    }

    $content .= "#0\n\n";

    return $content;
}

function serialize_mobile($data) {
    $content = "#".$data["vnum"]."\n";

    $content.=$data["name"]."^\n";
    $content.=$data["short"]."^\n";
    $content.=$data["long"]."^\n";
    $content.=$data["desc"]."^\n";
    $content.=$data["race"]."^\n";
    $content.=serialize_flags($data["act"])." ";
    $content.=serialize_flags($data["affected_by"])." ";
    $content.=$data["alignment"]."\n";
    $content.=$data["level"]." ";
    $content.=$data["hitroll"]." ";
    $content.=$data["hit"]." ";
    $content.=$data["mana"]." ";
    $content.=$data["dam"]." ";
    $content.=$data["dam_type"]."\n";
    $content.=$data["ac_pierce"]." ";
    $content.=$data["ac_bash"]." ";
    $content.=$data["ac_slash"]." ";
    $content.=$data["ac_exotic"]."\n";
    $content.=serialize_flags($data["offensive"])." ";
    $content.=serialize_flags($data["immune"])." ";
    $content.=serialize_flags($data["resistant"])." ";
    $content.=serialize_flags($data["vulnerable"])."\n";
    $content.=$data["start_pos"]." ";
    $content.=$data["default_pos"]." ";
    $content.=$data["sex"]." ";
    $content.=$data["gold"]."\n";
    $content.=serialize_flags($data["form"])." ";
    $content.=serialize_flags($data["parts"])." ";
    $content.=$data["size"]." ";
    $content.=$data["exp"]."\n";

    if (array_key_exists("sn", $data)) {
        foreach ($data["sn"] as $i=>$sn) {
            $content.="T ".$sn."\n";
        }
    }

    if (array_key_exists("mprog", $data)) {
        for ($i=0; $i<count($data["mprog"]); ++$i) {
            $program = $data["mprog"][$i];

            $content.=">".$program["name"]." ";

            if (!str_cmp($program["name"], "in_file_prog")) {
                $content.=$program["file"]."^\n";
            }
            else {
                $content.=$program["arglist"]."^\n";
                $content.=$program["comlist"]."^\n";
            }
        }
        $content.="|\n";
    }

    return $content."\n";
}

function serialize_mobprogs($data) {
    if (!count($data)) {
        return "";
    }

    $content = "#MOBPROGS\n\n";

    for ($i = 0; $i < count($data); ++$i) {
        $content.="M ".$data[$i]["vnum"]." ".$data[$i]["file"]."\n";
    }

    return $content."\nS\n\n";
}

function serialize_resets($data) {
    if (!count($data)) {
        return "";
    }

    $content = "#RESETS\n\n";

    for ($i = 0; $i < count($data); ++$i) {
        $reset = $data[$i];

        $content.=$reset["command"]." ".sprintf("%5s", $reset["arg1"])." ";
        $content.=sprintf("%3s", $reset["chance"])." ";
        $content.=sprintf("%3s", $reset["arg2"]);

        if ($reset["command"] !== 'G' && $reset["command"] !== 'R') {
            $content.=" ".sprintf("%5s", $reset["arg3"]);
        }

        if ($reset["command"] === 'D') {
            $content.=" ".sprintf("%3s", $reset["arg4"]);
            $content.=" ".sprintf("%3s", $reset["arg5"]);
        }

        $content.="\n";
    }

    return $content."\nS\n\n";
}

function serialize_shops($data) {
    if (!count($data)) {
        return "";
    }

    $content = "#SHOPS\n\n";

    for ($i = 0; $i < count($data); ++$i) {
        $shop = $data[$i];

        $content.=sprintf("%-5s", $shop["vnum"])." ";

        for ($j = 0; $j < count($shop["buy_type"]); ++$j) {
            $content.=sprintf("%2s", $shop["buy_type"][$j])." ";
        }

        $content.=sprintf("%3s", $shop["profit_buy"])." ";
        $content.=sprintf("%3s", $shop["profit_sell"])." ";
        $content.=sprintf("%2s", $shop["open_hour"])." ";
        $content.=sprintf("%2s", $shop["close_hour"])."\n";
    }

    return $content."\n0\n\n";
}

function serialize_specials($data) {
    if (!count($data)) {
        return "";
    }

    $content = "#SPECIALS\n\n";

    for ($i = 0; $i < count($data); ++$i) {
        $special = $data[$i];

        $content.=$special["type"]." ".sprintf("%5s", $special["vnum"])." ";
        $content.=$special["fun"];
        $content.="\n";
    }

    return $content."\nS\n\n";
}

function serialize_objects($data) {
    $content = "#OBJECTS\n\n";
    $objects = get_vnums_as_dictionary($data);

    ksort($objects);

    foreach ($objects as $vnum=>$obj) {
        $content .= serialize_object($obj);
    }

    $content .= "#0\n\n";

    return $content;
}

function serialize_object($data) {
    $content = "#".$data["vnum"]."\n";

    $content.=$data["name"]."^\n";
    $content.=$data["short"]."^\n";
    $content.=$data["description"]."^\n";
    $content.=$data["material"]." ";
    $content.=$data["item_type"]." ";
    $content.=serialize_flags($data["extra_flags"])." ";
    $content.=serialize_flags($data["wear_flags"])."\n";
    $content.=$data["v0"]." ";
    $content.=$data["v1"]." ";
    $content.=$data["v2"]." ";
    $content.=$data["v3"]." ";
    $content.=$data["limit"]."\n";
    $content.=$data["level"]." ";
    $content.=$data["weight"]." ";
    $content.=$data["cost"]." ";
    $content.=$data["condition"]."\n";

    if (array_key_exists("affects", $data)) {
        for ($i=0; $i<count($data["affects"]); ++$i) {
            $aff = $data["affects"][$i];

            $content.="A ".$aff["location"]." ".$aff["modifier"]."\n";
        }
    }

    if (array_key_exists("extra_desc", $data)) {
        for ($i=0; $i<count($data["extra_desc"]); ++$i) {
            $ed = $data["extra_desc"][$i];

            $content.="E\n".$ed["keyword"]."^\n".$ed["content"]."^\n";
        }
    }

    if (array_key_exists("dp", $data)) {
        $content.="D ".$data["dp"]."\n";
    }

    return $content."\n";
}

function serialize_rooms($data) {
    $content = "#ROOMS\n\n";
    $rooms = get_vnums_as_dictionary($data);

    ksort($rooms);

    foreach ($rooms as $vnum=>$room) {
        $content .= serialize_room($room);
    }

    $content .= "#0\n\n";

    return $content;
}

function serialize_room($data) {
    $content = "#".$data["vnum"]."\n";

    $content.=$data["name"]."^\n";
    $content.=$data["description"]."^\n";
    $content.=$data["area_index"]." ";
    $content.=serialize_flags($data["flags"])." ";
    $content.=$data["sector_type"]."\n";

    for ($i=0; $i<count($data["exits"]); ++$i) {
        $exit = $data["exits"][$i];

        $content.="D".$exit["direction"]."\n";
        $content.=$exit["description"]."^\n";
        $content.=$exit["keyword"]."^\n";
        $content.=serialize_flags($exit["info"])." ";
        $content.=$exit["key"]." ".$exit["to_room"];

        if (array_key_exists("move", $exit)) {
            $content.=" ".$exit["move"]["percent"]."\n";
            $content.=$exit["move"]["name"]."^";
        }

        $content.="\n";
    }

    for ($i=0; $i<count($data["extra_desc"]); ++$i) {
        $ed = $data["extra_desc"][$i];
        $content.="E\n".$ed["keyword"]."^\n".$ed["description"]."^\n";
    }

    return $content."S\n\n";
}

function serialize_helps($data) {
    if (!count($data)) {
        return "";
    }

    $content = "#HELPS\n\n";

    for ($i = 0; $i < count($data); ++$i) {
        $content.=$data[$i]["level"]." ".$data[$i]["keyword"]."^\n";
        $content.=$data[$i]["text"]."^\n\n";
    }

    return $content."0 $^\n\n";
}

function serialize_socials($data) {
    if (!count($data)) {
        return "";
    }

    $content = "#SOCIALS\n\n";

    for ($i = 0; $i < count($data); ++$i) {
        $social = $data[$i];

        $content.=$social["name"].$social["after_name"]."\n";

        if (!array_key_exists("char_no_arg", $social)) {
            $content.="#\n\n";
            continue;
        }

        $content.=(
            $social["char_no_arg"] === null ? "$" : $social["char_no_arg"]
        )."\n";

        if (!array_key_exists("others_no_arg", $social)) {
            $content.="#\n\n";
            continue;
        }

        $content.=(
            $social["others_no_arg"] === null ? "$" : $social["others_no_arg"]
        )."\n";

        if (!array_key_exists("char_found", $social)) {
            $content.="#\n\n";
            continue;
        }

        $content.=(
            $social["char_found"] === null ? "$" : $social["char_found"]
        )."\n";

        if (!array_key_exists("others_found", $social)) {
            $content.="#\n\n";
            continue;
        }

        $content.=(
            $social["others_found"] === null ? "$" : $social["others_found"]
        )."\n";

        if (!array_key_exists("vict_found", $social)) {
            $content.="#\n\n";
            continue;
        }

        $content.=(
            $social["vict_found"] === null ? "$" : $social["vict_found"]
        )."\n";

        if (!array_key_exists("char_not_found", $social)) {
            $content.="#\n\n";
            continue;
        }

        $content.=(
            $social["char_not_found"] === null ? "$" : $social["char_not_found"]
        )."\n";

        if (!array_key_exists("char_auto", $social)) {
            $content.="#\n\n";
            continue;
        }

        $content.=(
            $social["char_auto"] === null ? "$" : $social["char_auto"]
        )."\n";

        if (!array_key_exists("others_auto", $social)) {
            $content.="#\n\n";
            continue;
        }

        $content.=(
            $social["others_auto"] === null ? "$" : $social["others_auto"]
        )."\n\n";
    }

    return $content."#0\n\n";
}

function serialize_flags($value) {
    $flags = array(
        1 => "A",
        2 => "B",
        4 => "C",
        8 => "D",
        16 => "E",
        32 => "F",
        64 => "G",
        128 => "H",
        256 => "I",
        512 => "J",
        1024 => "K",
        2048 =>"L",
        4096 => "M",
        8192 => "N",
        16384 => "O",
        32768 => "P",
        65536 => "Q",
        131072 => "R",
        262144 => "S",
        524288 => "T",
        1048576 => "U",
        2097152 => "V",
        4194304 => "W",
        8388608 => "X",
        16777216 => "Y",
        33554432 => "Z",
        67108864 => "aa",
        134217728 => "bb",
        268435456 => "cc",
        536870912 => "dd",
        1073741824 => "ee"
    );

    $content = "";

    foreach ($flags as $number=>$letter) {
        if ($number & $value) {
            $content.=$letter;
        }
    }

    return strlen($content) > 0 ? $content : "0";
}
