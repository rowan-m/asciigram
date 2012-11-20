<?php
$app = require __DIR__.'/bootstrap.php';

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;

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

    return $app['twig']->render('upload.html.twig', array('form' => $form->createView()));
})->method('GET|POST')->bind('upload');

$app->post('/process', function() use ($app) {
    // SNS posts us JSON in the request body
    $message = json_decode(file_get_contents('php://input'), true);

    if (!is_null($message)) {
        $app['asciigram.image_transformer']->handleMessage($message);
    }
});

$app->get('/gram/{gramified}', function($gramified) use ($app) {
    if ( ! $gram = $app['asciigram.image_lister']->fetchGram($app->escape($gramified))) 
    {
        $app->abort(404, "Gram does not exist, or hasn't completed yet");
    }

    return $app['twig']->render('gram.html.twig', array('gram' => $gram));
})->bind('gram');

$app->error(function (\Exception $e, $code) use ($app) { 
    if ($app['debug']) {
        return;
    }
    
    if (404 == $code) {
        $message = "The requested page could not be found."; 
    } else {
        $message = "Something went wrong";
    } 

    return $app['twig']->render('error.html.twig', array('code' => $code, 'message' => $message));
});

$app->get('/', function() use ($app) {
    $view = array('grams' => $app['asciigram.image_lister']->fetchLatestGrams());
    return $app['twig']->render('index.html.twig', array('view' => $view));
})->bind('homepage');

return $app;
