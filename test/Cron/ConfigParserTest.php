<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue\Cron;

use Phly\RedisTaskQueue\Cron\ConfigParser;
use Phly\RedisTaskQueue\Cron\Cronjob;
use Phly\RedisTaskQueue\Cron\Crontab;
use Phly\RedisTaskQueue\Mapper\Mapper;
use PhlyTest\RedisTaskQueue\TestAsset\Task;
use PhlyTest\RedisTaskQueue\TestAsset\TaskMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ConfigParserTest extends TestCase
{
    /** @var MockObject&LoggerInterface */
    private $logger;

    private ConfigParser $parser;
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mapper = new Mapper();
        $this->mapper->attach(new TaskMapper());
        $this->parser = new ConfigParser($this->mapper);
    }

    /** @psalm-return iterable<string, array{0: array<mixed>}> */
    public static function provideInvalidJobTypes(): iterable
    {
        yield 'null'   => [[null]];
        yield 'int'    => [[1]];
        yield 'float'  => [[1.1]];
        yield 'string' => [['string']];
        yield 'object' => [[(object) ['message' => 'string']]];
    }

    /** @dataProvider provideInvalidJobTypes */
    public function testIgnoresJobWhenNotAnArray(array $jobs): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('must be an array with the keys "schedule" and "event"'));
        $crontab = ($this->parser)($jobs, $this->logger);

        $this->assertInstanceOf(Crontab::class, $crontab);
        $this->assertSame(0, $crontab->count());
    }

    public function testIgnoresJobWhenScheduleMissing(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('missing "schedule" key'));
        $crontab = ($this->parser)([
            ['no' => 'schedule'],
        ], $this->logger);

        $this->assertInstanceOf(Crontab::class, $crontab);
        $this->assertSame(0, $crontab->count());
    }

    public function testIgnoresJobWhenScheduleIsNotAString(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('"schedule" value is not a string'));
        $crontab = ($this->parser)([
            ['schedule' => 1],
        ], $this->logger);

        $this->assertInstanceOf(Crontab::class, $crontab);
        $this->assertSame(0, $crontab->count());
    }

    public function testIgnoresJobWhenScheduleIsInvalid(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->matchesRegularExpression('/schedule "[^"]+" is invalid/'));
        $crontab = ($this->parser)([
            ['schedule' => 'invalid schedule'],
        ], $this->logger);

        $this->assertInstanceOf(Crontab::class, $crontab);
        $this->assertSame(0, $crontab->count());
    }

    public function testIgnoresJobWhenTaskMissing(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('missing "task" key'));
        $crontab = ($this->parser)([
            ['schedule' => '0 * * * *'],
        ], $this->logger);

        $this->assertInstanceOf(Crontab::class, $crontab);
        $this->assertSame(0, $crontab->count());
    }

    public function testIgnoresJobWhenTaskIsNotAString(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('non-string "task" key'));
        $crontab = ($this->parser)([
            [
                'schedule' => '0 * * * *',
                'task'     => 1,
            ],
        ], $this->logger);

        $this->assertInstanceOf(Crontab::class, $crontab);
        $this->assertSame(0, $crontab->count());
    }

    public function testIgnoresJobWhenTaskIsInvalid(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->matchesRegularExpression('/"task" value \([^)]+\) must be valid task JSON/'));
        $crontab = ($this->parser)([
            [
                'schedule' => '0 * * * *',
                'task'     => 'invalid-task',
            ],
        ], $this->logger);

        $this->assertInstanceOf(Crontab::class, $crontab);
        $this->assertSame(0, $crontab->count());
    }

    public function testGeneratedCrontabContainsValidJobs(): void
    {
        $task = new Task('Task message');
        $job  = [
            'schedule' => '0 * * * *',
            'task'     => json_encode($task, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];

        $this->logger
            ->expects($this->never())
            ->method('warning');

        $crontab = ($this->parser)([$job], $this->logger);

        $this->assertInstanceOf(Crontab::class, $crontab);
        $this->assertSame(1, $crontab->count());

        foreach ($crontab as $cronjob) {
            break;
        }

        $this->assertInstanceOf(Cronjob::class, $cronjob);
        $this->assertSame($job['schedule'], $cronjob->schedule);
        $this->assertSame($job['task'], $cronjob->task);
    }
}
