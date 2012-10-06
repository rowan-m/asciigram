<?php
ini_set('display_errors', 1);
error_reporting(-1);

$app = require __DIR__.'/../src/app.php';
$app->run();
