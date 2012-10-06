<?php

namespace Asciigram;

class ImageUploader
{
    /**
     * @var array
     */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function persist(ImageUpload $imageupload)
    {
        $s3 = new S3Service($this->config);
        $s3Name = $s3->persistImageUpload($imageupload);

        $sns = new SNSService($this->config);
        $sns->sendNotification($imageupload, $s3Name);
    }
}
