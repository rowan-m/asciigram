<?php

namespace Asciigram;

use Aws\S3\S3Client;
use Aws\S3\Enum\CannedAcl;
use Aws\S3\Exception\S3Exception;

class S3Service
{
    /**
     * @var S3Client
     */
    protected $s3;

    /**
     * @param string
     */
    protected $bucket;

    public function __construct(S3Client $s3)
    {
        $this->s3 = $s3;
        $this->bucket = 'asciigram-' . strtolower($this->s3->getCredentials()->getAccessKeyId());
    }

    public function persistImageUpload(ImageUpload $imageUpload)
    {
        $this->initS3Bucket();

        // Upload the image
        try {
            $imageName = uniqid();
            $this->s3->putObject(array(
                'Bucket'     => $this->bucket,
                'Key'        => $imageName,
                'ACL'        => CannedAcl::PUBLIC_READ,
                'SourceFile' => $imageUpload->getImage()->getPathname(),
            ));

            return $imageName;
        } catch (S3Exception $e) {
            return false;
        }
    }

    public function persistGramified($text)
    {
        $this->initS3Bucket();

        // Upload the image
        try {
            $textName = uniqid();
            $this->s3->putObject(array(
                'Bucket' => $this->bucket,
                'Key'    => $textName,
                'ACL'    => CannedAcl::PUBLIC_READ,
                'Body'   => $text,
            ));

            return $textName;
        } catch (S3Exception $e) {
            return false;
        }
    }

    protected function initS3Bucket()
    {
        if ( ! $this->s3->doesBucketExist($this->bucket)) {
            $this->s3->createBucket(array(
                'Bucket' => $this->bucket,
                'ACL'    => CannedAcl::PUBLIC_READ,
            ));

            $this->s3->waitUntilBucketExists(array('Bucket' => $this->bucket));
        }
    }

    public function getImageUrl($imageName)
    {
        return (string) $this->getObjectCommand($imageName)->getRequest()->getUrl();
    }

    public function getGramified($textName)
    {
        return (string) $this->getObjectCommand($textName)->getResult()->get('Body');
    }

    protected function getObjectCommand($key)
    {
        return $this->s3->getCommand('GetObject', array(
            'Bucket' => $this->bucket,
            'Key'    => $key,
        ));
    }
}
