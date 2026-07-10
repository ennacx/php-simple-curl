<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Static;

use Random\RandomException;
use RuntimeException;
use Stringable;

class Utils {

    /**
     * キャメルケース `CamelCaseString` をスネークケース `snake_case_string` に変換する。
     *
     * @param  string|null $str Target string
     * @return string|null
     */
    public static function snakize(?string $str): ?string {

        if($str === null){
            return null;
        }

        $snakeStr = strtolower(preg_replace('/[A-Z]/', "_$0", $str));

        return ltrim($snakeStr, '_');
    }

    /**
     * スネークケース `snake_case_string` をキャメルケース `CamelCaseString (camelCaseString)` に変換する。
     *
     * @param  string|null $str     Target string
     * @param  boolean     $toLower True: lowerCamel / False: UpperCamel
     * @return string|null
     */
    public static function camelize(?string $str, bool $toLower = true): ?string {

        if($str === null){
            return null;
        }

        $camelStr = preg_replace_callback('/(^|_)(.)/', fn(array $v): string => ucfirst($v[2]), $str);

        return ($toLower) ? lcfirst($camelStr) : $camelStr;
    }

    /**
     * 小文字にして両端の空白を除去
     *
     * @param  string  $v              Target string
     * @param  boolean $spaceAllRemove True: Remove all spaces / False: Remove both ends spaces
     * @return string
     */
    public static function trimLower(string $v, bool $spaceAllRemove = false): string {

        $temp = strtolower($v);

        return ($spaceAllRemove) ? str_replace(' ', '', $temp) : trim($temp);
    }

    /**
     * 与えられた引数を文字列に変換する。
     *
     * @param  mixed             $value
     * @return string|null|false        与えられた引数が`null`の場合は`null`を返し、文字列に変換できない場合は`false`を返す
     */
    public static function toString(mixed $value): string|null|false {

        if($value === null){
            return null;
        } else if(is_string($value) || is_numeric($value)){
            return trim((string)$value);
        } else if($value instanceof Stringable){
            return trim($value->__toString());
        }

        return false;
    }

    /**
     * UUIDv4を生成する。
     *
     * @return string RFC4122 UUID
     * @throws RuntimeException
     * @see    https://www.ietf.org/rfc/rfc4122.txt
     */
    public static function uuid_v4(): string {

        try{
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                // 32 bits for "time_low"
                random_int(0, 0xFFFF),
                random_int(0, 0xFFFF),
                // 16 bits for "time_mid"
                random_int(0, 0xFFFF),
                // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
                random_int(0, 0x0FFF) | 0x4000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                random_int(0, 0x3FFF) | 0x8000,
                // 48 bits for "node"
                random_int(0, 0xFFFF),
                random_int(0, 0xFFFF),
                random_int(0, 0xFFFF)
            );
        } catch(RandomException $e){
            throw new RuntimeException($e->getMessage(), previous: $e);
        }
    }
}