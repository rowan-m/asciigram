<?php

namespace Asciigram;

class DynamoDbService
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \AmazonDynamoDB
     */
    protected $amazonDynamoDB;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return \AmazonDynamoDD
     */
    protected function getDynamoDb()
    {
        if ($this->amazonDynamoDB) {
            return $this->amazonDynamoDB;
        }

        return new \AmazonDynamoDB($this->config);
    }

    public function persist($imageId, $gramifiedImage, $message)
    {
        $db = $this->getDynamoDb();

        // initialise table
        $tableName = 'asciigram';
        $this->initDynamoDbTable($tableName);

        $response = $db->put_item(
            array(
                'TableName' => $tableName,
                'Item' => $db->attributes(
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

    protected function initDynamoDbTable($tableName)
    {
        $db = $this->getDynamoDb();
        $response = $db->list_tables();

        if (!in_array($tableName, $response->body->TableNames->to_array()->getArrayCopy())) {
            $response = $db->create_table(array(
                'TableName' => $tableName,
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
                $response = $db->describe_table(array('TableName' => $tableName));
                $status = (string) $response->body->Table->TableStatus;

                while ($status !== 'ACTIVE') {
                    sleep(1);
                    $response = $db->describe_table(array('TableName' => $tableName));
                    $status = (string) $response->body->Table->TableStatus;
                }
            }
        }
    }

    public function getLatestGrams()
    {
        $db = $this->getDynamoDb();
        $tableName = 'asciigram';

        $query = array(
            'TableName' => $tableName,
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

        $response = $db->query($query);
        $body = $response->body->to_array()->getArrayCopy();

        if ($body['Count'] == 1) {
            return array($body['Items']);
        }

        return $body['Items'];
    }
}
