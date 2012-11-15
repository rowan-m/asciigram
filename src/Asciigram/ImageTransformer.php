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
    protected $DynamoDBService;

    public function __construct(S3Service $s3service, SNSService $snsService, DynamoDBService $DynamoDBService)
    {
        $this->s3service = $s3service;
        $this->snsService = $snsService;
        $this->DynamoDBService = $DynamoDBService;
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

            $db = new DynamoDBService($this->config);
            $db->persist($message['Subject'], $textName, $message['Message']);
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
        $width = imagesx($img);
        $height = imagesy($img);

        // loop for height
        for ($h = 0; $h < $height; $h++) {
            // loop for height
            for ($w = 0; $w < $width; $w++) {
                // add color
                $rgb = imagecolorat($img, $w, $h);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                // create a hex value from the rgb
                $hex = '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
                    . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
                    . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

                // now add to the return string and we are done
                if ($w == ($width - 1)) {
                    $text .= '<br />';
                } else {
                    $text .= '<span style="color:' . $hex . ';">#</span>';
                }
            }
        }

        return $text;
    }
}
