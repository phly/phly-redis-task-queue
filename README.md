# phly/redis-task-queue

Implements both a task queue and crontab runner utilizing Redis, a [PSR-14 EventDispatcher](https://www.php-fig.org/psr/psr-14/), ReactPHP's event loop, and symfony/console (via [laminas-cli](https://docs.laminas.dev/laminas-cli/).

## Installation

```bash
composer require phly/phly-redis-task-queue
```

I recommend having ext-event installed for best results.

Additionally, the component marks itself as a [Laminas component](https://docs.laminas.dev/laminas-component-installer), and usage in a Laminas or Mezzio application will automatically setup wiring for usage.

## Usage

### Creating tasks

Tasks are objects of any class.

As an example:

```php
namespace Foo;

class HelloWorldTask
{
    public function __construct(
        public readonly string $message
    ) {
    }
}
```

### Serializing tasks

In order to queue a task and later dispatch it, you need to be able to serialize and deserialize it.
To do this, you will need to create a _mapper_, which is a class implementing `Phly\RedisTaskQueue\Mapper\MapperInterface`:

```php
interface MapperInterface
{
    /**
     * Can this implementation hydrate the given array type?
     *
     * @psalm-param array{__type: string, ...} $serialized
     */
    public function handlesArray(array $serialized): bool;

    /**
     * Can this implementation extract the given object type?
     */
    public function handlesObject(object $object): bool;

    /** @return array{__type: string, ...} */
    public function extract(object $object): array;

    /** @psalm-param array{__type: string, ...} $serialized */
    public function hydrate(array $serialized): object;
}
```

When serializing to an array, the returned array MUST contain the member `__type`, with a string value resolving to the task class type.
Otherwise, the remainder of the serialization format is up to you.
You can assume that when `hydrate` is called, the array contains a `__type` member your mapper can handle.

These mapper classes can be registered with your DI container.
Once you have, you can register them with your configuration so that they will be automatically added to the internal mapper:

```php
return [
    'redis-task-queue' => [
        'mappers' => [
            \App\Mapper\TaskMapper::class,
        ],
    ],
];
```

#### EmptyObjectMapper

This component provides a versatile mapper implementation for empty task implementations, `Phly\RedisTaskQueue\Mapper\EmptyObjectMapper`.
This class takes a single argument, the name of a task class to respond to.
When extracting, it will extract an array with exactly one member, `__type`, with the class value.
When hydrating, it will instantiate the given class with no arguments and return it.

Because this implementation has an argument, you have two possibilities for registering it with the mapper.

First, you could create a custom factory.
As an example, if the empty class were named `RssFeed`, you could create an `RssFeedMapperFactory`:

```php
class RssFeedMapperFactory
{
    public function __invoke(): EmptyObjectMapper
    {
        return new EmptyObjectMapper(RssFeed::class);
    }
}
```

and then map a service to it:

```php
return [
    'dependencies' => [
        'factories' => 
            'rss-feed-mapper' => RssFeedMapperFactory::class,
        ],
    ],
    'redis-task-queue' => [
        'mappers' => [
            'rss-feed-mapper',
        ],
    ],
];
```

Second, you could use a delegator factory on the `Phly\RedisTaskQueue\Mapper\Mapper` class to attach it:

```php
class RssFeedMapperDelegator
{
    public function __invoke(ContainerInterface $container, string $requestedName, callable $factory): Mapper
    {
        $mapper = $factory();
        $mapper->attach(new EmptyObjectMapper(RssFeed::class));
        return $mapper;
    }
}
```

You would then just register the delegator factory:

```php
return [
    'dependencies' => [
        'delegators' => [
            \Phly\RedisTaskQueue\Mapper\Mapper::class => [
                RssFeedMapperDelegator::class,
            ],
        ],
    ],
];
```

### Enqueuing tasks

I recommend decoupling your application from the `RedisTaskQueue`, and instead use a PSR-14 dispatcher to dispatch an event wrapping the task.
This approach means that in development, you can have an alternate handler for deferred events that, for instance, logs the task, versus actually enqueueing it.
Additionally, by wrapping the task in a `DeferredEvent`, you will be signaling in your code that you expect this to happen asynchronously, versus immediately.
If you later decide to handle such tasks immediately, you can use a different listener for `DeferredEvent`s, or you can unwrap specific tasks from `DeferredEvent`.

To enqueue a task, dispatch it by wrapping it in a `Phly\RedisTaskQueue\EventDispatcher\DeferredEvent`:

```php
$dispatcher->dispatch(new DeferredEvent($task));
```

A listener for this event is provided in this component: `Phly\RedisTaskQueue\EventDispatcher\DeferredEventListener`.
You will need to wire this to your PSR-14 dispatcher.

> #### Decoupling your application from this library for purposes of deferment
>
> If you want to "own" the application code that would defer tasks, and not have it depend on this component, you can do so by defining your own `DeferredEvent` or `AsyncEvent` type, and then creating your own PSR-14 listener for that type.
> The implementation would look like the [DeferredEventListener](./src/EventDispatcher/DeferredEventListener.php) in this library.

### Processing tasks

You will need to register one or more listeners for each task type you will queue with the event dispatcher.
As an example, building on the above, you might have the following listener:

```php
namespace Foo;

class HelloWorldListener
{
    public function __invoke(HelloWorldTask $task): void
    {
        error_log(sprintf('Hello, %s', $task->message));
    }
}
```

You would then register this via a PSR-14 listener provider.

### Running the task runner

```bash
./vendor/bin/laminas phly:redis-task-queue:task-worker
```

If you want a pool of task workers, I recommend [supervisord](https://supervisord.org).
Configuration for a pool with five workers might look like this:

```dosini
[program:worker]
autostart=true
autorestart=unexpected
command=vendor/bin/laminas phly:redis-task-queue:task-worker
; Change the following to your application root:
directory=/var/www
numprocs=5
process_name=%(program_name)s_%(process_num)d
redirect_stderr=true
```

## Crontabs

The crontab implementation in this library is via the `phly:redis-task-queue:cron-runner` laminas-cli command.
It pulls crontab definitions from your application configuration, and then once a minute checks to see if any tasks are due.
If so, it enqueues the related task.

### Configuration

Configuration is via the "cron.jobs" configuration key.
Each element is an array with two keys:

- **schedule**: the crontab schedule to use; see the [dragonmantank/cron-expression write-up](https://github.com/dragonmantank/cron-expression#cron-expressions) for a good overview.
- **task**: a JSON string representing a task to run.
  This string MUST represent a JSON object that can be mapped to a PHP class using the mapper.
  Due to how JSON parsing works, you will need to ensure you escape namespace separators properly; this is usually a sequence of four backslashes: `App\\\\Tasks\\\\FetchRssFeed`.

As an example:

```php
return [
    'cron' => [
        // Keys are not required for jobs, but are helpful when debugging configuration
        'rss' => [
            // Fetch every 3 hours at the top of the hour
            'schedule' => '0 */3 * * *',
            'task'     => '{"__type": "App\\\\Tasks\\\\FetchRssFeed", "url": "https://github.com/weierophinney", "headers": {"Accept": "application/atom+xml"}}',
        ],
        'social' => [
            // Fetch every 15 minutes
            'schedule' => '*/15 * * * *',
            'task' => '{"__type": "App\\\\Tasks\\\\FetchSocial"}',
        ],
    ],
];
```

### Invoking the cron-runner

To invoke the cron-runner, use the following:

```bash
./vendor/bin/laminas phly:redis-task-queue:cron-runner
```

I recommend running this with [supervisord](https://supervisord.org).
When you do, use **ONLY ONE** worker, to ensure that only one task is queued when it comes due.
Configuration would look like the following:

```dosini
[program:cron]
autostart=true
autorestart=unexpected
command=vendor/bin/laminas phly:redis-task-queue:cron-runner
; Change the following to your application root:
directory=/var/www
numprocs=1
redirect_stderr=true
```
