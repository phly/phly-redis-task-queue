# phly/redis-task-queue

Implements a task queue utilizing Redis, and using ReactPHP's event loop and symfony/console to implement a task runner.

## Installation

```bash
composer require phly/phly-redis-task-queue
```

I recommend having ext-event installed for best results.

## Usage

### Creating tasks

Tasks must implement `Phly\RedisTaskQueue\TaskInterface`, which itself extends `JsonSerializable`, and defines the following method:

```php
public static function createFromStdClass(object $object): self;
```

The `jsonSerialize()` method MUST return an associative array with the property `__type` resolving to the task class itself.


As an example:

```php
namespace Foo;

use Phly\RedisTaskQueue\TaskInterface;

class HelloWorldTask implements TaskInterface
{
    public static function createFromStdClass(object $object): self
    {
        $args = (array) $object;
        unset($args['__type']);

        return new self(...$args);
    }

    public function __construct(
        public readonly string $message
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            '__type'  => __CLASS__,
            'message' => $this->message,
        ];
    }
}
```

### Enqueuing tasks

I recommend decoupling your application from the `RedisTaskQueue`, and instead use a PSR-14 dispatcher to dispatch an event wrapping the task.
This approach means that in development, you can have an alternate handler for deferred events that, for instance, logs the task, versus actually enqueueing it.
Additionally, by wrapping the task in a `DeferredEvent`, you will be signaling in your code that you expect this to happen asynchronously, versus immediately.
If you later decide to handle such tasks immediately, you can use a different listener for `DeferredEvent`s.

To enqueue a task, dispatch it by wrapping it in a `Phly\RedisTaskQueue\EventDispatcher\DeferredEvent`:

```php
$dispatcher->dispatch(new DeferredEvent($task));
```

A listener for this event is provided in this component: `Phly\RedisTaskQueue\EventDispatcher\DeferredEventListener`.
You will need to wire this to your PSR-14 dispatcher.

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
./vendor/bin/laminas phly:redis-task-queue:start
```
