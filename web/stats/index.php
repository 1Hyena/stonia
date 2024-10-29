<?php
date_default_timezone_set('UTC');

header('Content-type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'GET'
||  $_SERVER['REQUEST_METHOD'] == 'HEAD') {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $response = unarchive();
        echo json_encode($response);
    }

    http_response_code(201);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    header('Allow: GET, POST, HEAD');

    echo json_encode(
        array('error' => 'method not supported: '.$_SERVER['REQUEST_METHOD'])
    );

    exit;
}

$request = json_decode(
    file_get_contents("php://input"),
    true, 4, JSON_BIGINT_AS_STRING|JSON_INVALID_UTF8_IGNORE
);

handle_request($request);

function archive($data) {
    $data = json_encode($data);

    if ($data === false) {
        return "unable to encode json";
    }

    $filename = "data.zip";
    $zip = new ZipArchive;

    $res = $zip->open($filename, ZipArchive::CREATE);

    if ($res !== true) {
        return "unable to open $filename";
    }

    $date = new DateTimeImmutable();
    $dirname = date_format($date, 'Y-m');
    $day = date_format($date, 'd');

    $zip->addEmptyDir($dirname, ZipArchive::FL_ENC_UTF_8);

    if ($zip->locateName($dirname."/") === false) {
        $zip->close();
        return "unable to create directory $dirname in $filename";
    }

    $before = $zip->getFromName($dirname."/".$day);

    if ($before === false) {
        $before = "";
    }

    $result = $zip->addFromString(
        $dirname."/".$day, $before.$data."\n",
        ZipArchive::FL_ENC_UTF_8|ZipArchive::FL_OVERWRITE
    );

    $zip->close();

    if ($result === false) {
        return "failed to archive data";
    }

    return null;
}

function unarchive() {
    $filename = "data.zip";
    $zip = new ZipArchive;

    $res = $zip->open($filename, ZipArchive::RDONLY);

    if ($res !== true) {
        return array('error' => "unable to open $filename");
    }

    $date = new DateTime();
    $date->modify("-6 day");

    $result = "";

    for ($i = 0; $i < 7; $i++) {
        $dirname = date_format($date, 'Y-m');
        $day = date_format($date, 'd');
        $contents = $zip->getFromName($dirname."/".$day);

        if ($contents !== false) {
            $result.=$contents;
        }

        $date->modify("+1 day");
    }

    $lines = explode("\n", $result);
    $result = array(
        'error' => null,
        'csv' => "timestamp,category,count\n"
    );

    foreach ($lines as &$line) {
        $count = json_decode(
            $line, true, 4, JSON_BIGINT_AS_STRING|JSON_INVALID_UTF8_IGNORE
        );

        if ($count === null) {
            continue;
        }

        $white = $count['online']['white'];
        $black = $count['online']['black'];
        $brown = $count['online']['brown'];
        $misty = $count['online']['misty'];
        $total = $white + $black + $brown + $misty;

        $result['csv'].=$count['time'].",white,".$white."\n";
        $result['csv'].=$count['time'].",black,".$black."\n";
        $result['csv'].=$count['time'].",brown,".$brown."\n";
        $result['csv'].=$count['time'].",misty,".$misty."\n";
        $result['csv'].=$count['time'].",total,".$total."\n";
    }

    unset($line);

    return $result;
}

function handle_request($request) {
    if ($request === null || !array_key_exists('fun', $request)) {
        return abort_request("invalid request", 400);
    }

    if ($request['fun'] === 'add_count') {
        return fun_add_count($request);
    }
    else if ($request['fun'] === 'plot_count') {
        return fun_plot_count($request);
    }

    return abort_request("invalid fun parameter", 400);
}

function abort_request($error, $code) {
    $response = array(
        'error' => $error
    );

    http_response_code($code);
    echo json_encode($response);

    exit;
}

function fun_add_count($request) {
    if (!array_key_exists('count', $request)
    || !is_array($request['count'])
    || !array_key_exists('time', $request['count'])
    || !is_int($request['count']['time'])
    || !array_key_exists('online', $request['count'])
    || !is_array($request['count']['online'])
    || !array_key_exists('white', $request['count']['online'])
    || !array_key_exists('black', $request['count']['online'])
    || !array_key_exists('brown', $request['count']['online'])
    || !array_key_exists('misty', $request['count']['online'])
    || !is_int($request['count']['online']['white'])
    || !is_int($request['count']['online']['black'])
    || !is_int($request['count']['online']['brown'])
    || !is_int($request['count']['online']['misty'])) {
        return abort_request("invalid request parameters", 400);
    }

    $count = array(
        'time' => $request['count']['time'],
        'online' => array(
            'white' => $request['count']['online']['white'],
            'black' => $request['count']['online']['black'],
            'brown' => $request['count']['online']['brown'],
            'misty' => $request['count']['online']['misty']
        )
    );

    $error = archive($count);

    if ($error !== null) {
        return abort_request($error, 500);
    }

    http_response_code(201);

    echo json_encode(
        array('error' => null)
    );
}

function fun_plot_count($request) {
    if (!array_key_exists('image', $request)
    ||  !is_string($request['image'])) {
        return abort_request("invalid request parameters", 400);
    }

    $image = base64_decode($request['image'], true);

    if ($image === false) {
        return abort_request("invalid base64 in image", 400);
    }

    if (file_put_contents("../files/count.png", $image) === false) {
        return abort_request("failed to save the image", 500);
    }

    http_response_code(201);

    echo json_encode(
        array('error' => null)
    );
}
