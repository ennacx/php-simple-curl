# PHP - Simple cURL Library

[![PHP Version Require](http://poser.pugx.org/ennacx/php-simple-curl/require/php)](https://packagist.org/packages/ennacx/php-simple-curl)
[![Latest Stable Version](http://poser.pugx.org/ennacx/php-simple-curl/v)](https://packagist.org/packages/ennacx/php-simple-curl)
[![Total Downloads](http://poser.pugx.org/ennacx/php-simple-curl/downloads)](https://packagist.org/packages/ennacx/php-simple-curl)
[![Latest Unstable Version](http://poser.pugx.org/ennacx/php-simple-curl/v/unstable)](https://packagist.org/packages/ennacx/php-simple-curl)
[![License](http://poser.pugx.org/ennacx/php-simple-curl/license)](https://packagist.org/packages/ennacx/php-simple-curl)

## 概要
cURLを極力シンプルだけど幅広く対応した<strike>い</strike>、PHP専用のライブラリ。

cURLって設定項目多すぎてわかんない。もっとシンプルに出来ないものか。<br>
PHP8になっても ```curl_init();``` やら ```curl_close();``` やらでいちいち手続きしたり、 ```CURLOPT_XXXX``` ってなんぞ…ってのが **すごく** 多いのしんどい。

って常々思ってたので作りました。

## 特長
* シンプルさを追求
* メソッドチェーンで設定追加できる
* 設定メソッドは最後に呼び出した内容で上書きする

## 動作要件
* PHP 8.2 以上
* PHPモジュール ```curl```, ```openssl```
* composer 2.0 以上

## インストール
```
composer require ennacx/php-simple-curl
```

## 使い方
### めっちゃシンプルに
```php
<?php
// まず初期化をします。
$lib = new \Ennacx\SimpleCurl\SimpleCurlLib('https://www.php.net/');

// exec()メソッドでcurlの実行をします。
/** @var \Ennacx\SimpleCurl\Entity\ResponseEntity $result */
$result = $lib->exec();

// エンティティーには各cURLの実行結果を扱いやすくまとめています。
echo $result->result; // (bool)
```

### レスポンスデータが欲しい場合
```php
<?php
$lib = new \Ennacx\SimpleCurl\SimpleCurlLib('https://www.php.net/', returnTransfer: true);

/** @var \Ennacx\SimpleCurl\Entity\ResponseEntity $result */
$result = $lib->exec();

echo $result->result;         // (bool)
echo $result->responseHeader; // (string) レスポンスヘッダー
echo $result->responseBody;   // (string) レスポンスボディー
```

### POSTやPUTもお手軽に
```php
<?php
$postData = ['foo' => 1, 'bar' => 'enjoy PHP', 'baz' => null];

$lib = new \Ennacx\SimpleCurl\SimpleCurlLib('https://www.php.net/', method: CurlMethod::POST);

/** @var \Ennacx\SimpleCurl\Entity\ResponseEntity $result */
$result = $lib
    ->setPostFields($postData, jsonFlags: JSON_UNESCAPED_SLASHES)
    ->exec();

echo $result->result; // (bool)
```

他にもプロキシだったりCookieだったり認証だったり最低限必要と思われるものは用意。

### レスポンス内容も分かりやすく
```php
$lib = new \Ennacx\SimpleCurl\SimpleCurlLib('https://www.php.net/');

/** @var \Ennacx\SimpleCurl\Entity\ResponseEntity $result */
$result = $lib->exec();

// HTTPステータスコード
$statusCode = $result->http_code;
$statusCode = $result->http_status_code;

// ダウンロードサイズ
$contentLength = $result->content_length;
$contentLength = $result->download_content_length;
```

その他 ```curl_getinfo()``` で取得可能はパラメーターは網羅済。<br>
ダイナミックプロパティ形式でアクセスすれば取得出来ます。

### 並列処理も対応
```php
<?php
// 並列処理したいcURL対象を列挙します。
// MultiCurlLib使用時は内部でreturnTransferを有効にするため、わざわざ指定する必要はありません。
$sLib1 = new \Ennacx\SimpleCurl\SimpleCurlLib('https://www.php.net/');
$sLib2 = new \Ennacx\SimpleCurl\SimpleCurlLib('https://github.com/');
$sLib3 = new \Ennacx\SimpleCurl\SimpleCurlLib('https://packagist.org/');

// MultiCurlLibに適用し exec() メソッドで実行します。
$mLib = new \Ennacx\SimpleCurl\MultiCurlLib($sLib1, $sLib2, $sLib3);

/** @var array<string, \Ennacx\SimpleCurl\Entity\ResponseEntity> $multiResult */
$multiResult = $mLib->exec();

// それぞれのIDから各結果を取得出来ます。
$s1Result = $multiResult[$sLib1->getId()];
echo $s1Result->result;         // (bool)   $sLib1のcurl実行結果
echo $s1Result->responseHeader; // (string) $sLib1のレスポンスヘッダー
echo $s1Result->responseBody;   // (string) $sLib1のレスポンスボディー
```

## ライセンス
[MIT](https://en.wikipedia.org/wiki/MIT_License)

[CreativeCommons BY-SA](https://creativecommons.org/licenses/by-sa/4.0/)
