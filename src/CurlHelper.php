<?php

namespace AlonePhp\Curl;

use AlonePhp\Curl\Process\Method;
use AlonePhp\Curl\Process\BodyCall;

class CurlHelper {
    protected array        $config = [];
    protected static array $conf   = [];

    /**
     * 设置公共配置
     * @param array $config
     * @return void
     */
    public static function setConfig(array $config): void {
        static::$conf = $config;
    }

    /**
     * 请求url
     * @param string $url
     * @param string $mode
     * @return static
     */
    public function url(string $url, string $mode = 'get'): static {
        $this->config['url'] = $url;
        return $this->mode($mode);
    }

    /**
     * 请求路径
     * @param string $path
     * @return $this
     */
    public function path(string $path): static {
        $this->config['path'] = $path;
        return $this;
    }

    /**
     * 请求模式(get,post,put,patch,delete,head,connect,options), 支持请求体[post,put,patch,delete]
     * @param string $mode
     * @return $this
     */
    public function mode(string $mode = "get"): static {
        $this->config['mode'] = $mode;
        return $this;
    }

    /**
     * URL请求参数 string|array
     * @param array|string|int $key 支持json字符串,key=val,array
     * @param string|int|null  $val
     * @return $this
     */
    public function query(array|string|int $key, string|int|null $val = null): static {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->query($k, $v);
            }
        } else {
            if (is_string($key) && $val === null) {
                if (empty($array = Method::isJson($key))) {
                    parse_str($key, $array);
                }
                $this->query($array);
            } else {
                $this->config['query'][$key] = $val;
            }
        }
        return $this;
    }

    /**
     * 自定请求内容,优先级1
     * @param mixed $body
     * @return $this
     */
    public function text(mixed $body): static {
        $this->config['text'] = $body;
        return $this;
    }

    /**
     * 请求内容array,优先级2
     * @param array|string|int $key 支持json字符串,key=val,array
     * @param string|int|null  $val
     * @return $this
     */
    public function body(array|string|int $key, string|int|null $val = null): static {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->body($k, $v);
            }
        } else {
            if (is_string($key) && $val === null) {
                if (empty($array = Method::isJson($key))) {
                    parse_str($key, $array);
                }
                $this->body($array);
            } else {
                $this->config['body'][$key] = $val;
            }
        }
        return $this;
    }

    /**
     * 上传文件
     * @param array|string|int $key
     * @param string|int|null  $val
     * @return $this
     */
    public function file(array|string|int $key, string|int|null $val = null): static {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->file($k, $v);
            }
        } else {
            $this->config['file'][$key] = $val;
        }
        return $this;
    }

    /**
     * 设置头部信息
     * @param array|string|int $key
     * @param string|int|null  $val
     * @return $this
     */
    public function header(array|string|int $key, string|int|null $val = null): static {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->header($k, $v);
            }
        } else {
            $this->config['header'][$key] = $val;
        }
        return $this;
    }

    /**
     * 设置cookie
     * @param array|string|int $key
     * @param string|int|null  $val
     * @return $this
     */
    public function cookie(array|string|int $key, string|int|null $val = null): static {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->cookie($k, $v);
            }
        } else {
            $this->config['cookie'][$key] = $val;
        }
        return $this;
    }

    /**
     * 设置来路,true=使用当前域名,string自定
     * @param bool|string $origin
     * @return $this
     */
    public function origin(bool|string $origin = true): static {
        $this->config['origin'] = $origin;
        return $this;
    }

    /**
     * 设置浏览器信息,true=使用默认浏览器,string自定
     * @param bool|string $browser
     * @return $this
     */
    public function browser(bool|string $browser = true): static {
        $this->config['browser'] = $browser;
        return $this;
    }

    /**
     * 设置基本认证信息
     * @param string $auth
     * @return $this
     */
    public function auth(string $auth): static {
        $this->config['auth'] = $auth;
        return $this;
    }

    /**
     * 设置代理ip true=默认代理,string=设置全局代理key,false=关闭,array=设置单独代理
     * @param bool|array|string $proxy [ip,port,user,type=http|socks5,auth=basic|ntlm]
     * @return $this
     */
    public function proxy(bool|array|string $proxy): static {
        $this->config['proxy'] = $proxy;
        return $this;
    }

    /**
     * 设置伪装ip
     * @param string $req_ip
     * @param array  $req_ip_name 伪装ip的key列表
     * @return $this
     */
    public function reqIp(string $req_ip, array $req_ip_name = []): static {
        $this->config['req_ip'] = $req_ip;
        $this->config['req_ip_name'] = $req_ip_name;
        return $this;
    }

    /**
     * 是否url编码
     * @param bool $encode
     * @return $this
     */
    public function queryEncode(bool $encode = true): static {
        $this->config['query_encode'] = $encode;
        return $this;
    }

    /**
     * 是否ajax提交
     * @param bool $ajax
     * @return $this
     */
    public function ajax(bool $ajax = true): static {
        $this->config['ajax'] = $ajax;
        return $this;
    }

    /**
     * 是否json
     * @param bool $json
     * @return $this
     */
    public function json(bool $json = true): static {
        $this->config['json'] = $json;
        return $this;
    }

    /**
     * 连接时间,默认10
     * @param int $connect
     * @return $this
     */
    public function connect(int $connect = 10): static {
        $this->config['connect'] = $connect;
        return $this;
    }

    /**
     * 超时时间,默认10
     * @param int $timeout
     * @return $this
     */
    public function timeout(int $timeout = 10): static {
        $this->config['timeout'] = $timeout;
        return $this;
    }

    /**
     * 设置解码名称
     * @param string $encoding
     * @return $this
     */
    public function encoding(string $encoding): static {
        $this->config['encoding'] = $encoding;
        return $this;
    }

    /**
     * 内容解码
     * true=自动解码,false=不解码,string=指定解码名称,callable=自定义解码方法
     * @param string|bool|callable $encoding
     * @return $this
     */
    public function bodyEncoding(string|bool|callable $encoding = true): static {
        $this->config['body_encoding'] = $encoding;
        return $this;
    }

    /**
     * 是否自动跳转,默认跳转
     * @param bool $follow
     * @return $this
     */
    public function follow(bool $follow = true): static {
        $this->config['follow'] = $follow;
        return $this;
    }

    /**
     * 进度条
     * @param callable $progress
     * @return $this
     */
    public function progress(callable $progress): static {
        $this->config['progress'] = $progress;
        return $this;
    }

    /**
     * 是否检查证书,默认不检查
     * @param bool $ssl_peer
     * @return $this
     */
    public function sslPeer(bool $ssl_peer = false): static {
        $this->config['ssl_peer'] = $ssl_peer;
        return $this;
    }

    /**
     * 是否检查证书公用名,默认不检查
     * @param bool $ssl_host
     * @return $this
     */
    public function sslHost(bool $ssl_host = false): static {
        $this->config['ssl_host'] = $ssl_host;
        return $this;
    }

    /**
     * 自定义Curl设置
     * @param array|string|int $key
     * @param string|int|null  $val
     * @return $this
     */
    public function curl(array|string|int $key, string|int|null $val = null): static {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->curl($k, $v);
            }
        } else {
            $this->config['curl'][$key] = $val;
        }
        return $this;
    }

    /**
     * 获取配置,可使用 alone_curl_send 批量发送
     * @param bool $cache 删除缓存数据
     * @return array
     */
    public function config(bool $cache = true): array {
        $config = $this->config;
        ($cache === true) && $this->config = [];
        return $config;
    }

    /**
     * 执行请求 - 方式1
     * @param bool $cache 删除缓存数据
     * @return BodyCall
     */
    public function exec(bool $cache = true): BodyCall {
        return CurlRequest::send($this->config($cache))->exec();
    }

    /**
     * 执行请求 - 方式2
     * @param bool $cache 删除缓存数据
     * @return BodyCall
     */
    public function call(bool $cache = true): BodyCall {
        return CurlRequest::call($this->config($cache));
    }

    /**
     * @param array $config
     */
    public function __construct(array $config = []) {
        $this->config = array_merge(static::$conf, $config);
    }
}