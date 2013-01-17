<?php

namespace Asciigram;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\ResourceNotFoundException;
use Aws\DynamoDb\Enum\Type;
use Aws\DynamoDb\Enum\ComparisonOperator;

class DynamoDBService
{
    /**
     * @var DynamoDbClient
     */
    protected $dynamoDb;

    /**
     * @var string
     */
    protected $tableName;

    public function __construct(\AmazonDynamoDB $amazonDynamoDB)
    {
        $this->dynamoDb = $amazonDynamoDB;
        $this->tableName = "asciigram";
    }

    public function persist($imageId, $gramifiedImage, $message)
    {
        $this->initDynamoDbTable();

        $this->dynamoDb->putItem(array(
            'TableName' => $this->tableName,
            'Item' => $this->dynamoDb->formatAttributes(array(
                'display'    => 1,
                'uploadDate' => time(),
                'imageId'    => $imageId,
                'message'    => $message,
                'gramified'  => $gramifiedImage,
            )),
        ));
    }

    protected function initDynamoDbTable()
    {
        try {
            $this->dynamoDb->describeTable(array('TableName' => $this->tableName));
        } catch (ResourceNotFoundException $e) {
            $this->dynamoDb->createTable(array(
                'TableName' => $this->tableName,
                'KeySchema' => array(
                    'HashKeyElement' => array(
                        'AttributeName' => 'display',
                        'AttributeType' => Type::N,
                    ),
                    'RangeKeyElement' => array(
                        'AttributeName' => 'uploadDate',
                        'AttributeType' => Type::N,
                    )
                ),
                'ProvisionedThroughput' => array(
                    'ReadCapacityUnits'  => 50,
                    'WriteCapacityUnits' => 10
                )
            ));

            $this->dynamoDb->waitUntil('table_exists', $this->tableName);
        }
    }

    public function getLatestGrams()
    {
        try {
            $items = $this->dynamoDb->getIterator('Query', array(
                'TableName' => $this->tableName,
                'AttributesToGet' => array('uploadDate', 'gramified', 'message'),
                'Limit' => 20,
                'ScanIndexForward' => false,
                'HashKeyValue' => array(Type::N, '1'),
                'RangeKeyCondition' => array(
                    'ComparisonOperator' => ComparisonOperator::LE,
                    'AttributeValueList' => array(array(Type::N => (string) time())),
                )
            ));
        } catch (ResourceNotFoundException $e) {
            return false;
        }

        return $items->toArray();
    }

    public function getGram($gramified)
    {
        try {
            $items = $this->dynamoDb->getIterator('Scan', array(
                'TableName' => $this->tableName,
                'AttributesToGet' => array('uploadDate', 'gramified', 'message'),
                'ScanFilter' => array(
                    'gramified' => array(
                        'ComparisonOperator' => ComparisonOperator::EQ,
                        'AttributeValueList' => array(array(Type::S => (string) $gramified)),
                    ),
                )
            ));
        } catch (ResourceNotFoundException $e) {
            return false;
        }

        return $items->toArray();
    }
}
