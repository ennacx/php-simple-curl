<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Static;

final class HeaderUtils {

    /**
     * 既にヘッダー配列に登録されているかをチェックする。
     *
     * @param  array  $headers ヘッダー配列
     * @param  string $name    チェック対象
     * @return boolean
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
}
