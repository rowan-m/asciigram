<?php

namespace Asciigram;
class ImageLister
{
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function fetchLatestGrams()
    {
        $db = new DynamoDBService($this->config);
        $raw = $db->getLatestGrams();

        $s3 = new S3Service($this->config);

        $grams = array();

        foreach ($raw as $protogram) {
            $ref = current($protogram['gramified']);
            $grams[$ref]['uploadDate'] = current($protogram['uploadDate']);
            $grams[$ref]['message'] = current($protogram['message']);
            $grams[$ref]['gramified'] = $s3->getGramified($ref);
        }

        return $grams;
    }
}
