<?php

namespace Asciigram;

class SNSService
{
    /**
     * @var \AmazonSNS
     */
    protected $amazonSNS;

    public function __construct(\AmazonSNS $amazonSNS)
    {
        $this->amazonSNS = $amazonSNS;
    }

    /**
     * @param ImageUpload $imageupload
     * @param string $s3Name
     */
    public function sendNotification(ImageUpload $imageupload, $s3Name)
    {
        $response = $this->amazonSNS->create_topic('asciigram-image-uploaded');
        $arn = (string) $response->body->CreateTopicResult->TopicArn;

        $this->initSNSSubscriptions($arn);

        $response = $this->amazonSNS->publish(
            $arn, $imageupload->getMessage(),
            array('Subject' => $s3Name)
        );
    }

    protected function initSNSSubscriptions($arn)
    {
        $response = $this->amazonSNS->list_subscriptions_by_topic($arn);
        $subs = $response->body->ListSubscriptionsByTopicResult->Subscriptions->to_array();

        if (count($subs) == 0) {
            $response = $this->amazonSNS->subscribe(
                $arn, 'http', 'http://ascii-dev-vfnuwuvfjh.elasticbeanstalk.com/process'
            );
        }
    }

    public function confirmSubscription(array $message)
    {
        $response = $this->amazonSNS->confirm_subscription($message['TopicArn'], $message['Token']);
    }
}
