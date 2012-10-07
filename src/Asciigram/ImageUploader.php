<?php

namespace Asciigram;

class ImageUploader
{
    /**
     * @var S3Service
     */
    protected $s3service;

    /**
     * @var S3Service
     */
    protected $snsService;

    public function __construct(S3Service $s3service, SNSService $snsService)
    {
        $this->s3service = $s3service;
        $this->snsService = $snsService;
    }

    public function persist(ImageUpload $imageupload)
    {
        $s3Name = $this->s3service->persistImageUpload($imageupload);
        $this->snsService->sendNotification($imageupload, $s3Name);
    }
}
