<?php
// API Design

use Dilab\Queueable\Driver\InMemoryDriver;
use Dilab\Queueable\Worker;

$sendEmailJob = (new SendEmailJob())->toQueue('email');

// Enqueue
$queue->push($sendEmailJob, $payLoad);

// Specs
// max tries before releasing job back to queue
// manual release a job
// view queue jobs

// Usage

// Start worker
$queue = new Queue($queueName, new InMemoryDriver());

$logger = new Monolog($logger);

Worker::run($queue, $maxTries = 5, $logger);

// View jobs inside a queue
Worker::view($queue);






// Notes

// Worker deals with Job & Queue objects
// Driver deals with Messages(JSON)
// Queue translates Driver Message to Job Objects
// Job deals with Driver Messages


