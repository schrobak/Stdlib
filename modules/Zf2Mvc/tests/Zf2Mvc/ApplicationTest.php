<?php

namespace Zf2Mvc;

use PHPUnit_Framework_TestCase as TestCase,
    stdClass,
    Zend\Di\DependencyInjector,
    Zend\Di\ServiceLocator,
    Zend\Http\Request,
    Zend\Http\Response;

class ApplicationTest extends TestCase
{
    public function testEventManagerIsLazyLoaded()
    {
        $app = new Application();
        $events = $app->events();
        $this->assertInstanceOf('Zend\EventManager\EventCollection', $events);
        $this->assertInstanceOf('Zend\EventManager\EventManager', $events);
    }

    public static function invalidLocators()
    {
        return array(
            array(null),
            array(0),
            array(1),
            array(1.0),
            array(''),
            array('bad'),
            array(array()),
            array(array('foo')),
            array(array('foo' => 'bar')),
            array(new stdClass),
        );
    }

    /**
     * @dataProvider invalidLocators
     */
    public function testLocatorMutatorShouldRaiseExceptionOnInvalidInput($locator)
    {
        $app = new Application();

        $this->setExpectedException('InvalidArgumentException');
        $app->setLocator($locator);
    }

    public function testLocatorIsNullByDefault()
    {
        $app = new Application();
        $this->assertNull($app->getLocator());
    }

    public static function validLocators()
    {
        return array(
            array(new ServiceLocator()),
            array(new DependencyInjector()),
            array(new TestAsset\Locator()),
        );
    }

    /**
     * @dataProvider validLocators
     */
    public function testCanRetrieveLocatorOnceSet($locator)
    {
        $app     = new Application();
        $app->setLocator($locator);
        $this->assertSame($locator, $app->getLocator());
    }

    public function testRouterIsLazyLoaded()
    {
        $app    = new Application();
        $router = $app->getRouter();
        $this->assertInstanceOf('Zf2Mvc\Router\RouteStack', $router);
    }

    public function testRouterMayBeInjected()
    {
        $app    = new Application();
        $router = new Router\SimpleRouteStack();
        $app->setRouter($router);
        $this->assertSame($router, $app->getRouter());
    }

    public function testRequestIsLazyLoaded()
    {
        $app     = new Application();
        $request = $app->getRequest();
        $this->assertInstanceOf('Zend\Http\Request', $request);
    }

    public function testRequestMayBeInjected()
    {
        $app     = new Application();
        $request = new Request();
        $app->setRequest($request);
        $this->assertSame($request, $app->getRequest());
    }

    public function testResponseIsLazyLoaded()
    {
        $app      = new Application();
        $response = $app->getResponse();
        $this->assertInstanceOf('Zend\Http\Response', $response);
    }

    public function testResponseMayBeInjected()
    {
        $app      = new Application();
        $response = new Response();
        $app->setResponse($response);
        $this->assertSame($response, $app->getResponse());
    }

    public function testRunRaisesAnExceptionIfNoLocatorIsAvailable()
    {
        $app = new Application();

        $this->setExpectedException('RuntimeException');
        $app->run();
    }

    public function testRunExecutesNormallyWhenLocatorPresent()
    {
        $locator = new TestAsset\Locator;
        $app     = new Application();
        $app->setLocator($locator);
        $app->run();
    }
}
