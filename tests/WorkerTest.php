<?php


namespace Dilab\Queueable;


use Dilab\Queueable\Contract\JobContract;
use Dilab\Queueable\Driver\InMemoryDriver;
use Dilab\Queueable\Job\Job;
use Dilab\Queueable\Job\Payload;
use PHPUnit\Framework\TestCase;

class WorkerOnce extends Worker
{
    public function work($maxTries = 5, $sleepSecs = 5)
    {
        return $this->heartbeat($maxTries, $sleepSecs);
    }
}

class WorkerTest extends TestCase
{
    /**
     * @var WorkerOnce
     */
    public $worker;

    /**
     * @var WorkerTestJob
     */
    public $job;

    /**
     * @var WorkerTestJobException
     */
    public $jobWithException;

    /**
     * @var Queue
     */
    public $queue;

    public function setUp()
    {
        parent::setUp();

        $queueName = 'test';

        $inMemoryDriver = new InMemoryDriver();

        $this->queue = new Queue($queueName, $inMemoryDriver);

        $this->worker = new WorkerOnce($this->queue);

        $this->job = $this->getMockBuilder(WorkerTestJob::class)->setMethods(['handle'])->getMock();

        $this->jobWithException = new WorkerTestJobException();
    }

    public function testWorkOnce_no_job()
    {
        $this->assertSame(0, $this->worker->work(1, 0.1));
    }

    public function testWorkOnce_max_tries_reached()
    {
        $this->queue->push($this->job, new Payload());
        $this->assertSame(1, $this->worker->work(-1, 0.1));
    }

    public function testWorkOnce_fire_job()
    {
        $this->queue->push($this->job, new Payload());
        $this->assertSame(2, $this->worker->work(1, 0.1));
    }

    public function testWorkOnce_throw_exception()
    {
        $this->job->expects($this->exactly(1))->method('handle')->will($this->throwException(new \Exception()));
        $this->queue->push($this->job, new Payload());
        $this->assertSame(3, $this->worker->work(1, 0.1));
    }

    public function testWorkerOnce_callback_beforeCompleteJob()
    {
        $this->queue->push($this->job, new Payload());
        $called = false;
        $this->worker->attach('beforeCompleteJob', function () use (&$called) {
            $called = true;
        });
        $this->worker->work(1, 0.1);
        $this->assertTrue($called);
    }

    public function testWorkerOnce_callback_afterCompleteJob()
    {
        $this->queue->push($this->job, new Payload());
        $called = false;
        $this->worker->attach('afterCompleteJob', function () use (&$called) {
            $called = true;
        });
        $this->worker->work(1, 0.1);
        $this->assertTrue($called);
    }

    public function testWorkerOnce_callback_heartbeat()
    {
        $this->queue->push($this->job, new Payload());
        $called = false;
        $this->worker->attach('heartbeat', function () use (&$called) {
            $called = true;
        });
        $this->worker->work(1, 0.1);
        $this->assertTrue($called);
    }

    public function testWorkerOnce_callback_onError()
    {
        $this->queue->push($this->jobWithException, new Payload());
        $expectedJob = null;
        $expectedExceptionStr = null;
        $message = null;
        $called = function (Job $job, $message, $exceptionStr) {
            $this->assertInstanceOf(WorkerTestJobException::class, $job->userJobInstance());
            $this->assertNotNull($message);
            $this->assertNotNull($exceptionStr);
        };
        $this->worker->attach('onError', $called);
        $this->worker->work(1, 0.1);
    }

}

class WorkerTestJob implements JobContract
{
    public function handle(Payload $payload)
    {
        return;
    }
}

class WorkerTestJobException implements JobContract
{
    public function handle(Payload $payload)
    {
        throw new \Exception('I failed');
    }
}

