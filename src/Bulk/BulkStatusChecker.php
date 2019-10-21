<?php

namespace SfdcApiSdk\Bulk;

class BulkStatusChecker
{
    private $batchJobInfo;

    /**
     * BulkStatusChecker constructor.
     * @param $limitSec
     */
    public function __construct($batchJobInfo)
    {
        $this->batchJobInfo = $batchJobInfo;
    }

    /**
     * @return mixed
     */
    public function getBatchJobInfo()
    {
        return $this->batchJobInfo;
    }

    /**
     * @param mixed $batchJobInfo
     */
    public function setBatchJobInfo($batchJobInfo)
    {
        $this->batchJobInfo = $batchJobInfo;
    }
}
