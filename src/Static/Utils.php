<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Static;

use Random\RandomException;
use RuntimeException;

class Utils {

    /**
     * キャメルケースをスネークケースに変換
     *  ex.) camelCaseString → camel_case_string
     *
     * @param  string|null $str
     * @return string|null
     */
    public static function snakize(?string $str): ?string {

        if($str === null){
            return null;
        }

        // 正規表現
        $snakeStr = strtolower(preg_replace('/[A-Z]/', "_$0", $str));

        return ltrim($snakeStr, '_');
    }

    /**
     * スネークケースをキャメルケースに変換
     *  (ex.) snake_case_string → "snakeCaseString" or "SnakeCaseString"
     *
     * @param  string|null  $str     対象文字列
     * @param  boolean      $isLower キャメルタイプ (true: lowerCamel, false: UpperCamel / default: true)
     * @return string|null
     */
    public static function camelize(?string $str, bool $isLower = true): ?string {

        if($str === null){
            return null;
        }

        $camelStr = preg_replace_callback('/(^|_)(.)/', fn(array $v): string => ucfirst($v[2]), $str);

        // lowerCamelの場合は頭文字を小文字にして返却
        return ($isLower) ? lcfirst($camelStr) : $camelStr;
    }

    /**
     * UUID生成
     *
     * @see    https://www.ietf.org/rfc/rfc4122.txt
     * @return string RFC 4122 UUID
     * @throws RuntimeException
     */
    public static function generateUUID(): string {

        try{
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                // 32 bits for "time_low"
                random_int(0, 65535),
                random_int(0, 65535),
                // 16 bits for "time_mid"
                random_int(0, 65535),
                // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
                random_int(0, 4095) | 0x4000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                random_int(0, 0x3fff) | 0x8000,
                // 48 bits for "node"
                random_int(0, 65535),
                random_int(0, 65535),
                random_int(0, 65535)
            );
        } catch(RandomException $e){
            throw new RuntimeException($e->getMessage());
        }
    }
}