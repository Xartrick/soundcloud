<?php

namespace Njasm\Soundcloud\Tests;

use Njasm\Soundcloud\Resource\Resource;
use Njasm\Soundcloud\UrlBuilder\UrlBuilder;
use Njasm\Soundcloud\Factory\Factory;
use Njasm\Soundcloud\Request\Request;
use Njasm\Soundcloud\Soundcloud;
use Njasm\Soundcloud\Request\Response;
use PHPUnit\Framework\TestCase;

class SoundcloudTest extends TestCase
{
    public $soundcloud;
    
    public function setUp()
    {
        $clientID = "ClientIDHash";
        $clientSecret = "ClientSecretHash";
        $uriCallback = "http://example.com/soundcloud";
        $this->soundcloud = new Soundcloud($clientID, $clientSecret, $uriCallback);
    }
    
    public function testRequest()
    {
        // request Factory mock
        $reqFactoryMock = $this->createMock(
            "Njasm\\Soundcloud\\Factory\\Factory",
            array('make')
        );
        $reqFactoryMock->expects($this->any())
            ->method('make')
            ->with($this->equalTo('ResponseInterface'))
            ->will(
                $this->returnCallback(
                    function ($arg) {
                        return new Response(
                            "HTTP/1.1 302 Found\nurl: http://127.0.0.1/index.php\r\n\r\nDummy Response Body",
                            array('url' => 'http://127.0.0.1/index.php'),
                            0,
                            "No Error"
                        );
                    }
                )
            );
            
        // soundcloud Factory mock
        $factoryMock = $this->createMock(
            "Njasm\\Soundcloud\\Factory\\Factory",
            array('make')
        );
        $factoryMock->expects($this->any())
            ->method('make')
            ->with(
                $this->logicalOr(
                    $this->equalTo('UrlBuilderInterface'),
                    $this->equalTo('RequestInterface')
                )
            )->will(
                $this->returnCallback(
                    function ($arg) use (&$reqFactoryMock) {
                        if ($arg == 'UrlBuilderInterface') {
                            return new UrlBuilder(new Resource('get', '/index.php'), "127", "0.0.1", "http://");
                        } elseif ($arg == 'RequestInterface') {
                            return new Request(
                                new Resource('get', '/index.php'),
                                new UrlBuilder(new Resource('get', '/index.php'), "127", "0.0.1", "http://"),
                                $reqFactoryMock
                            );
                        }
                    }
                )
            );
                
        $property = $this->reflectProperty("Njasm\\Soundcloud\\Soundcloud", "factory");
        $property->setAccessible(true);
        $property->setValue($this->soundcloud, $factoryMock);
        $response = $this->soundcloud->request();

        $this->assertInstanceOf('Njasm\Soundcloud\Request\ResponseInterface', $response);
        $this->assertEquals("Dummy Response Body", $response->bodyRaw());
        // coverage, already tested inside Request class
        $this->soundcloud->request(array(CURLOPT_RETURNTRANSFER => true));
    }

    /**
     * Auth tests.
     */
    public function testGetAuthClientID()
    {
        $this->assertEquals("ClientIDHash", $this->soundcloud->getAuthClientID());
    }
    
    public function testNulledGetAuthToken()
    {
        $this->assertNull($this->soundcloud->getAuthToken());
    }
    
    public function testNulledGetAuthScope()
    {
        $this->assertNull($this->soundcloud->getAuthScope());
    }
    
    public function testNullGetExpires()
    {
        $this->assertNull($this->soundcloud->getExpires());
    }
    
    /**
     * Resources tests.
     */
    public function testGetResourceCreation()
    {
        $property = $this->reflectProperty("Njasm\\Soundcloud\\Soundcloud", "resource");
        $this->soundcloud->get('/resolve');
        $this->assertTrue($property->getValue($this->soundcloud) instanceof Resource);
        $this->assertEquals("get", $property->getValue($this->soundcloud)->getVerb());
    }

    public function testPostResourceCreation()
    {
        $property = $this->reflectProperty("Njasm\\Soundcloud\\Soundcloud", "resource");
        $this->soundcloud->post('/resolve');
        $this->assertTrue($property->getValue($this->soundcloud) instanceof Resource);
        $this->assertEquals("post", $property->getValue($this->soundcloud)->getVerb());
    }
    
    public function testPutResourceCreation()
    {
        $property = $this->reflectProperty("Njasm\\Soundcloud\\Soundcloud", "resource");
        $this->soundcloud->put('/resolve');
        $this->assertTrue($property->getValue($this->soundcloud) instanceof \Njasm\Soundcloud\Resource\ResourceInterface);
        $this->assertEquals("put", $property->getValue($this->soundcloud)->getVerb());
    }
    
    public function testDeleteResourceCreation()
    {
        $property = $this->reflectProperty("Njasm\\Soundcloud\\Soundcloud", "resource");
        $this->soundcloud->delete('/resolve');
        $this->assertTrue($property->getValue($this->soundcloud) instanceof Resource);
        $this->assertEquals("delete", $property->getValue($this->soundcloud)->getVerb());
    }
    
    public function testSetParams()
    {
        $params = array(
            'url' => 'http://www.soundcloud.com/hybrid-species'
        );
        $property = $this->reflectProperty("Njasm\\Soundcloud\\Soundcloud", "resource");
        $this->soundcloud->get('/resolve');
        $this->soundcloud->setParams($params);
        $this->assertArrayHasKey('url', $property->getValue($this->soundcloud)->getParams());
    }

    public function testNoResourceException()
    {
        $this->setExpectedException(
            '\RuntimeException',
            "No Resource found. you must call a http verb method before Njasm\Soundcloud\Soundcloud::setParams"
        );
        
        $facade = $this->soundcloud->setParams(array('url' => 'http://www.soundcloud.com/hybrid-species'));
    }
    
    public function testAsJson()
    {
        $property = $this->reflectProperty("Njasm\\SoundCloud\\Soundcloud", "responseFormat");
        $this->soundcloud->asJson();
        $this->assertEquals("json", $property->getValue($this->soundcloud));
    }
    
    public function testMergeAuthParams()
    {
        $method = $this->reflectMethod("Njasm\\Soundcloud\\Soundcloud", "mergeAuthParams");
        $params = $method->invoke($this->soundcloud, array(), false);
        $this->assertArrayHasKey("client_id", $params);
        $this->assertArrayNotHasKey("oauth_token", $params);
                
        $params = $method->invoke($this->soundcloud, array(), true);
        $this->assertArrayHasKey("client_secret", $params);
    }
    
    /**
     * Code Coverage
     */
    public function testSetResponseFormat()
    {
        $reqMock = $this->createMock(
            "Njasm\\Soundcloud\\Request\\Request",
            array('asJson'),
            array(new Resource('get', '/resolve'), new UrlBuilder(new Resource('get', '/resolve')), new Factory())
        );
        $reqMock->expects($this->any())->method('asJson');
        
        $method = $this->reflectMethod("Njasm\\Soundcloud\\Soundcloud", "setResponseFormat");

        $this->soundcloud->asXml();
        $method->invoke($this->soundcloud, $reqMock);
        $this->soundcloud->asJson();
        $method->invoke($this->soundcloud, $reqMock);
    }
    
    public function testGetCurlResponse()
    {
        $this->assertNull($this->soundcloud->getCurlResponse());
    }
    
    /**
     * Helper method for properties reflection testing.
     */
    private function reflectProperty($class, $property)
    {
        $property = new \ReflectionProperty($class, $property);
        $property->setAccessible(true);
        
        return $property;
    }
    
    private function reflectMethod($class, $method)
    {
        $method = new \ReflectionMethod($class, $method);
        $method->setAccessible(true);
        
        return $method;
    }
}
