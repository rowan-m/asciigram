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


// Temporary hack. Silex should start session on demand.
$app->before(function() use ($app) {
    $app['session']->start();
});

return $app;