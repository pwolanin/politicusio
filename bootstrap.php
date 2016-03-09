<?php

require 'vendor/autoload.php';

define('DATABASE_HOST', 'localhost');
define('DATABASE_NAME', 'politicusio');
define('DATABASE_USER', 'root');
define('DATABASE_PASS', '');

ORM::configure(
    array(
        'connection_string' => 'mysql:host=' . DATABASE_HOST . ';dbname=' . DATABASE_NAME,
        'username' => DATABASE_USER,
        'password' => DATABASE_PASS,
    )
);