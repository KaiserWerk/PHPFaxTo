<?php

include_once 'src/FaxTo.php';
use PHPUnit\Framework\TestCase;

class FaxToTest extends TestCase
{
    private $endpoint = 'https://fax.to/api/v1/%mode%?api_key=%apikey%';
    private $apikey = '62c22bf0-0b39-11e7-92c9-c516d648dd0c';
    private $http;

    public function setUp()
    {
        $this->endpoint = str_replace('%apikey%', $this->apikey, $this->endpoint);
    }

    public function tearDown() {
        $this->http = null;
    }

    public function testGetCashBalance()
    {
        $this->endpoint = str_replace('%mode%', 'balance', $this->endpoint);
        $this->http = new GuzzleHttp\Client(
            ['base_uri' => $this->endpoint]
        );
        $response = $this->http->request('GET', '');

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody());
        $this->assertInternalType('float', $data->balance);
    }

    public function testGetFaxCost()
    {
        // Create a stub for the SomeClass class.
        $stub = $this->createMock(FaxTo::class);

        // Configure the stub.
        #$stub
        #    ->getFaxCost('+49123456789', 268644)
        #    ->willReturn(0.1);

        // Calling $stub->doSomething() will now return
        // 'foo'.
        $this->assertEquals(0.1, $stub->getFaxCost('+49123456789', 268644));
    }
}