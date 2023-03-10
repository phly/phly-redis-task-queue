<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Cron;

use Cron\CronExpression;
use JsonException;
use Phly\RedisTaskQueue\Mapper\Mapper;
use Psr\Log\LoggerInterface;

use function array_key_exists;
use function is_array;
use function is_string;
use function Phly\RedisTaskQueue\jsonDecode;

/**
 * @internal
 */
class ConfigParser
{
    public function __construct(
        private Mapper $mapper,
    ) {
    }

    public function __invoke(array $jobs, ?LoggerInterface $logger = null): Crontab
    {
        $crontab = new Crontab();

        foreach ($jobs as $index => $jobDetails) {
            $cronjob = $this->validateAndExtractCronjob($jobDetails, $index, $logger);

            if (null === $cronjob) {
                continue;
            }

            $crontab->append($cronjob);
        }

        return $crontab;
    }

    private function validateAndExtractCronjob(mixed $jobDetails, int|string $index, ?LoggerInterface $logger): ?Cronjob
    {
        if (! is_array($jobDetails)) {
            $this->logWarning(
                $logger,
                'Job at index {index} is invalid; it must be an array with the keys "schedule" and "event"',
                ['index' => $index],
            );
            return null;
        }

        if (! array_key_exists('schedule', $jobDetails)) {
            $this->logWarning(
                $logger,
                'Job at index {index} is invalid; missing "schedule" key',
                ['index' => $index],
            );
            return null;
        }

        if (! $this->isScheduleValid($jobDetails['schedule'], $index, $logger)) {
            return null;
        }

        if (! array_key_exists('task', $jobDetails)) {
            $this->logWarning(
                $logger,
                'Job at index {index} is invalid; missing "task" key',
                ['index' => $index],
            );
            return null;
        }

        if (! $this->isTaskValid($jobDetails['task'], $index, $logger)) {
            return null;
        }

        return new Cronjob(
            schedule: $jobDetails['schedule'],
            task: $jobDetails['task'],
        );
    }

    private function isScheduleValid(mixed $schedule, int|string $index, ?LoggerInterface $logger): bool
    {
        if (! is_string($schedule)) {
            $this->logWarning(
                $logger,
                'Job at index {index} is invalid; "schedule" value is not a string',
                ['index' => $index],
            );
            return false;
        }

        if (! CronExpression::isValidExpression($schedule)) {
            $this->logWarning(
                $logger,
                'Job at index {index} is invalid; schedule "{schedule}" is invalid',
                [
                    'index'    => $index,
                    'schedule' => $schedule,
                ],
            );
            return false;
        }

        return true;
    }

    private function isTaskValid(mixed $task, int|string $index, ?LoggerInterface $logger): bool
    {
        if (! is_string($task)) {
            $this->logWarning(
                $logger,
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Job at index {index} is invalid; non-string "task" key provided; must be valid task JSON',
                ['index' => $index],
            );
            return false;
        }

        try {
            $serialized = jsonDecode($task);
        } catch (JsonException $e) {
            $this->logWarning(
                $logger,
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Job at index {index} is invalid; "task" value ("{task}") must be valid task JSON: {message}',
                [
                    'index'   => $index,
                    'task'    => $task,
                    'message' => $e->getMessage(),
                ],
            );
            return false;
        }

        if (! $this->mapper->canHydrate($serialized)) {
            $this->logWarning(
                $logger,
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Job at index {index} is invalid; "task" value ("{task}") must be valid task JSON: missing __type or no mapper exists',
                [
                    'index' => $index,
                    'task'  => $task,
                ],
            );
            return false;
        }

        return true;
    }

    private function logWarning(?LoggerInterface $logger, string $message, array $context): void
    {
        $message = '[CRON][PARSER] ' . $message;
        $logger?->warning($message, $context);
    }
}
