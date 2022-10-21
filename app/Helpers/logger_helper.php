<?php

function log_event($msg, $data)
{
    $logger = \Config\Services::logger();
    $message = [
        'message' => $msg,
        'user' => [
            'id' => user_id(),
            'name' => user()->name,
        ],
        'data' => $data,
    ];

    $logger->notice(json_encode($message));
}
