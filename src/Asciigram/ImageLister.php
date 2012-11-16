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
    protected $dynamoDBService;

    public function __construct(S3Service $s3service, DynamoDBService $dynamoDBService)
    {
        $this->s3service = $s3service;
        $this->dynamoDBService = $dynamoDBService;
    }

    public function fetchLatestGrams()
    {
        $raw = $this->dynamoDBService ->getLatestGrams();

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
