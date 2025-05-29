```text
composer require alone-php/curl
```

## 基本用法

### 初始化请求

支持的 HTTP 请求方法：

- GET
- POST
- PUT
- PATCH
- DELETE
- HEAD
- CONNECT
- OPTIONS

```php
<?php
// 创建一个 curl 请求 (url, 请求方法)
$curl = alone_curl('https://api.example.com/users', 'get');
```

### 执行请求

```php

// 响应内容编码方式
$curl->bodyEncoding(true); // 自动编码

// 执行请求方式1 并获取响应
$response = $curl->exec();
// 执行请求方式2 并获取响应
$response = $curl->call();

// 获取响应内容
$body = $response->getBody();
```

## 请求方法

```php
// GET 请求
$curl = alone_curl('https://api.example.com/users', 'get');

// POST 请求
$curl = alone_curl('https://api.example.com/users', 'post');
```

## 请求参数

### URL 参数 (Query String)

```php
// 添加单个参数
$curl->query('page', 1);

// 添加多个参数
$curl->query([
    'page' => 1,
    'limit' => 10,
    'sort' => 'desc'
]);

// 使用字符串形式
$curl->query('page=1&limit=10');

// 使用 JSON 字符串
$curl->query('{"page":1,"limit":10}');

// 是否对参数进行 URL 编码 (默认为 true)
$curl->queryEncode(true);
```

### 请求体 (Body)

```php
// 设置文本内容（优先级最高）
$curl->text('Hello World');

// 添加单个键值对
$curl->body('name', 'value');

// 添加多个键值对
$curl->body([
    'name' => 'John',
    'age' => 30
]);

// 使用 key=value 格式字符串
$curl->body('name=John&age=30');

// 使用 JSON 字符串
$curl->body('{"name":"John","age":30}');
```

### 文件上传

```php
// 上传单个文件
$curl->file('avatar', '/path/to/file.jpg');

// 上传多个文件
$curl->file([
    'avatar' => '/path/to/avatar.jpg',
    'document' => '/path/to/doc.pdf'
]);
```

## 请求头和配置

### 设置请求头

```php
// 设置单个请求头
$curl->header('Authorization', 'Bearer token123');

// 设置多个请求头
$curl->header([
    'Authorization' => 'Bearer token123',
    'Accept' => 'application/json'
]);
```

### 设置 Cookie

```php
// 设置单个 Cookie
$curl->cookie('session_id', 'abc123');

// 设置多个 Cookie
$curl->cookie([
    'session_id' => 'abc123',
    'user_id' => '456'
]);
```

### 设置请求来源和浏览器信息

```php
// 设置来源 (Origin)
$curl->origin(true); // 使用当前域名
$curl->origin('https://example.com'); // 自定义来源

// 设置浏览器信息 (User-Agent)
$curl->browser(true); // 使用默认浏览器信息
$curl->browser('Mozilla/5.0 ...'); // 自定义浏览器信息
```

### 设置 JSON 格式

```php
// 设置请求为 JSON 格式
$curl->json(true);
```

### 设置 AJAX 请求

```php
// 设置为 AJAX 请求
$curl->ajax(true);
```

## 高级配置

### 超时设置

```php
// 设置连接超时时间 (秒)
$curl->connect(10);

// 设置请求超时时间 (秒)
$curl->timeout(30);
```

### 编码设置

```php
// 设置响应编码
$curl->encoding('gzip');
```

### SSL 设置

```php
// 是否验证 SSL 证书
$curl->sslPeer(false);

// 是否验证 SSL 主机名
$curl->sslHost(false);
```

### 重定向设置

```php
// 是否跟随重定向
$curl->follow(true);
```

### 代理设置

```php
// 使用默认代理
$curl->proxy(true);

// 设置单独代理
$curl->proxy([
    'ip' => '127.0.0.1',
    'port' => 8080,
    'user' => 'username',
    'pass' => 'password',
    'type' => 'http', // 或 'socks5'
    'auth' => 'basic' // 或 'ntlm'
]);

// 关闭代理
$curl->proxy(false);

// 设置全局代理 (所有请求共用)
alone_curl_proxy([
    'ip' => '127.0.0.1',
    'port' => 8080
]);
```

### IP 伪装

```php
// 设置伪装 IP
$curl->reqIp('8.8.8.8');

// 设置伪装 IP 和自定义请求头
$curl->reqIp('8.8.8.8', ['X-Forwarded-For', 'Client-IP']);
```

### 基本认证

```php
// 设置基本认证
$curl->auth('username:password');
```

## 批量请求

### 发送批量请求

```php
// 方法一：使用配置数组创建批量请求
$batch = alone_curl_send([
    [
        'url' => 'https://api.example.com/users',
        'mode' => 'get'
    ],
    [
        'url' => 'https://api.example.com/posts',
        'mode' => 'get'
    ]
]);

// 方法二：使用 alone_curl 对象创建批量请求
$curl = alone_curl_send([
    'demo' => alone_curl('https://api.example.com/users', 'get')->path('path')->header()->config(),
    1=>alone_curl('https://api.example.com/posts', 'get')->path('path')->header()->config(),
    2=>alone_curl('https://api.example.com/comments', 'get')->path('path')->header()->config()
]);

// 执行批量请求
$responses = $batch->exec();

// 获取每个响应
foreach ($responses as $response) {
    echo $response->getBody();
}

// 使用回调函数处理所有响应
$curl->handle(function($key, $req) {
    // 是否请求成功
    dump($req->getStatus());
    
    // 获取请求信息
    dump($req->getRequest());
    
    // 获取响应时间
    dump($req->getTime());
    
    // 其他响应处理方法与单请求相同
    // 例如：$req->getBody(), $req->getJson(), $req->getHeader() 等
});
```

## 全局配置

设置全局配置，所有请求都会使用：

```php
alone_curl_config([
    'connect' => 5,
    'timeout' => 30,
    'header' => [
        'User-Agent' => 'My Custom Agent'
    ]
]);
```

## 响应处理

```php
$response = $curl->exec();

// 获取响应状态码
$statusCode = $response->getInfo('http_code');

// 获取响应内容
$body = $response->getBody();

// 获取 JSON 响应并解析
$json = $response->getJson();

// 获取响应头
$headers = $response->getHeader();

// 获取特定响应头
$contentType = $response->getHeader('Content-Type');

// 获取请求信息
$info = $response->getInfo();
```

## 实例

```php
<?php

// 创建一个 POST 请求
$curl = alone_curl('https://api.example.com/users', 'post');

// 添加查询参数
$curl->query(['token' => 'abc123']);

// 添加请求体
$curl->body([
    'name' => '张三',
    'email' => 'zhangsan@example.com',
    'age' => 30
]);

// 设置请求头
$curl->header([
    'Authorization' => 'Bearer token123',
    'Accept' => 'application/json'
]);

// 设置为 JSON 请求
$curl->json(true);

// 执行请求
$response = $curl->call();

// 处理响应
if ($response->getInfo('http_code') === 200) {
    $result = $response->getArr();
    echo "用户ID: " . $result['id'];
} else {
    echo "请求失败: " . $response->getBody();
}
```