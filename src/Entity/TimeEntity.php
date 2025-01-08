<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

/**
 * cURLの結果から時間に関する部分だけをまとめたエンティティー
 */
class TimeEntity extends AbstEntity {

    /** @var float|null 接続開始からレスポンスボディのダウンロードまで含めた、FIN受信までのTCPコネクション終了までにかかった時間 (秒) */
    public ?float $total = null;
    /** @var int|null 接続開始からレスポンスボディのダウンロードまで含めた、FIN受信までのTCPコネクション終了までにかかった時間 (マイクロ秒) */
    public ?int $total_us = null;

    /** @var float|null DNSでの名前解決が完了するまでにかかった時間 (秒) */
    public ?float $nsLookup = null;
    /** @var int|null DNSでの名前解決が完了するまでにかかった時間 (マイクロ秒) */
    public ?int $nsLookup_us = null;

    /** @var float|null TLSのハンドシェイクが完了するまでにかかったオーバーヘッド時間 (秒) */
    public ?float $appConnect = null;
    /** @var int|null TLSのハンドシェイクが完了するまでにかかったオーバーヘッド時間 (マイクロ秒) */
    public ?int $appConnect_us = null;

    /** @var float|null TCPの3-wayハンドシェイクにおいてクライアント側が3ステップ目のACKを送信するまでにかかった時間 (秒) */
    public ?float $connect = null;
    /** @var int|null TCPの3-wayハンドシェイクにおいてクライアント側が3ステップ目のACKを送信するまでにかかった時間 (マイクロ秒) */
    public ?int $connect_us = null;

    /** @var float|null クライアント側から最初のバイトが転送される (データ転送開始) までにかかった時間 (秒) */
    public ?float $preTransfer = null;
    /** @var int|null クライアント側から最初のバイトが転送される (データ転送開始) までにかかった時間 (マイクロ秒) */
    public ?int $preTransfer_us = null;

    /** @var float|null サーバー側からレスポンスとして最初のTTFB(time to first byte)を受け取るまでにかかった時間 (秒) */
    public ?float $startTransfer = null;
    /** @var int|null サーバー側からレスポンスとして最初のTTFB(time to first byte)を受け取るまでにかかった時間 (マイクロ秒) */
    public ?int $startTransfer_us = null;

    /** @var float|null すべてのリダイレクトステップにかかった時間 (秒) */
    public ?float $redirect = null;
    /** @var int|null すべてのリダイレクトステップにかかった時間 (マイクロ秒) */
    public ?int $redirect_us = null;
}