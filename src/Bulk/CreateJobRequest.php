<?php

namespace SfdcApiSdk\Bulk;


class CreateJobRequest
{
    private $object;
    private $operation;
    private $externalFieldName;
    private $contentType;

    /**
     * @return mixed
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param mixed $object
     */
    public function setObject($object)
    {
        $this->object = $object;
    }

    /**
     * @return mixed
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param mixed $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }

    /**
     * @return mixed
     */
    public function getExternalFieldName()
    {
        return $this->externalFieldName;
    }

    /**
     * @param mixed $externalFieldName
     */
    public function setExternalFieldName($externalFieldName)
    {
        $this->externalFieldName = $externalFieldName;
    }

    /**
     * @return mixed
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param mixed $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }
}
