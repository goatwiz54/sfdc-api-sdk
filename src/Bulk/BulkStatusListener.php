<?php

namespace SfdcApiSdk\Bulk;

class BulkStatusListener
{
    /**
     * @var array
     */
    private $container;

    /**
     * BulkStatusListener constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * コンテナを初期化
     */
    public function init()
    {
        $this->container = [];
    }

    /**
     * 初期ビルド
     */
    public function buildDefault()
    {
        $this->container = [
            "Queued"        => function ($json) {
            },
            "InProgress"    => function ($json) {
            },
            "Completed"     => function ($json) {
            },
            "Failed"        => function ($json) {
            },
            "Not Processed" => function ($json) {
            },
        ];
    }

    /**
     * リスナーを設定する
     * @param $key
     * @param $func
     */
    public function setListener($key, $func)
    {
        $this->container[$key] = $func;
    }

    /**
     * コンテナをからリスナーを取得する
     * @param $key
     */
    public function getListener($key)
    {
        $this->container[$key];
    }

    /**
     * コンテナに存在するか調べる
     * @param $key
     * @return bool
     */
    public function existsListener($key)
    {
        return isset($this->container[$key]);
    }

    /**
     * @param $key
     * @param $json
     */
    protected function callListener($key, $json)
    {
        $this->container[$key]($json);
    }
}
