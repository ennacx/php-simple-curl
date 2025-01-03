# PHP Simple cURL Library

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
* composer 2.6 以上

## インストール
```
composer require ennacx/php-simple-curl
```

## 使い方
### めっちゃシンプルに
```php
<?php
$lib = new SimpleCurlLib('https://www.google.co.jp/');
$result = $lib->exec();

echo $result->result; // (bool)
```

### レスポンスデータが欲しい場合
```php
<?php
$lib = new SimpleCurlLib('https://www.google.co.jp/', returnTransfer: true);
$result = $lib->exec();

echo $result->result;         // (bool)
echo $result->responseHeader; // (string) レスポンスヘッダー
echo $result->responseBody;   // (string) レスポンスボディー
```

### POSTやPUTもお手軽に
```php
<?php
$postData = ['foo' => 1, 'bar' => 'enjoy PHP', 'baz' => null];

$lib = new SimpleCurlLib('https://www.google.co.jp/', method: CurlMethod::POST);
$result = $lib
    ->setPostFields($postData, jsonEncode: true)
    ->exec();

echo $result->result; // (bool)
```

他にもプロキシだったりCookieだったり認証だったり最低限必要と思われるものは用意。

### 並列処理も対応
```php
<?php
// 並列処理したいcURL対象を列挙
$sLib1 = new SimpleCurlLib('https://www.google.co.jp/', returnTransfer: true);
$sLib2 = new SimpleCurlLib('https://www.yahoo.co.jp/', returnTransfer: true);
$sLib3 = new SimpleCurlLib('https://www.amazon.co.jp/', returnTransfer: true);

// MultiCurlLibに適用し実行
$mLib = new MultiCurlLib($sLib1, $sLib2, $sLib3);
$multiResult = $mLib->exec();

// それぞれのIDから各結果を取得出来ます
$s1Result = $multiResult[$sLib1->getId()];
echo $s1Result->result;         // (bool)   $sLib1のcurl実行結果
echo $s1Result->responseHeader; // (string) $sLib1のレスポンスヘッダー
echo $s1Result->responseBody;   // (string) $sLib1のレスポンスボディー
```

## ライセンス
[MIT](https://en.wikipedia.org/wiki/MIT_License)

[CreativeCommons BY-SA](https://creativecommons.org/licenses/by-sa/4.0/)
