<?php

namespace AlonePhp\Curl\Process;

use CURLFile;

class Method {

    //默认浏览器
    protected static string $browser = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.74 Safari/537.36 Edg/99.0.1150.46';

    //默认请求IP字段名
    protected static array $reqIpName = ['CLIENT-IP', 'X-FORWARDED-FOR', 'CDN_SRC_IP', 'CF_CONNECTING_IP'];

    //全局代理ip
    public static array $proxy = [
        //默认使用名称
        'default' => 'default',
        //代理配置列表
        'config'  => [
            'default' => [
                //ip
                'ip'   => '',
                //端口
                'port' => '',
                //认证信息
                'user' => '',
                //http,socks5
                'type' => '',
                //basic,ntlm
                'auth' => ''
            ]
        ]
    ];

    //默认请求参数
    protected static array $config = [
        //请求url
        'url'           => '',
        //请求路径
        'path'          => '',
        //请求模式(get,post,put,patch,delete,head,connect,options), 支持请求体[post,put,patch,delete]
        'mode'          => 'get',
        //URL请求参数 string|array
        'query'         => [],
        //URL请求参数是否url编码
        'query_encode'  => false,
        //设置头部信息
        'header'        => [],
        //自定请求内容,优先级1
        'text'          => '',
        //请求体array,优先级2
        'body'          => [],
        //是否ajax提交
        'ajax'          => false,
        //是否json
        'json'          => false,
        //是否自动跳转,默认跳转
        'follow'        => true,
        //上传文件(body要设置array)
        'file'          => [],
        //设置cookie
        'cookie'        => [],
        //设置来路,true=使用当前域名,string自定
        'origin'        => false,
        //设置浏览器信息,true=使用默认浏览器,string自定
        'browser'       => true,
        //设置基本认证信息
        'auth'          => '',
        //设置解码名称
        'encoding'      => '',
        //内容解码true=自动解码,false=不解码,string=指定解码名称,callable=自定义解码方法
        'body_encoding' => '',
        //连接时间,默认10
        'connect'       => 10,
        //超时时间,默认10
        'timeout'       => 10,
        //设置代理ip true=默认代理,string=设置全局代理key,false=关闭,array=设置单独代理
        'proxy'         => false,
        //设置伪装ip
        'req_ip'        => '',
        //伪装ip的key列表
        'req_ip_name'   => [],
        //是否检查证书,默认不检查
        'ssl_peer'      => false,
        //是否检查证书公用名,默认不检查
        'ssl_host'      => false,
        //自定义Curl设置
        'curl'          => []
    ];

    /**
     * 获取url详细/修改url中的get参数
     * @param string       $url
     * @param array|string $set    要设置的get
     * @param bool         $encode get是否url编码
     * @return array
     */
    public static function urlShow(string $url, array|string $set = [], bool $encode = false): array {
        $parse = parse_url($url);
        $array['scheme'] = $parse['scheme'] ?? 'http';
        $array['host'] = $parse['host'] ?? '';
        $array['port'] = $parse['port'] ?? '';
        $array['path'] = $parse['path'] ?? '';
        $query = $parse['query'] ?? '';
        $get = [];
        parse_str($query, $get);
        $array['get'] = $get;
        if (is_string($set)) {
            $get = [];
            parse_str(($set ?: ''), $get);
            $array['get'] = array_merge($array['get'], $get);
        } else {
            $array['get'] = array_merge($array['get'], $set);
        }
        $array['fragment'] = $parse['fragment'] ?? '';
        $array['query'] = '';
        if (!empty($array['get'])) {
            foreach ($array['get'] as $k => $v) {
                $val = trim(urldecode($v));
                $val = ($encode === true ? urlencode($val) : $val);
                $array['query'] .= $k . '=' . $val . '&';
            }
        }
        $array['query'] = trim($array['query'], '&');
        $array['url'] = $array['scheme'] . '://' . $array['host']
                        . (!empty($array['port']) ? ':' . $array['port'] : '')
                        . ($array['path'] ?? "")
                        . (!empty($array['query']) ? ('?' . $array['query']) : '')
                        . (!empty($array['fragment']) ? '#' . $array['fragment'] : '');
        return $array;
    }

    /**
     * 判断字符串是否json,返回array
     * @param mixed     $json
     * @param bool|null $associative
     * @param int       $depth
     * @param int       $flags
     * @return mixed
     */
    public static function isJson(mixed $json, bool $associative = true, int $depth = 512, int $flags = 0): mixed {
        $json = json_decode((is_string($json) ? ($json ?: '') : ''), $associative, $depth, $flags);
        return (($json && is_object($json)) || (is_array($json) && $json)) ? $json : [];
    }

    /**
     * 设置Curl
     * @param array $config
     * @return array
     */
    public static function setCurl(array $config): array {
        $config = array_merge(static::$config, $config);
        $conf = function($key, $default = '') use ($config) {
            return ($config[$key] ?? $default) ?: $default;
        };
        $url = trim($conf('url'), '/');
        $path = ltrim($conf('path'), '/');
        $uri = $url . (!empty($path) ? "/$path" : "");
        if ($conf('query')) {
            $uri = static::urlShow($uri, $conf('query'), $conf('query_encode', false))['url'];
        }
        //请求方法
        $mode = strtoupper($conf('mode', 'GET'));
        //重定向
        $follow = isset($config['follow']) ? $conf('follow', false) : true;
        //请求头
        $headers = [];
        //是否ajax
        if ($conf('ajax')) {
            $headers['X-Requested-With'] = 'XMLHttpRequest';
        }
        //是否json
        if ($conf('json')) {
            $headers['Content-Type'] = 'application/json';
        }
        //请求体
        $text = $conf('text', '');
        if (!empty($text)) {
            $body = $text;
        } else {
            $body = $conf('body', []);
        }
        if (in_array($mode, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            if (!empty($text)) {
                $headers['Content-Type'] = 'text/plain';
                $headers['Content-Length'] = strlen($body);
            } else {
                if (is_array($body)) {
                    //上传文件
                    if (!empty($file = $conf('file'))) {
                        foreach ($file as $key => $val) {
                            if (!empty($filePath = realpath($val))) {
                                $body[$key] = new CURLFile($filePath);
                            }
                        }
                    }
                }
                if (!empty($body)) {
                    if ($conf('json')) {
                        $body = is_array($body) ? json_encode($body) : $body;
                    } else {
                        $body = is_array($body) ? http_build_query($body) : $body;
                    }
                    $headers['Content-Length'] = strlen($body);
                }
            }
            $curl[] = [CURLOPT_POSTFIELDS => $body];
        }
        //设置cookie
        if (!empty($cookie = $conf('cookie'))) {
            if (is_array($cookie)) {
                $cookies = '';
                foreach ($cookie as $key => $val) {
                    if (!empty($val)) {
                        $cookies .= $key . '=' . $val . ';';
                    }
                }
                $cookie = $cookies;
            }
        }
        //返回响应而不是输出到屏幕
        $curl[] = [CURLOPT_RETURNTRANSFER => true];
        //是否返回头部信息
        $curl[] = [CURLOPT_HEADER => true];
        //跟随重定向
        if (!empty($follow)) {
            $curl[] = [CURLOPT_FOLLOWLOCATION => true];
            $curl[] = [CURLOPT_AUTOREFERER => true];
        } else {
            $curl[] = [CURLOPT_FOLLOWLOCATION => false];
        }
        //是否POST
        if ($mode == 'POST') {
            $curl[] = [CURLOPT_POST => true];
        }
        //请求方法
        $curl[] = [CURLOPT_CUSTOMREQUEST => $mode];
        //设置cookie
        if (!empty($cookie)) {
            $curl[] = [CURLOPT_COOKIE => $cookie];
        }
        //设置解码名称
        if (!empty($encoding = $conf('encoding'))) {
            $curl[] = [CURLOPT_ENCODING => $encoding];
        }
        //设置基本认证信息
        if (!empty($auth = $conf('auth'))) {
            $curl[] = [CURLOPT_USERPWD => $auth];
        }
        //连接时间,设置为0，则无限等待
        $curl[] = [CURLOPT_CONNECTTIMEOUT => $conf('connect', 10)];
        //超时时间,设置为0，则无限等待
        $curl[] = [CURLOPT_TIMEOUT => $conf('timeout', 10)];
        //否检查证书,默认不检查
        $curl[] = [CURLOPT_SSL_VERIFYPEER => $conf('ssl_peer', false)];
        //设置成 2，会检查公用名是否存在，并且是否与提供的主机名匹配。 0 为不检查名称。 在生产环境中，这个值应该是 2（默认值）
        $curl[] = [CURLOPT_SSL_VERIFYHOST => $conf('ssl_host', false)];
        //自动设置浏览器信息
        $browser = $conf('browser');
        if ($browser === true) {
            $curl[] = [CURLOPT_USERAGENT => static::$browser];
        } elseif (!empty($browser)) {
            $curl[] = [CURLOPT_USERAGENT => $browser];
        }
        //设置来源
        if (!empty($origin = $conf('origin'))) {
            if ($origin === true) {
                $origin = ((explode('/', (explode('://', $uri)[1] ?? ''))[0]) ?? $url) ?: $url;
            }
            $curl[] = [CURLOPT_REFERER => $origin];
            $headers['REFERER'] = $origin;
            $headers['ORIGIN'] = $origin;
        }
        //伪装ip
        if (!empty($ip = $conf('req_ip'))) {
            $name_list = $conf('req_ip_name', static::$reqIpName);
            foreach ($name_list as $val) {
                $headers[$val] = $ip;
            }
        }
        $headers = array_merge($headers, $conf('header', []));
        //设置请求头
        if (!empty($headers)) {
            $header = [];
            foreach ($headers as $key => $val) {
                $header[] = is_numeric($key) ? $val : "$key: $val";
            }
            $curl[] = [CURLOPT_HTTPHEADER => $header];
        }
        //设置代理ip
        if (!empty($proxy = $conf('proxy'))) {
            if (empty(is_array($proxy))) {
                $pn = ($proxy === true) ? (static::$proxy['default'] ?? '') : $proxy;
                $pc = static::$proxy['config'] ?? [];
                if (!empty($pn) && !empty($pc)) {
                    $proxy = $pc[$pn] ?? [];
                }
            }
            if (!empty($ip = ($proxy['ip'] ?? ''))) {
                $curl[] = [CURLOPT_PROXY => $ip];
                if (!empty($port = ($proxy['port'] ?? ''))) {
                    $curl[] = [CURLOPT_PROXYPORT => $port];
                }
                if (!empty($user = ($proxy['user'] ?? ''))) {
                    $curl[] = [CURLOPT_PROXYUSERPWD => $user];
                }
                if (!empty($type = ($proxy['type'] ?? ''))) {
                    $curl[] = [CURLOPT_PROXYTYPE => ($type == 'http' ? CURLPROXY_HTTP : ($type == 'socks5' ? CURLPROXY_SOCKS5 : $type))];
                }
                if (!empty($auth = ($proxy['auth'] ?? ''))) {
                    $curl[] = [CURLOPT_PROXYAUTH => ($auth == 'basic' ? CURLAUTH_BASIC : ($auth == 'ntlm' ? CURLAUTH_NTLM : $auth))];
                }
            }
        }
        //自定设置
        if (!empty($curls = $conf('curl'))) {
            foreach ($curls as $key => $val) {
                $curl[] = [$key => $val];
            }
        }
        $request['url'] = $uri;
        $request['mode'] = $mode;
        if (!empty($body)) {
            $request['body'] = $body;
        }
        if (!empty($headers)) {
            $request['headers'] = $headers;
        }
        return [
            'url'     => $uri,
            'curl'    => $curl,
            'request' => $request,
        ];
    }
}