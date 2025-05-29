<?php

namespace AlonePhp\Curl\Process;

class BodySend {
    public array $response = [];

    public function __construct(array $res) {
        $this->response = $res;
    }

    /**
     * 获取指定的key
     * @param mixed $key
     * @return BodyCall
     */
    public function exec(mixed $key = null): BodyCall {
        $keys = $key ?? key($this->response);
        return new BodyCall($this->response[$keys] ?? []);
    }

    /**
     * 处理
     * @param callable $callable (key,BodyReq)
     * @return void
     */
    public function handle(callable $callable): void {
        foreach ($this->response as $key => $val) {
            $callable($key, new BodyCall($val));
        }
    }
}