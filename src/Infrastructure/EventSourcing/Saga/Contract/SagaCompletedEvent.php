<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Saga\Contract;

use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;

/**
 * Saga marked as completed
 */
class SagaCompletedEvent implements EventInterface
{
    /**
     * Closed at datetime
     *
     * @var string
     */
    public $closedAt;
}
