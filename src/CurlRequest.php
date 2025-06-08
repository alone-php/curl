<?php

namespace AlonePhp\Curl;

use AlonePhp\Curl\Process\Method;
use AlonePhp\Curl\Process\BodyCall;
use AlonePhp\Curl\Process\BodySend;

class CurlRequest {
    /**
     * 批请求
     * @param array $config
     * @return BodySend
     */
    public static function send(array $config): BodySend {
        $curl = [];
        $init = curl_multi_init();
        $config = isset($config['url']) ? [$config] : $config;
        foreach ($config as $key => $val) {
            $value = Method::setCurl($val, $key);
            $curl[$key]['status'] = true;
            $curl[$key]['time'] = microtime(true);
            $curl[$key]['request'] = $value['request'] ?? [];
            $curl[$key]['conn'] = curl_init($value['url']);
            foreach ($value['curl'] as $v) {
                $keys = key($v);
                $curl[$key]['curl'][$keys] = $v[$keys];
            }
            curl_setopt_array($curl[$key]['conn'], $curl[$key]['curl']);
            curl_multi_add_handle($init, $curl[$key]['conn']);
        }
        do {
            $exec = curl_multi_exec($init, $active);
            if ($active) {
                curl_multi_select($init, 10);
            }
        } while ($active && $exec == CURLM_OK);
        $res = [];
        foreach ($curl as $key => $v) {
            $res[$key]['status'] = true;
            $res[$key]['request'] = $v['request'];
            $res[$key]['curl'] = $v['curl'];
            $res[$key]['info'] = curl_getinfo($v['conn']);
            $res[$key]['code'] = $res[$key]['info']['http_code'] ?? 0;
            $size = $res[$key]['info']['header_size'] ?? 0;
            $response = curl_multi_getcontent($v['conn']);
            $res[$key]['header'] = trim(trim(substr($response, 0, $size), "\r\n"), "\r\n");
            if (curl_errno($v['conn'])) {
                $res[$key]['status'] = false;
                $res[$key]['body'] = curl_error($v['conn']);
            } else {
                $res[$key]['body'] = substr($response, $size);
            }
            $res[$key]['body'] = static::bodyEncoding(($config[$key]['body_encoding'] ?? false), $res[$key]['body']);
            curl_multi_remove_handle($init, $v['conn']);
            curl_close($v['conn']);
            $res[$key]['time'] = microtime(true) - $v['time'];
        }
        return new BodySend($res);
    }

    /**
     * 单请求
     * @param array $config
     * @return BodyCall
     */
    public static function call(array $config): BodyCall {
        $config = isset($config['url']) ? $config : $config[key($config)];
        $value = Method::setCurl($config);
        $time = microtime(true);
        $res['status'] = true;
        $res['request'] = $value['request'] ?? [];
        $res['curl'] = $value['curl'];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $value['url']);
        foreach ($value['curl'] as $v) {
            $key = key($v);
            curl_setopt($curl, $key, $v[$key]);
        }
        $response = curl_exec($curl);
        $res['info'] = curl_getinfo($curl);
        $res['code'] = $res['info']['http_code'] ?? 0;
        $size = $res['info']['header_size'] ?? 0;
        $res['header'] = trim(trim(substr($response, 0, $size), "\r\n"), "\r\n");
        if (curl_errno($curl)) {
            $res['status'] = false;
            $res['body'] = curl_error($curl);
        } else {
            $res['body'] = substr($response, $size);
        }
        $res['body'] = static::bodyEncoding(($config['body_encoding'] ?? false), $res['body']);
        $res['time'] = microtime(true) - $time;
        curl_close($curl);
        return new BodyCall($res);
    }

    /**
     * 设置全局代理ip
     * @param array $config
     * @return void
     */
    public static function proxy(array $config): void {
        Method::$proxy['config'] = array_merge(Method::$proxy['config'] ?? [], $config['config'] ?? []);
        Method::$proxy['default'] = ($config['default'] ?? Method::$proxy['config'] ?? '') ?: key(Method::$proxy['config']);
    }

    /**
     * 内容解码
     * @param string|bool|callable $encoding true=自动解码,string=指定解码名称,false=不解码
     * @param string               $body     内容
     * @return string
     */
    public static function bodyEncoding(string|bool|callable $encoding, string $body): string {
        if (!empty($encoding)) {
            if ($encoding === true && function_exists('mb_detect_encoding')) {
                $detectedEncodings = ['UTF-8', 'GBK', 'GB2312', 'BIG5', 'ISO-8859-1'];
                $encoding = mb_detect_encoding($body, $detectedEncodings, true);
            }
            if (is_string($encoding)) {
                if (strtolower($encoding) !== 'utf-8') {
                    if (function_exists('mb_convert_encoding')) {
                        $body = (mb_convert_encoding($body, 'UTF-8', $encoding) ?: $body);
                    } elseif (function_exists('iconv')) {
                        $body = (iconv($encoding, 'UTF-8//IGNORE', $body) ?: $body);
                    }
                }
            } elseif (is_callable($encoding)) {
                $body = (call_user_func($encoding, $body) ?: $body);
            }
        }
        return $body;
    }
}