<?php

use Symfony\Component\Validator\Constraints as Assert;

$app = require_once __DIR__ . '/bootstrap.php';

$app->get('/hello/{name}', function ($name) use ($app) {
    return 'Hello '.$app->escape($name);
});

$app->match('/upload', function() use ($app) {
    $imageupload = new Asciigram\ImageUpload();
    $form = $app['form.factory']->createBuilder('form', $imageupload)
        ->add('image', 'file', array(
            'label' => 'upload.image',
            'constraints' => array(
                new Assert\NotBlank(),
            ),
        ))
        ->add('message', 'text', array(
            'label' => 'upload.message',
            'constraints' => array(
                new Assert\NotBlank(),
            ),
        ))
        ->getForm();

    if ('POST' === $app['request']->getMethod()) {
        $form->bindRequest($app['request']);
        if ($form->isValid()) {
            $app['session']->setFlash('success', "upload.success");
            $app['asciigram.image_uploader']->persist($imageupload);
        }
    }

    return $app->render('upload.html.twig', ['form' => $form->createView()]);
})->method('GET|POST')->bind('upload');

$app->post('/process', function() use ($app) {
    // SNS posts us JSON in the request body
    $message = json_decode(file_get_contents('php://input'), true);

    if (!is_null($message)) {
        $app['asciigram.image_transformer']->handleMessage($message);
    }
})->bind('process');;

$app->get('/gram/{gramified}', function($gramified) use ($app) {
    if ( ! $gram = $app['asciigram.image_lister']->fetchGram($app->escape($gramified)))
    {
        $app->abort(404, "Gram does not exist, or hasn't completed yet");
    }

    return $app->render('gram.html.twig', ['gram' => $gram]);
})->bind('gram');

$app->get('/', function () use ($app) {
    return $app->render('home.html.twig', ['grams' => $app['asciigram.image_lister']->fetchLatestGrams()]);
})->bind('home');

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'We are sorry, but something went terribly wrong.';
    }

    return $app->render('error.html.twig', ['message' => $message]);
});

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