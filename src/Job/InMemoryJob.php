<?php


namespace Dilab\Queueable\Job;


use Dilab\Queueable\Contract\JobContract;

class InMemoryJob extends Job
{

    public function acknowledge()
    {
        $this->queue->getDriver()->delete(
            $this->queue->getQueueName(),
            $this->message
        );
    }

    public function release()
    {
        return;
    }

    public function userJobInstance()
    {
        return $this->message['userJobInstance'];
    }

    public function payload()
    {
        return $this->message['payload'];
    }


}