<?php

namespace Asciigram;
class ImageLister
{
    /**
     * @var S3Service
     */
    protected $s3service;

    /**
     * @var DynamoDBService
     */
    protected $DynamoDBService;

    public function __construct(S3Service $s3service, DynamoDBService $DynamoDBService)
    {
        $this->s3service = $s3service;
        $this->DynamoDBService = $DynamoDBService;
    }

    public function fetchLatestGrams()
    {
        $raw = $this->DynamoDBService ->getLatestGrams();

        $grams = array();

        foreach ($raw as $protogram) {
            $ref = current($protogram['gramified']);
            $grams[$ref]['uploadDate'] = current($protogram['uploadDate']);
            $grams[$ref]['message'] = current($protogram['message']);
            $grams[$ref]['gramified'] = $this->s3service->getGramified($ref);
        }

        return $grams;
    }
}
