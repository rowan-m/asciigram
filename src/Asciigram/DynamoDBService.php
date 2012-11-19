<?php

namespace Asciigram;

class DynamoDBService
{
    /**
     * @var \AmazonDynamoDB
     */
    protected $amazonDynamoDB;

    /**
     * @var \AmazonDynamoDB
     */
    protected $tablename;

    public function __construct(\AmazonDynamoDB $amazonDynamoDB)
    {
        $this->amazonDynamoDB = $amazonDynamoDB;
        $this->tablename = "asciigram";
    }

    public function persist($imageId, $gramifiedImage, $message)
    {
        // initialise table
        $this->initDynamoDbTable();

        $response = $this->amazonDynamoDB->put_item(
            array(
                'TableName' => $this->tablename,
                'Item' => $this->amazonDynamoDB->attributes(
                    array(
                        'display' => 1,
                        'uploadDate' => time(),
                        'imageId' => $imageId,
                        'message' => $message,
                        'gramified' => $gramifiedImage,
                    )
                ),
            )
        );
    }

    protected function initDynamoDbTable()
    {
        $response = $this->amazonDynamoDB->list_tables();

        if (!in_array($this->tablename, $response->body->TableNames->to_array()->getArrayCopy())) {
            $response = $this->amazonDynamoDB->create_table(array(
                'TableName' => $this->tablename,
                'KeySchema' => array(
                    'HashKeyElement' => array(
                        'AttributeName' => 'display',
                        'AttributeType' => \AmazonDynamoDB::TYPE_NUMBER,
                    ),
                    'RangeKeyElement' => array(
                        'AttributeName' => 'uploadDate',
                        'AttributeType' => \AmazonDynamoDB::TYPE_NUMBER,
                    )
                ),
                'ProvisionedThroughput' => array(
                    'ReadCapacityUnits' => 50,
                    'WriteCapacityUnits' => 10
                )
                ));

            if ($response->isOk()) {
                $response = $this->amazonDynamoDB->describe_table(array('TableName' => $this->tablename));
                $status = (string) $response->body->Table->TableStatus;

                while ($status !== 'ACTIVE') {
                    sleep(1);
                    $response = $this->amazonDynamoDB->describe_table(array('TableName' => $this->tablename));
                    $status = (string) $response->body->Table->TableStatus;
                }
            }
        }
    }

    public function getLatestGrams()
    {
        $query = array(
            'TableName' => $this->tablename,
            'AttributesToGet' => array('uploadDate', 'gramified', 'message'),
            'Limit' => 20,
            'ScanIndexForward' => false,
            'HashKeyValue' => array(
                \AmazonDynamoDB::TYPE_NUMBER => strval(1),
            ),
            'RangeKeyCondition' => array(
                'ComparisonOperator' => \AmazonDynamoDB::CONDITION_LESS_THAN_OR_EQUAL,
                'AttributeValueList' => array(
                    array(\AmazonDynamoDB::TYPE_NUMBER => strval(time()))
                ),
            )
        );

        $response = $this->amazonDynamoDB->query($query);

        if( ! $response)
        {
            return false;
        }

        $body = $response->body->to_array()->getArrayCopy(); 

        if ($body['Count'] == 1) 
        {
            return array($body['Items']);
        }

        return $body['Items'];
    }

    public function getGram($gramified)
    {
        $query = array(
            'TableName' => $this->tablename, 
            'AttributesToGet' => array('uploadDate', 'gramified', 'message'),
            'ScanFilter' => array( 
                'gramified' => array(
                    'ComparisonOperator' => \AmazonDynamoDB::CONDITION_EQUAL,
                    'AttributeValueList' => array(
                        array( \AmazonDynamoDB::TYPE_STRING => strval($gramified) )
                    ),
                ),
            )
        );

        $response = $this->amazonDynamoDB->scan($query);

        if( ! $response)
        {
            return false;
        }

        $body = $response->body->to_array()->getArrayCopy();

        if ($body['Count'] == 0)
        {
            return false;
        }

        return array($body['Items']);
    }
}
