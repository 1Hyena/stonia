<?php
/* Example configuration file:
    {
        "mud": {
            "host": "stonia.ttu.ee",
            "port": 4000,
            "account": "",
            "password": ""
        },
        "api": {
            "address": "https://stonia.net.ee/stats/",
            "username": "",
            "password": ""
        }
    }
*/

error_reporting(E_ALL);
date_default_timezone_set('UTC');

if (count($argv) < 2)  {
    echo "usage: conf.json\n";
    exit;
}

$conf = @file_get_contents($argv[1]);

if ($conf === false) {
    echo "unable to read ".$argv[1]."\n";
    exit;
}

$conf = json_decode(
    $conf, true, 4, JSON_BIGINT_AS_STRING|JSON_INVALID_UTF8_IGNORE
);

if ($conf === null
|| !array_key_exists("mud", $conf)
|| !is_array($conf['mud'])
|| !array_key_exists("api", $conf)
|| !is_array($conf['api'])
|| !array_key_exists("host", $conf['mud'])
|| !array_key_exists("port", $conf['mud'])
|| !array_key_exists("account", $conf['mud'])
|| !array_key_exists("password", $conf['mud'])
|| !array_key_exists("address", $conf['api'])
|| !array_key_exists("username", $conf['api'])
|| !array_key_exists("password", $conf['api'])
|| !is_string($conf['mud']['host'])
|| (!is_string($conf['mud']['port']) && !is_int($conf['mud']['port']))
|| !is_string($conf['mud']['account'])
|| !is_string($conf['mud']['password'])
|| !is_string($conf['api']['address'])
|| !is_string($conf['api']['username'])
|| !is_string($conf['api']['password'])) {
    echo "invalid configuration in ".$argv[1]."\n";
    exit;
}

$user = $conf['mud']['account'];
$pass = $conf['mud']['password'];
$port = $conf['mud']['port'];
$addr = gethostbyname($conf['mud']['host']);

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

if ($socket === false) {
    echo(
        "socket_create() failed: reason: ".
        socket_strerror(socket_last_error())."\n"
    );

    exit;
}

log_line("connecting to $addr on port $port");

$result = @socket_connect($socket, $addr, $port);
$errno = socket_last_error($socket);
socket_clear_error($socket);

if ($result === false) {
    log_line("socket_connect() failed: ".socket_strerror($errno));
    exit;
}

$command = $user."\n".$pass."\n";

if (@socket_write($socket, $command, strlen($command)) === false) {
    $errno = socket_last_error($socket);
    socket_clear_error($socket);
    log_line("socket_write() failed: ".socket_strerror($errno));
    exit;
}

socket_set_nonblock($socket);

$state = array(
    'conf' => $conf,
    'count' => ""
);

$buffer = "";

while (true) {
    $out = socket_read($socket, 1024);

    if ($out === false || strcmp($out, '') == 0) {
        $errno = socket_last_error($socket);
        socket_clear_error($socket);

        if ($errno == SOCKET_EAGAIN) {
            $command = "play\n \n";

            if (@socket_write($socket, $command, strlen($command)) === false) {
                $errno = socket_last_error($socket);
                socket_clear_error($socket);
                log_line("socket_write() failed: ".socket_strerror($errno));
            }

            sleep(10);
        } else {
            log_line("code $errno when reading");

            break;
        }
    } else {
        $buffer.=$out;
        $buffer = process_buffer($state, $buffer);
    }
}


log_line("closing the socket");
socket_close($socket);

function process_buffer(&$state, $message) {
    $last_pos = 0;
    $msglen = strlen($message);

    while ($last_pos < $msglen) {
        $pos = strpos($message, "\n", $last_pos);

        if ($pos === false) {
            return substr($message, $last_pos);
        }

        process_line($state, substr($message, $last_pos, $pos - $last_pos));
        $last_pos = $pos + 1;
    }

    return "";
}

function process_line(&$state, $line) {
    $line = str_replace("\r", "", $line);

    if (preg_match("/white: /", $line) === 1
    &&  preg_match("/black: /", $line) === 1
    &&  preg_match("/brown: /", $line) === 1
    &&  preg_match("/misty: /", $line) === 1) {
        $white = intval(substr($line, strpos($line, "white: ") + 7));
        $black = intval(substr($line, strpos($line, "black: ") + 7));
        $brown = intval(substr($line, strpos($line, "brown: ") + 7));
        $misty = intval(substr($line, strpos($line, "misty: ") + 7));

        $url = $state['conf']['api']['address'];
        $data = json_encode(
            array(
                'fun' => 'add_count',
                'count' => array(
                    'time' => time(),
                    'online' => array(
                        'white' => $white,
                        'black' => $black,
                        'brown' => $brown,
                        'misty' => $misty
                    )
                )
            )
        );

        $username = $state['conf']['api']['username'];
        $password = $state['conf']['api']['password'];
        $auth = base64_encode( "{$username}:{$password}" );

        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n".
                            "Content-Length: ".strlen($data)."\r\n".
                            "Authorization: Basic ".$auth,
                'method' => 'POST',
                'content' => $data,
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            log_line("error when posting count");
        }
        else {
            $count = (
                "white: $white, black: $black, brown: $brown, misty: $misty"
            );

            log_line($count);

            if ($state['count'] !== $count) {
                $state['count'] = $count;

                render($state);
            }
        }
    }
}

function render(&$state) {
    $username = $state['conf']['api']['username'];
    $password = $state['conf']['api']['password'];
    $auth = base64_encode( "{$username}:{$password}" );

    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n".
                        "Authorization: Basic ".$auth,
            'method' => 'GET'
        ],
    ];

    $url = $state['conf']['api']['address'];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === false) {
        log_line("failed to get data to render");
        return;
    }

    $result = json_decode(
        $result,
        true, 4, JSON_BIGINT_AS_STRING|JSON_INVALID_UTF8_IGNORE
    );

    $csv = $result['csv'];

    if (file_put_contents("count.csv", $csv) === false) {
        log_line("failed to write CSV file for plotting");
        return;
    }

    $mtime = null;

    if (file_exists("count.png")) {
        $mtime = filemtime("count.png");
    }

    shell_exec("gnuplot -c count.plot");
    clearstatcache();

    if (!file_exists("count.png") || filemtime("count.png") === $mtime) {
        log_line("failed to render the plot");
    }
    else {
        log_line("a new plot has been rendered");
        upload($state, "count.png");
    }
}

function upload(&$state, $filepath) {
    $url = $state['conf']['api']['address'];
    $data = json_encode(
        array(
            'fun' => 'plot_count',
            'image' => base64_encode(file_get_contents($filepath))
        )
    );

    $username = $state['conf']['api']['username'];
    $password = $state['conf']['api']['password'];
    $auth = base64_encode( "{$username}:{$password}" );

    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n".
                        "Content-Length: ".strlen($data)."\r\n".
                        "Authorization: Basic ".$auth,
            'method' => 'POST',
            'content' => $data,
        ],
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === false) {
        log_line("error when uploading ".$filepath);
    }
    else {
        log_line($filepath." uploaded");
    }
}

function log_line($message) {
    $date = new DateTimeImmutable();

    echo(date_format($date, '[ Y-m-d H:i:s ]')." :: ".$message."\n");
}

?>
