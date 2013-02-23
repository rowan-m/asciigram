<?php

namespace Asciigram;

class ImageTransformer
{
    /**
     * @var S3Service
     */
    protected $s3service;

    /**
     * @var S3Service
     */
    protected $snsService;

    /**
     * @var DynamoDBService
     */
    protected $dynamoDBService;

    public function __construct(S3Service $s3service, SNSService $snsService, DynamoDBService $dynamoDBService)
    {
        $this->s3service = $s3service;
        $this->snsService = $snsService;
        $this->dynamoDBService = $dynamoDBService;
    }

    public function handleMessage(array $message)
    {
        if ($message['Type'] == 'SubscriptionConfirmation') {
            $this->snsService->confirmSubscription($message);
        } elseif ($message['Type'] == 'Notification') {
            $url = $this->s3service->getImageUrl($message['Subject']);
            $img = $this->resizeImage($url);
            $text = ($img) ? $this->gramifyImage($img) : 'Error!';
            $textName = $this->s3service->persistGramified($text);

            $this->dynamoDBService->persist($message['Subject'], $textName, $message['Message']);
        }
    }

    protected function resizeImage($url)
    {
        $sizeData = getimagesize($url);
        $origWidth = $sizeData[0];
        $origHeight = $sizeData[1];
        $type = $sizeData['mime'];

        switch ($type) {
            case "image/jpeg":
                $image = imagecreatefromjpeg($url);
                break;
            case "image/gif":
                $image = imagecreatefromgif($url);
                break;
            case "image/png":
                $image = imagecreatefrompng($url);
                break;
            default:
                $image = false;
                break;
        }

        $newWidth = 64;
        $newHeight = 48;

        $origRatio = $origWidth / $origHeight;

        if ($newWidth / $newHeight > $origRatio) {
            $newWidth = $newHeight * $origRatio;
        } else {
            $newHeight = $newWidth / $origRatio;
        }

        // Resample
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        return $resizedImage;
    }

    protected function gramifyImage($img)
    {
        $text = '';
        // get width and height
        $width  = imagesx($img);
        $height = imagesy($img);

        // Initially, no previous colour has been seen, so default to NULL.
        $previousColor = NULL;

        // loop for height
        for ($y = 0; $y < $height; ++$y) {
            // loop for width
            for ($x = 0; $x < $width; ++$x) {
                // add color
                $color = imagecolorat($img, $x, $y);

                // If the current colour is the same as the previous colour,
                // simply append a hash.
                if ($color === $previousColor) {
                    $text .= '#';
                } else {
                    // We arrive here if no previous colour has been seen, or
                    // the previous colour seen differs from the current colour.

                    // If a previous colour has been seen, a span element /must/
                    // have been opened; close it.
                    if ( !is_null($previousColor)) {
                        $text .= '</span>';
                    }

                    // Open a new span element that uses the current colour.
                    $text .= sprintf("<span style=\"color: #%02x%02x%02x\">#",
                        ($color >> 16) & 0xff, ($color >> 8) & 0xff,
                        $color & 0xff);
                }

                // Where this is the last pixel of the current row (x), append
                // a br element to format the asciigram correctly.
                if ($x === ($width - 1)) {
                    $text .= "<br />\n";
                }

                // Update previous colour.
                $previousColor = $color;
            }
        }

        // Close the final span element.
        $text .= '</span>';

        return $text;
    }
}
