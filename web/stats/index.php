<?php

header('Content-type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'GET'
||  $_SERVER['REQUEST_METHOD'] == 'HEAD') {
    http_response_code(501);

    echo json_encode(
        array('error' => 'method not implemented: '.$_SERVER['REQUEST_METHOD'])
    );

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

$error = null;

if ($request === null
|| !array_key_exists('fun', $request)
|| !array_key_exists('count', $request)
|| $request['fun'] !== 'add_count'
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
    $error = "invalid request parameters";
}

if ($error !== null) {
    $response = array(
        'error' => $error
    );

    http_response_code(400);
    echo json_encode($response);

    exit;
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

file_put_contents("data", json_encode($count)."\n", LOCK_EX|FILE_APPEND);

http_response_code(201);

echo json_encode(
    array('error' => null)
);
