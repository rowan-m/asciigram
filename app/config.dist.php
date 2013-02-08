<?php

// Debug
$app['debug'] = true;

$app['aws.environment'] = "AWSUrlNoTrailingSlash";
$app['aws.config'] = [
    'key' => 'QuiteSecret',
    'secret' => 'OoohDoublySuperSecret',
    'region' => 'us-east-1',
    'default_cache_config' => 'apc',
    'certificate_authority' => false,
];

