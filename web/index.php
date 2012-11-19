<?php
ini_set('display_errors', 1);
error_reporting(-1);

date_default_timezone_set("Europe/Lisbon");

$app = require __DIR__.'/../src/app.php';
$app->run();
