<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue\Cron;

use Phly\RedisTaskQueue\Cron\Cronjob;
use Phly\RedisTaskQueue\Cron\Crontab;
use Phly\RedisTaskQueue\Cron\Dispatcher;
use Phly\RedisTaskQueue\Mapper\Mapper;
use Phly\RedisTaskQueue\RedisTaskQueue;
use PhlyTest\RedisTaskQueue\TestAsset\Task;
use PhlyTest\RedisTaskQueue\TestAsset\TaskMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Symfony\Component\Console\Output\OutputInterface;

use function Phly\RedisTaskQueue\jsonEncode;

class DispatcherTest extends TestCase
{
    /** @return MockObject&OutputInterface */
    private function getOutputMock()
    {
        /** @var MockObject&OutputInterface $output */
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->exactly(3))
            ->method('writeln')
            ->withConsecutive(
                ['<info>Phly\RedisTaskQueue\Cron\Dispatcher invoked</info>'],
                [$this->stringContains('<info>Evaluating')],
                [$this->stringContains('<info>- Due! dispatching')],
            );

        return $output;
    }

    /** @return MockObject&Client */
    private function getRedisMock(string $taskJson)
    {
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lpush'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lpush')
            ->with('pending', [$taskJson]);

        return $redis;
    }

    public function testDispatcherQueuesJobsWhenDue(): void
    {
        $mapper     = new Mapper();
        $mapper->attach(new TaskMapper());
        $task       = new Task('Task message');
        $taskJson   = jsonEncode($mapper->extract($task));
        $cronjob    = new Cronjob('* * * * *', $taskJson);
        $crontab    = new Crontab();
        $crontab->append($cronjob);
        $output     = $this->getOutputMock();
        $redis      = $this->getRedisMock($taskJson);
        $queue      = new RedisTaskQueue($redis, $mapper);
        $dispatcher = new Dispatcher($queue, $crontab, $mapper);

        $this->assertNull($dispatcher($output));
    }
}
