<?php

// Debug
$app['debug'] = true;

$app['aws.environment'] = "AWSUrlNoTrailingSlash";
$app['aws.config'] = [
    'key' => 'AKIAJQUE6FGZILFC5NUA',
    'secret' => '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',
    'region' => 'us-east-1',
    'default_cache_config' => 'apc',
    'certificate_authority' => false,
];

