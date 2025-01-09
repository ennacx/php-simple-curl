<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

enum SSLVersion: string {

    /** 最適なバージョンを探す */
    case DEFAULT = 'DEFAULT';

//    case TLSv1 = 'TLSv1';

    /** TLSv1.0以上 */
    case TLSv1_0 = 'TLSv1_0';

    /** TLSv1.1以上 */
    case TLSv1_1 = 'TLSv1_1';

    /** TLSv1.2以上 */
    case TLSv1_2 = 'TLSv1_2';

    /** TLSv1.0以上 */
    case TLSv1_3 = 'TLSv1_3';

    /** SSLv2以上 */
    case SSLv2 = 'SSLv2';

    /** SSLv3以上 */
    case SSLv3 = 'SSLv3';

    /** 最適なバージョンを上限として探す */
    case MAX_DEFAULT = 'MAX_DEFAULT';

//    case MAX_NONE = 'MAX_NONE';

    /** TLSv1.0以下 */
    case MAX_TLSv1_0 = 'MAX_TLSv1_0';

    /** TLSv1.1以下 */
    case MAX_TLSv1_1 = 'MAX_TLSv1_1';

    /** TLSv1.2以下 */
    case MAX_TLSv1_2 = 'MAX_TLSv1_2';

    /** TLSv1.3以下 */
    case MAX_TLSv1_3 = 'MAX_TLSv1_3';
}