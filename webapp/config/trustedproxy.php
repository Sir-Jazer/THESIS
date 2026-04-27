<?php

return [
    // Set trusted proxies to allow ngrok and other proxies
    'proxies' => '*', // Trust all proxies (for dev/tunnel use only)
    'headers' => [
        \Illuminate\Http\Request::HEADER_FORWARDED => 'FORWARDED',
        \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR => 'X_FORWARDED_FOR',
        \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST => 'X_FORWARDED_HOST',
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT => 'X_FORWARDED_PORT',
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO => 'X_FORWARDED_PROTO',
    ],
];
