<?php
/**
 *
 * (c) Copyright Spryker Systems GmbH 2015
 */

namespace SprykerEngine\Shared\Lumberjack\Model;

use SprykerEngine\Shared\Lumberjack\Model\Writer\WriterInterface;

abstract class AbstractEventJournal
{

    /**
     * @var DataCollectorInterface[]
     */
    private $dataCollectors = [];

    /**
     * @var WriterInterface[]
     */
    private $eventWriters = [];

    public function __construct()
    {
        $this->addDefaultCollectors();
    }

    protected function addDefaultCollectors()
    {
        $this->addDataCollector(new ServerDataCollector());
        $this->addDataCollector(new RequestDataCollector());
        $this->addDataCollector(new EnvironmentDataCollector());
    }

    /**
     * @param DataCollectorInterface $dataCollector
     */
    public function addDataCollector(DataCollectorInterface $dataCollector)
    {
        $this->dataCollectors[get_class($dataCollector)] = $dataCollector;
    }

    /**
     * @param EventInterface $event
     */
    public function applyCollectors(EventInterface $event)
    {
        foreach ($this->dataCollectors as $collector) {
            $event->addFields($collector->getData());
        }
    }

    /**
     * @param EventInterface $event
     */
    public function saveEvent(EventInterface $event)
    {
        $this->applyCollectors($event);
        $this->writeEvent($event);
    }

    /**
     * @param WriterInterface $writer
     */
    public function addEventWriter(WriterInterface $writer)
    {
        $this->eventWriters[get_class($writer)] = $writer;
    }

    /**
     * @param EventInterface $event
     */
    protected function writeEvent(EventInterface $event)
    {
        foreach ($this->eventWriters as $writer) {
            $writer->write($event);
        }
    }
}
