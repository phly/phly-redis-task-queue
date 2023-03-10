<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Cron;

use Cron\CronExpression;
use DateTimeImmutable;
use Phly\RedisTaskQueue\Mapper\Mapper;
use Phly\RedisTaskQueue\RedisTaskQueue;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

final class Dispatcher
{
    public function __construct(
        private RedisTaskQueue $queue,
        private Crontab $crontab,
        private Mapper $mapper,
    ) {
    }

    public function __invoke(OutputInterface $output): void
    {
        $output->writeln(sprintf('<info>%s invoked</info>', self::class));
        $now = new DateTimeImmutable();
        foreach ($this->crontab as $job) {
            $output->writeln(sprintf('<info>Evaluating "%s %s"</info>', $job->schedule, $job->task));
            $cron = new CronExpression($job->schedule);
            if (! $cron->isDue($now)) {
                $output->writeln('<info>- Not due; skipping</info>');
                continue;
            }

            $output->writeln(sprintf('<info>- Due! dispatching %s</info>', $job->task));

            if (! $this->mapper->canCastToObject($job->task)) {
                $output->writeln('<error>- Unable to cast task to object; malformed, or missing mapper</error>');
                continue;
            }

            $task = $this->mapper->toObject($job->task);
            $this->queue->queue($task);
        }
    }
}
