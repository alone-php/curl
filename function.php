<?php

use AlonePhp\Curl\CurlHelper;
use AlonePhp\Curl\CurlRequest;
use AlonePhp\Curl\Process\BodyCall;
use AlonePhp\Curl\Process\BodySend;

/**
 * 设置全局代理ip
 * @param array $config
 * @return void
 */
function alone_curl_proxy(array $config): void {
    CurlRequest::proxy($config);
}

/**
 * curl公共配置,只使用使用到alone_curl方法时生效
 * @param array $config
 * @return void
 */
function alone_curl_config(array $config): void {
    CurlHelper::setConfig($config);
}

/**
 * 配置Curl
 * @param string $url  请求url
 * @param string $mode 请求模式(get,post,put,patch,delete,head,connect,options), 支持请求体[post,put,patch,delete]
 * @return CurlHelper
 */
function alone_curl(string $url, string $mode = 'get'): CurlHelper {
    return (new CurlHelper())->url($url, $mode);
}

/**
 * 单请求
 * @param array $config
 * @return BodyCall
 */
function alone_curl_exec(array $config): BodyCall {
    return CurlRequest::send($config)->exec();
}

/**
 * 批请求
 * @param array $config
 * @return BodySend
 */
function alone_curl_send(array $config): BodySend {
    return CurlRequest::send($config);
}

/**
 * 单请求
 * @param array $config
 * @return BodyCall
 */
function alone_curl_call(array $config): BodyCall {
    return CurlRequest::call($config);
}