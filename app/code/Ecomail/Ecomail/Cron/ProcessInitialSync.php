<?php

namespace Ecomail\Ecomail\Cron;

use Ecomail\Ecomail\Model\ApiLog;
use Ecomail\Ecomail\Model\SyncManager;

class ProcessInitialSync
{
    /**
     * @var SyncManager
     */
    private $syncManager;

    /**
     * @var ApiLog
     */
    private $apiLog;

    /**
     * @param SyncManager $syncManager
     * @param ApiLog $apiLog
     */
    public function __construct(
        SyncManager $syncManager,
        ApiLog $apiLog
    ) {
        $this->syncManager = $syncManager;
        $this->apiLog = $apiLog;
    }

    /**
     * Run one sync batch and cleanup old API logs.
     */
    public function execute(): void
    {
        $this->syncManager->processNextBatch();
        $this->apiLog->cleanup();
    }
}
