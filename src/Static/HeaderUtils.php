<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Static;

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

            if(is_string($value) && str_starts_with(strtolower($value), "{$needle}:")){
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

            if(is_string($value) && str_starts_with(strtolower($value), "{$needle}:")){
                unset($headers[$key]);
            }
        }
    }
}
