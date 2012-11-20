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
        if( ! $raw = $this->dynamoDBService->getLatestGrams())
        {
            return false;
        }

        $grams = array();

        foreach ($raw as $protogram) {
            $ref = current($protogram['gramified']);
            $grams[$ref]['uploadDate'] = current($protogram['uploadDate']);
            $grams[$ref]['message'] = current($protogram['message']);
            $grams[$ref]['id'] = $ref;
            $grams[$ref]['gramified'] = $this->s3service->getGramified($ref);
        }

        return $grams;
    }

    public function fetchGram($gramified)
    {
        if( ! $raw = $this->dynamoDBService->getGram($gramified))
        {
            return false;
        }

        $gram = $raw[0];

        $ref = current($gram['gramified']);
        $gram['uploadDate'] = current($gram['uploadDate']);
        $gram['message'] = current($gram['message']);
        $gram['id'] = $ref;
        $gram['gramified'] = $this->s3service->getGramified($ref);
        

        return $gram;
    }
}
