<?php
declare(strict_types=1);

/**
 * @internal
 */
namespace Ennacx\SimpleCurl\Helper\Internal;

use Ennacx\SimpleCurl\Exception\RequestBodyException;
use Ennacx\SimpleCurl\Exception\SimpleCurlException;
use Random\RandomException;
use Stringable;

/**
 * @internal
 */
final class Utils {

    /**
     * キャメルケース `CamelCaseString` をスネークケース `snake_case_string` に変換する。
     *
     * @param string|null $str Target string
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
     * @param string|null $str     Target string
     * @param boolean     $toLower True: lowerCamel / False: UpperCamel
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
     */
    public static function trimLower(string $v, bool $spaceAllRemove = false): string {

        $temp = strtolower($v);

        return ($spaceAllRemove) ? str_replace(' ', '', $temp) : trim($temp);
    }

    /**
     * 与えられた引数を文字列に変換する。
     *
     * @return string|null|false 与えられた引数が`null`の場合は`null`を返し、文字列に変換できない場合は`false`を返す
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
     * ファイルが存在し、読み取り可能な通常ファイルであることを検証する。
     *
     * @param  string               $path 対象ファイルパス
     * @throws RequestBodyException       ファイルが存在しない・読取不可・通常ファイルではない場合
     */
    public static function fileCheck(string $path): void {

        if(!file_exists($path) || !is_readable($path)){
            throw new RequestBodyException('Target file does not exist or is not readable.');
        } else if(!is_file($path)){
            throw new RequestBodyException('Target path is not a file.');
        }
    }

    /**
     * UUIDv4を生成する。
     *
     * @return string RFC4122 UUID
     * @throws SimpleCurlException
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
            throw new SimpleCurlException('UUIDv4 generation failed.', previous: $e);
        }
    }

    /**
     * UUIDを簡易的に検証する。
     *
     * @param  string   $uuid    UUID
     * @param  int|null $version 1~9, nullは指定無し扱い
     */
    public static function isUuid(string $uuid, ?int $version = null): bool {

        if($version !== null && ($version <= 0 || $version >= 10)){
            $version = null;
        }

        $pattern = sprintf(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-%s[0-9a-f]{%d}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $version ?? '',
            ($version !== null) ? 3 : 4
        );

        return (preg_match($pattern, $uuid) === 1);
    }
}
