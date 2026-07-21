<?php
declare(strict_types=1);

/**
 * @internal
 */
namespace Ennacx\SimpleCurl\Helper\Internal;

use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;

/**
 * @internal
 */
final class HeaderUtils {

    /**
     * 既にヘッダー配列に登録されているかをチェックする。
     *
     * @param array<string, string> $headers ヘッダー配列
     * @param string                $name    チェック対象
     */
    public static function has(array $headers, string $name): bool {

        $needle = strtolower($name);

        foreach($headers as $key => $value){
            if(is_string($key) && strtolower($key) === $needle){
                return true;
            }

            if(is_string($value) && self::startsWithHeaderName($value, $needle)){
                return true;
            }
        }

        return false;
    }

    /**
     * ヘッダー配列に登録されている要素を削除する。
     *
     * @param array  $headers ヘッダー配列
     * @param string $name    削除対象
     */
    public static function remove(array &$headers, string $name): void {

        $needle = strtolower($name);

        foreach($headers as $key => $value){
            if(is_string($key) && strtolower($key) === $needle){
                unset($headers[$key]);

                continue;
            }

            if(is_string($value) && self::startsWithHeaderName($value, $needle)){
                unset($headers[$key]);
            }
        }
    }

    /**
     * ヘッダー値として利用できる文字列か検証する。
     *
     * @throws InvalidConfigurationException
     */
    public static function assertHeaderValue(string $name, string $value): void {

        $trimmedValue = trim($value);

        if($trimmedValue === ''){
            throw new InvalidConfigurationException(sprintf('%s header value must not be empty.', $name));
        }

        if(str_contains($value, "\r") || str_contains($value, "\n")){
            throw new InvalidConfigurationException(sprintf('%s header value must not contain line breaks.', $name));
        }

        if(self::startsWithHeaderName($trimmedValue, $name)){
            throw new InvalidConfigurationException(sprintf('%s header value must not include the header name.', $name));
        }
    }

    /**
     * ヘッダー行が指定したヘッダー名から始まるか検証する。
     */
    private static function startsWithHeaderName(string $value, string $name): bool {

        return (preg_match(sprintf('/^%s\s*:/i', preg_quote($name, '/')), $value) === 1);
    }
}
