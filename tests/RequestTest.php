<?php

namespace Njasm\Soundcloud\Tests;

use Njasm\Soundcloud\Request\Request;
use Njasm\Soundcloud\UrlBuilder\UrlBuilder;
use Njasm\Soundcloud\Resource\Resource;
use Njasm\Soundcloud\Factory\Factory;
use Njasm\Soundcloud\Request\Response;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    public $resource;
    public $urlBuilder;
    public $request;
    
    public function setUp()
    {
        $this->resource = new Resource('get', '/resolve');
        $this->urlBuilder = new UrlBuilder($this->resource);
        $this->request = new Request($this->resource, $this->urlBuilder, new Factory());
    }
    
    public function testSetOptions()
    {
        $this->request->setOptions(array(CURLOPT_VERBOSE => true));
        $this->assertArrayHasKey(CURLOPT_VERBOSE, $this->request->getOptions());
        $this->assertArrayHasKey(CURLOPT_RETURNTRANSFER, $this->request->getOptions());
    }
    
    public function testAsJsonAsXml()
    {
        $property = new ReflectionProperty("Njasm\\Soundcloud\\Request\\Request", "responseFormat");
        $property->setAccessible(true);
        
        $this->request->asJson();
        $this->assertEquals("application/json", $property->getValue($this->request));
        $this->request->asXml();
        $this->assertEquals("application/xml", $property->getValue($this->request));
    }
    
    public function testGetOptions()
    {
        $this->assertArrayHasKey(CURLOPT_HEADER, $this->request->getOptions());
        $this->assertArrayNotHasKey(CURLOPT_COOKIE, $this->request->getOptions());
    }
    
    public function testRequest()
    {
        $resource = new Resource('post', '/me', array('name' => 'John Doe'));
        $urlBuilder = new UrlBuilder($resource, '127', '0.0.1', 'http://');
        // request Factory mock
        $reqFactoryMock = $this->getMock(
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
                            "url: http://127.0.0.1/index.php\r\n\r\n{\"status\": \"ok\"}",
                            array('url' => 'http://127.0.0.1/index.php'),
                            0,
                            "No Error"
                        );
                    }
                )
            );
        $request = new Request($resource, $urlBuilder, $reqFactoryMock);
        $response = $request->exec();
        
        $this->assertInstanceOf('Njasm\\Soundcloud\\Request\\ResponseInterface', $response);
    }
}
