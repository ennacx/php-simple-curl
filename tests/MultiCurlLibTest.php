<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test;

use Ennacx\SimpleCurl\Entity\ResponseEntity;
use Ennacx\SimpleCurl\Enum\HttpStatusCode;
use Ennacx\SimpleCurl\MultiCurlLib;
use Ennacx\SimpleCurl\SimpleCurlLib;
use PHPUnit\Framework\TestCase;

/**
 * MultiCurlLib Test
 */
class MultiCurlLibTest extends TestCase {

    private const TEST_USE_URL1 = 'https://www.google.co.jp';
    private const TEST_USE_URL2 = 'https://www.yahoo.co.jp';
    private const TEST_USE_URL3 = 'https://php.net';
    private const TEST_USE_URL4 = 'https://cakephp.org';

    private MultiCurlLib $lib;

    public function setUp(): void {
        parent::setUp();

        $this->lib = new MultiCurlLib();
    }

    public function testConstruction(){

        $this->lib = new MultiCurlLib(
            new SimpleCurlLib(self::TEST_USE_URL1),
            new SimpleCurlLib(self::TEST_USE_URL2),
            new SimpleCurlLib(self::TEST_USE_URL3),
            new SimpleCurlLib(self::TEST_USE_URL4)
        );

        $this->assertEquals(4, count($this->lib->getChannelIds()));
    }

    public function testAddChannel(){

        $sc1 = new SimpleCurlLib(self::TEST_USE_URL1);
        $sc2 = new SimpleCurlLib(self::TEST_USE_URL2);
        $sc3 = new SimpleCurlLib(self::TEST_USE_URL3);
        $sc4 = new SimpleCurlLib(self::TEST_USE_URL4);

        $this->lib
            ->addChannel($sc1)
            ->addChannel($sc2)
            ->addChannel($sc3)
            ->addChannel($sc4);

        $this->assertEquals(4, count($this->lib->getChannelIds()));
    }

    public function testSuccessfully(){

        $sc1 = new SimpleCurlLib(self::TEST_USE_URL1);
        $sc2 = new SimpleCurlLib(self::TEST_USE_URL2);
        $sc3 = new SimpleCurlLib(self::TEST_USE_URL3);
        $sc4 = new SimpleCurlLib(self::TEST_USE_URL4);

        $this->lib
            ->addChannel($sc1)
            ->addChannel($sc2)
            ->addChannel($sc3)
            ->addChannel($sc4);

        $result = array_map(fn(ResponseEntity $v) => $v->http_status_code, $this->lib->exec());

        $this->assertTrue(empty(array_filter($result, fn($v) => ($v !== HttpStatusCode::OK->value && $v !== HttpStatusCode::MOVED_PERMANENTLY->value))));
    }
}