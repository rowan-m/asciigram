<?php

// Debug
$app['debug'] = true;

// Local
$app['locale'] = 'en';
$app['session.default_locale'] = $app['locale'];
$app['aws.environment'] = "AWSUrlNoTrailingSlash";
$app['aws.config'] = array(
    'key' => 'QuiteSecret',
    'secret' => 'OoohDoublySuperSecret',
    'region' => 'us-east-1',
    'default_cache_config' => 'apc',
    'certificate_authority' => false,
);
