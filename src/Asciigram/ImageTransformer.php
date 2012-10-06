<?php

namespace Asciigram;

class ImageTransformer
{
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function handleMessage(array $message)
    {
        if ($message['Type'] == 'SubscriptionConfirmation') {
            $sns = new SNSService($this->config);
            $sns->confirmSubscription($message);
        } elseif ($message['Type'] == 'Notification') {
            $s3 = new S3Service($this->config);
            $url = $s3->getImageUrl($message['Subject']);
            $img = $this->resizeImage($url);
            $text = ($img) ? $this->gramifyImage($img) : 'Error!';
            $textName = $s3->persistGramified($text);

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
