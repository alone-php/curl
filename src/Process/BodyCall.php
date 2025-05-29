<?php

namespace AlonePhp\Curl\Process;

class BodyCall {
    //响应状态
    protected bool $status = false;

    //响应状态码
    protected string|int $code = 0;

    //响应头部信息
    protected string $header = '';

    //响应内容
    protected string $body = '';

    //请求信息
    protected array $request = [];

    //响应时间
    protected int|float $time = 0;

    //curl_get_info()响应信息
    protected array $info = [];

    public function __construct(array $res) {
        foreach ($res as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * 调试信息
     * @param bool|null $header true=原样头,false=array头,null=不返回头
     * @return array
     */
    public function getDebug(bool|null $header = null): array {
        $data = [
            'request'  => $this->request,
            'response' => ['body' => $this->body],
            'execute'  => $this->time,
        ];
        if ($header !== null) {
            $data['response']['header'] = ($header === true ? $this->getHeader() : $this->getHead());
        }
        return $data;
    }

    /**
     * 响应状态
     * @return bool
     */
    public function getStatus(): bool {
        return $this->status;
    }

    /**
     * 状态码
     * @return string|int
     */
    public function getCode(): string|int {
        return $this->code;
    }

    /**
     * 头部信息
     * @return string
     */
    public function getHeader(): string {
        return $this->header;
    }

    /**
     * 响应内容
     * @return string
     */
    public function getBody(): string {
        return $this->body;
    }

    /**
     * 请求信息
     * @return array
     */
    public function getRequest(): array {
        return $this->request;
    }

    /**
     * 响应时间
     * @return string
     */
    public function getTime(): string {
        return $this->time;
    }

    /**
     * 响应信息
     * curl_get_info array
     * @param string|int|null $key
     * @param mixed           $def
     * @return mixed
     */
    public function getInfo(string|int|null $key = null, mixed $def = ''): mixed {
        return isset($key) ? ($this->info[$key] ?? $def) : $this->info;
    }

    /**
     * 获取body信息 array
     * @return array
     */
    public function getArr(): array {
        return Method::isJson($this->body);
    }

    /**
     * 获取头部信息 array
     * @param string|int|null $key
     * @param mixed           $def
     * @return mixed
     */
    public function getHead(string|int|null $key = null, mixed $def = ''): mixed {
        $headers = [];
        $header = explode("\r\n", trim($this->header));
        foreach ($header as $val) {
            if (str_contains($val, ':')) {
                [$k, $v] = explode(': ', $val, 2);
                $keys = str_replace(' ', '-', strtolower(trim($k)));
                $keys = str_replace('_', '-', $keys);
                $headers[$keys] = Method::isJson($v) ?: $v;
            }
        }
        return isset($key) ? ($headers[strtolower(trim($key))] ?? $def) : $headers;
    }


    /**
     * 获取所有 Cookie 信息的二维数组
     * @return array
     */
    public function getCookie(): array {
        preg_match_all('/^Set-Cookie:\s*([^\r\n]*)/mi', $this->header, $matches);
        foreach ($matches[1] as $cookie) {
            preg_match_all('/([^=;]+)=([^;]*);?\s*/', $cookie, $cookieAttributes);
            $path = '/';
            $expires = '';
            foreach ($cookieAttributes[1] as $index => $attrKey) {
                $attrValue = $cookieAttributes[2][$index];
                if (strtolower($attrKey) === 'path') {
                    $path = $attrValue;
                } elseif (strtolower($attrKey) === 'expires') {
                    $expires = $attrValue;
                }
            }
            if ($expires) {
                $expiresTime = strtotime($expires);
                if ($expiresTime !== false) {
                    $expires = gmdate('Y-m-d H:i:s', $expiresTime + 8 * 3600); // GMT+8
                } else {
                    $expires = '';
                }
            }
            foreach ($cookieAttributes[1] as $index => $key) {
                $value = $cookieAttributes[2][$index];
                if (!in_array(strtolower($key), ['path', 'expires', 'samesite', 'secure', 'httponly'])) {
                    $arr[] = ['key' => $key, 'value' => $value, 'path' => $path, 'expires' => $expires];
                }
            }
        }
        return $arr ?? [];
    }
}