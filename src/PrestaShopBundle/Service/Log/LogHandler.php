<?php

/**
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace PrestaShopBundle\Service\Log;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use PrestaShop\PrestaShop\Adapter\LegacyLogger;
use Symfony\Component\DependencyInjection\Container;

/**
 * This handler is an interface between Monolog and the legacy logger.
 *
 * It also provides a feature that allows you saving log records, it is always disabled by default, but you can temporarily
 * enable the saving of recors, which may be useful to get warning messages and then display them as flash messages in controllers.
 */
class LogHandler extends AbstractProcessingHandler
{
    protected $container;

    /**
     * @var array<int, list<array{level: int, message: string, context: array}>>
     */
    protected array $savedRecords = [];

    protected bool $recordsSaved = false;

    public function __construct(Container $container, $level = Logger::DEBUG, $bubble = true)
    {
        $this->container = $container;
        parent::__construct($level, $bubble);
    }

    /**
     * Writes the record down to the log of the implementing handler.
     */
    protected function write(LogRecord $record): void
    {
        /** @var LegacyLogger $logger */
        $logger = $this->container->get('prestashop.adapter.legacy.logger');
        $logger->log($record->level->value, $record->message, $record->context);
        if ($record->level->value > Logger::DEBUG && $this->recordsSaved) {
            $this->savedRecords[$record->level->value][] = [
                'level' => $record->level->value,
                'message' => $record->message,
                'context' => $record->context,
            ];
        }
    }

    public function isRecordsSaved(): bool
    {
        return $this->recordsSaved;
    }

    /**
     * All messages logged after this method is called are stored in a class field.
     */
    public function startSavingRecords(): void
    {
        $this->recordsSaved = true;
    }

    /**
     * Stop saving log records and clear the saved records.
     */
    public function stopSavingRecords(): void
    {
        $this->recordsSaved = false;
        $this->savedRecords = [];
    }

    public function getSavedRecords(int $level): array
    {
        return $this->savedRecords[$level] ?? [];
    }

    public function getAllSavedRecords(): array
    {
        return $this->savedRecords;
    }
}
