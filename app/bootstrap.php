<?php

$app = new \Asciigram\Asciication;

require __DIR__ . '/config.php';

$app->register(new Silex\Provider\SessionServiceProvider);

$app->register(new Silex\Provider\TwigServiceProvider, [
    'twig.options' => ['cache' => '/tmp/twig-cache', 'strict_variables' => true],
    'twig.form.templates' => ['form_div_layout.html.twig'],
    'twig.path' => __DIR__ . '/views',
]);

$app->register(new Silex\Provider\UrlGeneratorServiceProvider);

$app->register(new Silex\Provider\FormServiceProvider);

$app->register(new Silex\Provider\ValidatorServiceProvider);

$app->register(new Silex\Provider\TranslationServiceProvider, ['locale_fallback' => 'en']);

$app->register(new Aws\Silex\AwsServiceProvider);

$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
    $translator->addLoader('yaml', new Symfony\Component\Translation\Loader\YamlFileLoader());
    $translator->addResource('yaml', __DIR__.'/translations/en.yml', 'en');
    return $translator;
}));

$app['aws.s3'] = $app->share(function ($app) {
    return $app['aws']->get('s3');
});

$app['aws.sns'] = $app->share(function ($app) {
    return new \AmazonSNS($app['aws.config']);
});

$app['aws.dynamodb'] = $app->share(function ($app) {
    return $app['aws']->get('dynamodb');
});

$app['asciigram.s3service'] = $app->share(function ($app) {
    return new Asciigram\S3Service($app['aws.s3']);
});

$app['asciigram.snsService'] = $app->share(function ($app) {
    return new Asciigram\SNSService($app['aws.sns']);
});

$app['asciigram.dynamoDBService'] = $app->share(function ($app) {
    return new Asciigram\DynamoDBService($app['aws.dynamodb']);
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
        $app['asciigram.dynamoDBService']
    );
});

$app['asciigram.image_lister'] = $app->share(function ($app) {
    return new Asciigram\ImageLister(
        $app['asciigram.s3service'],
        $app['asciigram.dynamoDBService']
    );
});

return $app;