<?php

$loader = require  __DIR__.'/../vendor/autoload.php';

// Drop in our own namespace for application classes
$loader->add('Asciigram', __DIR__);

use Silex\Provider\SessionServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;

use Symfony\Component\Translation\Loader\YamlFileLoader;

$app = new Silex\Application();

require __DIR__ . '/config.php';


$app->register(new SessionServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new FormServiceProvider());
$app->register(new UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallback' => 'en',
));
$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());
    $translator->addResource('yaml', __DIR__.'/resources/locales/en.yml', 'en');
    return $translator;
}));

$app->register(new TwigServiceProvider(), array(
    'twig.options'          => array('cache' => '/tmp/twig-cache', 'strict_variables' => true),
    'twig.form.templates'   => array('form_div_layout.html.twig'),
    'twig.path'             => array(__DIR__ . '/templates')
));

$app['asciigram.amazonS3'] = $app->share(function ($app) {
    return new \AmazonS3($app['aws.config']);
});

$app['asciigram.amazonSNS'] = $app->share(function ($app) {
    return new \AmazonSNS($app['aws.config']);
});

$app['asciigram.amazonDynamoDB'] = $app->share(function ($app) {
    return new \AmazonDynamoDB($app['aws.config']);
});

$app['asciigram.s3service'] = $app->share(function ($app) {
    return new Asciigram\S3Service($app['asciigram.amazonS3']);
});

$app['asciigram.snsService'] = $app->share(function ($app) {
    return new Asciigram\SNSService($app['asciigram.amazonSNS']);
});

$app['asciigram.dynamoDbService'] = $app->share(function ($app) {
    return new Asciigram\DynamoDbService($app['asciigram.amazonDynamoDB']);
});

$app['asciigram.image_uploader'] = $app->share(function ($app) {
    return new Asciigram\ImageUploader(
        $app['asciigram.s3service'],
        $app['asciigram.snsService']
    );
});

$app['asciigram.image_transformer'] = $app->share(function ($app) {
    return new Asciigram\ImageTransformer(
        $app['asciigram.s3service'],
        $app['asciigram.snsService'],
        $app['asciigram.dynamoDbService']
    );
});

$app['asciigram.image_lister'] = $app->share(function ($app) {
    return new Asciigram\ImageLister(
        $app['asciigram.s3service'],
        $app['asciigram.dynamoDbService']
    );
});

// Temporary hack. Silex should start session on demand.
$app->before(function() use ($app) {
    $app['session']->start();
});

return $app;
