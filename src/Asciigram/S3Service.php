<?php

namespace Asciigram;
class S3Service
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \AmazonS3
     */
    protected $amazonS3;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return \AmazonS3
     */
    protected function getS3()
    {
        if ($this->amazonS3) {
            return $this->amazonS3;
        }

        return new \AmazonS3($this->config);
    }

    public function persistImageUpload(ImageUpload $imageupload)
    {
        $s3 = $this->getS3();

        // initialise bucket
        $bucket = 'asciigram-' . strtolower($s3->key);
        $this->initS3Bucket($bucket);

        // Upload the image
        $imageName = uniqid();
        $response = $s3->create_object(
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
        $s3 = $this->getS3();

        // initialise bucket
        $bucket = 'asciigram-' . strtolower($s3->key);
        $this->initS3Bucket($bucket);

        // Upload the image
        $textName = uniqid();
        $response = $s3->create_object(
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
        $s3 = $this->getS3();

        if (!$s3->if_bucket_exists($bucket)) {
            $response = $s3->create_bucket(
                $bucket,
                \AmazonS3::REGION_US_STANDARD,
                \AmazonS3::ACL_PUBLIC
            );

            if ($response->isOk()) {
                $exists = $s3->if_bucket_exists($bucket);

                while (!$exists) {
                    sleep(1);
                    $exists = $s3->if_bucket_exists($bucket);
                }
            }
        }
    }

    public function getImageUrl($imageName)
    {
        $s3 = $this->getS3();

        // initialise bucket
        $bucket = 'asciigram-' . strtolower($s3->key);
        return $s3->get_object_url($bucket, $imageName);
    }

    public function getGramified($textName)
    {
        $s3 = $this->getS3();

        // initialise bucket
        $bucket = 'asciigram-' . strtolower($s3->key);
        $response = $s3->get_object($bucket, $textName);
        return (string) $response->body;
    }
}
