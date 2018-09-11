* [Список параметров окружения]()
* [Инциализация]()
* [Конфигурация транспорта]()
* [Создание схемы базы данных]()
* [Пример инициализации]()

#### Список параметров окружения:
- ```APP_ENVIRONMENT```: Окружение (*test*, *dev*, *prod*). На текущий момент разница только на уровне компиляции контейнера (сохранять его, или пересоздавать каждый раз (*test*, *dev*))
- ```APP_ENTRY_POINT_NAME```: название точки входа (произвольное название вашего демона)
- ```TRANSPORT_CONNECTION_DSN```: DSN подключения к транспорту. Формат вида ```amqp://user:password@host:port```
- ```DATABASE_CONNECTION_DSN```: DSN подключения к базе данных. Формат вида ```sqlite:///:memory:``` для тестов и ```pgsql://user:password@host:port/database``` для реального использования
- ```LOG_LEVEL```: Уровень логгирования сообщений для логгера по умолчанию (stdOut)
- ```AMP_LOG_COLOR```: (1/0) Нужно ли подсвечивать уровни сообщения разными цветами (в зависимости от уровня применяется свой цвет)
- ```LOG_MESSAGE_PAYLOAD```: Необходимо ли логировать сообщение целиклм, включая заголовки. Если выключено, то данные, содержащиеся в сообщении залогированы не будут. Только факт того, что сообщение было получено\отправлено
- ```TRANSPORT_TOPIC```: Точка входа брокера сообщений. В контексте RabbitMQ - название exchange
- ```TRANSPORT_QUEUE```: Очередь, которую будет слушать демон
- ```TRANSPORT_ROUTING_KEY```: ключ роутинга для сообщений (topic -> queue)
- ```SENDER_DESTINATION_TOPIC```: Точка входа, в которую будут отправляться сообщения
- ```SENDER_DESTINATION_TOPIC_ROUTING_KEY```: Ключ роутинга для отправляемых сообщений

При старте демона будут созданы все указанные exchangeы, queueы и проставлены routing keys.

#### Инциализация
За первоначальную инциализацию приложения отвечает объект класса [Bootstrap](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php). На выбор доступны 2 варианта создания объекта: метод [withDotEnv](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L49) загрузит параметры окружения из указанного файла; [withEnvironmentValues](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L99) предполагает, что параметры окружения уже загружены кем-то
Помимо всего прочего, доступны следующие методы:
- [enableAutoImportSagas](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L67): Сканирует файлы проекта и автоматически регистрирует все найденные саги
- [enableAutoImportMessageHandlers](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L85): Сканирует файлы проекта и автоматически регистрирует все найденные обработчики сообщений
- [useAmqpExtTransport](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L121): Конфигурирует RabbitMQ транспорт
- [useSqlStorage](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L141): Конфигурация SQL базы данных
- [useCustomCacheDirectory](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L159): Если не указан, то будет использования к директории по умолчанию (sys_get_temp_dir)
- [importParameters](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L173): Импортирует параметры в DI-контейнера
- [addExtensions](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L185): Регистрирует пользовательский [Extension](https://symfony.com/doc/current/bundles/extension.html) в DI-контейнере
- [addCompilerPasses](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L199): Регистрирует пользовательский [CompilerPass](https://symfony.com/doc/current/service_container/compiler_passes.html)
- [boot](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/Bootstrap.php#L107): Компилирует DI-контейнер

#### Конфигурация транспорта
За конфигурацию транспортного уровня отвечает [ServiceBusKernel](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/ServiceBusKernel.php), в котором доступен [TransportConfigurator](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php)
- [addDefaultDestinations](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L61): Регистрация маршрута доставки сообщений по умолчанию
- [registerCustomMessageDestinations](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L80): Регистрирует специфичный маршрут доставки для сообщения
- [addQueue](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L99): Создаёт новую очередь (если не существует)
- [createTopic](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L120): Создаёт exchange (если не существует)
- [bindQueue](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L155): Привязывает очередь к exchange
- [bindTopic](https://github.com/mmasiukevich/service-bus/blob/master/src/Application/TransportConfigurator.php#L140): Привязывает exchange к exchange

#### Создание схемы базы данных
**Важно**: при старте приложения не создаётся схема базы данных. Это отдано на откуп пользователям.
Доступные для SQL фикстуры:
- [extensions.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/extensions.sql)
- [event_store_stream.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream.sql)
- [event_store_stream_events.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream_events.sql)
- [event_store_snapshots.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/event_store_snapshots.sql)
- [indexes.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/EventSourcing/EventStreamStore/Sql/schema/indexes.sql)
- [sagas_store.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/SagaStore/Sql/schema/sagas_store.sql)
- [indexes.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Sagas/SagaStore/Sql/schema/indexes.sql)
- [scheduler_registry.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Scheduler/Store/Sql/schema/scheduler_registry.sql)
- [event_sourcing_indexes.sql](https://github.com/mmasiukevich/service-bus/blob/master/src/Index/Storage/Sql/schema/event_sourcing_indexes.sql)

#### Пример инициализации демона

```$php
#!/usr/bin/env php
<?php

declare(strict_types = 1);

namespace DocumentProcessing\Bin;

use Desperado\ServiceBus\Application\Bootstrap;
use Desperado\ServiceBus\Application\ServiceBusKernel;
use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\Transport\AmqpExt\AmqpQueue;
use Desperado\ServiceBus\Transport\AmqpExt\AmqpTopic;
use Desperado\ServiceBus\Transport\QueueBind;
use ServiceBusDemo\App\ServiceBusDemoExtension;
use Symfony\Component\Debug\Debug;

include __DIR__ . '/vendor/autoload.php';

try
{
    /** @noinspection ForgottenDebugOutputInspection */
    Debug::enable();

    $container = Bootstrap::withDotEnv(__DIR__ . '/.env')
        ->useAmqpExtTransport((string) \getenv('TRANSPORT_CONNECTION_DSN'))
        ->useSqlStorage(AmpPostgreSQLAdapter::class, (string) \getenv('DATABASE_CONNECTION_DSN'))
        ->useCustomCacheDirectory(__DIR__ . '/cache')
        ->addExtensions(new ServiceBusDemoExtension())
        ->importParameters([
            'app.log_level' => (string) \getenv('LOG_LEVEL')
        ])
        ->enableAutoImportMessageHandlers([__DIR__ . '/src'])
        ->enableAutoImportSagas([__DIR__ . '/src'])
        ->boot();

    $kernel = new ServiceBusKernel($container);

    $transportConfigurator = $kernel->transportConfigurator();

    /** Main exchange and queue binds */

    $mainTopic = AmqpTopic::direct((string) \getenv('TRANSPORT_TOPIC'), true);
    $mainQueue = AmqpQueue::default((string) \getenv('TRANSPORT_QUEUE'), true);

    $transportConfigurator
        ->createTopic($mainTopic)
        ->addQueue($mainQueue)
        ->bindQueue(new QueueBind($mainQueue, $mainTopic, (string) \getenv('TRANSPORT_ROUTING_KEY')));

    $transportConfigurator->addDefaultDestinations(
        new Destination(
            (string) \getenv('SENDER_DESTINATION_TOPIC'),
            (string) \getenv('SENDER_DESTINATION_TOPIC_ROUTING_KEY')
        )
    );

    $kernel->listen($mainQueue);
}
catch(\Throwable $throwable)
{
    echo $throwable->getMessage(), \PHP_EOL, $throwable->getFile() . ':' . $throwable->getLine(), \PHP_EOL;
}

```