<?php

namespace Asciigram;

class SNSService
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \AmazonSNS
     */
    protected $amazonSNS;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return \AmazonSNS
     */
    protected function getSNS()
    {
        if ($this->amazonSNS) {
            return $this->amazonSNS;
        }

        return new \AmazonSNS($this->config);
    }

    /**
     * @param ImageUpload $imageupload
     * @param string $s3Name
     */
    public function sendNotification(ImageUpload $imageupload, $s3Name)
    {
        $sns = $this->getSNS();
        $response = $sns->create_topic('asciigram-image-uploaded');
        $arn = (string) $response->body->CreateTopicResult->TopicArn;

        $this->initSNSSubscriptions($arn);

        $response = $sns->publish(
            $arn, $imageupload->getMessage(),
            array('Subject' => $s3Name)
        );
    }

    protected function initSNSSubscriptions($arn)
    {
        $sns = $this->getSNS();
        $response = $sns->list_subscriptions_by_topic($arn);
        $subs = $response->body->ListSubscriptionsByTopicResult->Subscriptions->to_array();

        if (count($subs) == 0) {
            $response = $sns->subscribe(
                $arn, 'http', 'http://ascii-dev-vfnuwuvfjh.elasticbeanstalk.com/process'
            );
        }
    }

    public function confirmSubscription(array $message)
    {
        $sns = $this->getSNS();
        $response = $sns->confirm_subscription($message['TopicArn'], $message['Token']);
    }
}
