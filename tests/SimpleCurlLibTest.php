<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test;

use Ennacx\SimpleCurl\Enum\HttpStatusCode;
use Ennacx\SimpleCurl\SimpleCurlLib;
use PHPUnit\Framework\TestCase;

/**
 * SimpleCurlLib Test
 */
class SimpleCurlLibTest extends TestCase {

    private const TEST_USE_URL = 'https://www.google.co.jp';

    private SimpleCurlLib $lib;

    public function setUp(): void {
        parent::setUp();

        $this->lib = new SimpleCurlLib();
    }

    public function testSetUrl(){

        $this->lib->setUrl(self::TEST_USE_URL);

        $this->assertEquals(self::TEST_USE_URL, $this->lib->getUrl());
    }

    public function testConnection(){

        $this->lib->setUrl(self::TEST_USE_URL);
        $entity = $this->lib->exec();

        $this->assertEquals(HttpStatusCode::OK->value, $entity->http_status_code);
    }

    public function testReturnTransfer(){

        $this->lib->setUrl(self::TEST_USE_URL);
        $this->lib->setReturnTransfer(true, true);

        $result = $this->lib->exec();

        $this->assertTrue((!empty($result->responseBody)));
    }

    public function testNoHeader(){

        $this->lib->setUrl(self::TEST_USE_URL);
        $this->lib->setReturnTransfer(true, false);

        $result = $this->lib->exec();

        $this->assertNull($result->responseHeader);
    }
}