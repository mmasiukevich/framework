<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Storage\SQL;

use function Amp\Promise\wait;
use Desperado\ServiceBus\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter;
use Desperado\ServiceBus\Storage\StorageAdapter;
use Desperado\ServiceBus\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Storage\StorageConfiguration;

/**
 *
 */
final class DoctrineDBALAdapterTest extends BaseStorageAdapterTest
{
    /**
     * @var DoctrineDBALAdapter
     */
    private static $adapter;

    /**
     * @inheritdoc
     */
    protected static function getAdapter(): StorageAdapter
    {
        if(null === self::$adapter)
        {
            self::$adapter = StorageAdapterFactory::inMemory();
        }

        return self::$adapter;
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function failedConnection(): void
    {
        $adapter = new DoctrineDBALAdapter(
            StorageConfiguration::fromDSN('pgsql://localhost:4486/foo?charset=UTF-8')
        );

        wait($adapter->execute('SELECT now()'));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function failedConnectionString(): void
    {
        $adapter = new DoctrineDBALAdapter(
            StorageConfiguration::fromDSN('')
        );

        wait($adapter->execute('SELECT now()'));
    }
}
