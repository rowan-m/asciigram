<?php

namespace Asciigram;
class S3Service
{
    /**
     * @var \AmazonS3
     */
    protected $amazonS3;

    public function __construct(\AmazonS3 $amazonS3)
    {
        $this->amazonS3 = $amazonS3;
    }

    public function persistImageUpload(ImageUpload $imageupload)
    {
        // initialise bucket
        $bucket = 'asciigram-' . strtolower($this->amazonS3->key);
        $this->initS3Bucket($bucket);

        // Upload the image
        $imageName = uniqid();
        $response = $this->amazonS3->create_object(
            $bucket,
            $imageName,
            array(
                'fileUpload' => $imageupload->getImage()->getPathname(),
                'acl' => \AmazonS3::ACL_PUBLIC,
            )
        );

        if ($response->isOk()) {
            return $imageName;
        }
    }

    public function persistGramified($text)
    {
        // initialise bucket
        $bucket = 'asciigram-' . strtolower($this->amazonS3->key);
        $this->initS3Bucket($bucket);

        // Upload the image
        $textName = uniqid();
        $response = $this->amazonS3->create_object(
            $bucket,
            $textName,
            array(
                'body' => $text,
                'acl' => \AmazonS3::ACL_PUBLIC,
            )
        );

        if ($response->isOk()) {
            return $textName;
        }
    }

    protected function initS3Bucket($bucket)
    {
        if (!$this->amazonS3->if_bucket_exists($bucket)) {
            $response = $this->amazonS3->create_bucket(
                $bucket,
                \AmazonS3::REGION_US_STANDARD,
                \AmazonS3::ACL_PUBLIC
            );

            if ($response->isOk()) {
                $exists = $this->amazonS3->if_bucket_exists($bucket);

                while (!$exists) {
                    sleep(1);
                    $exists = $this->amazonS3->if_bucket_exists($bucket);
                }
            }
        }
    }

    public function getImageUrl($imageName)
    {
        // initialise bucket
        $bucket = 'asciigram-' . strtolower($this->amazonS3->key);
        return $this->amazonS3->get_object_url($bucket, $imageName);
    }

    public function getGramified($textName)
    {
        // initialise bucket
        $bucket = 'asciigram-' . strtolower($this->amazonS3->key);
        $response = $this->amazonS3->get_object($bucket, $textName);
        return (string) $response->body;
    }
}
