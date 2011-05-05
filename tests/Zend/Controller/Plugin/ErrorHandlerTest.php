<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace ZendTest\Controller\Plugin;
use Zend\Controller;
use Zend\Controller\Request;
use Zend\Controller\Plugin;
use Zend\Controller\Dispatcher;


/**
 * Test class for Zend_Controller_Plugin_ErrorHandler.
 * Generated by PHPUnit_Util_Skeleton on 2007-05-15 at 09:50:21.
 *
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Controller
 * @group      Zend_Controller_Plugin
 */
class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Request object
     * @var Zend_Controller_Request_HTTP
     */
    public $request;

    /**
     * Response object
     * @var Zend_Controller_Response_HTTP
     */
    public $response;

    /**
     * Error handler plugin
     * @var Zend_Controller_Plugin_ErrorHandler
     */
    public $plugin;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        Controller\Front::getInstance()->resetInstance();
        $this->request  = new Request\Http();
        $this->response = new \Zend\Controller\Response\Http();
        $this->plugin   = new Plugin\ErrorHandler();

        $this->plugin->setRequest($this->request);
        $this->plugin->setResponse($this->response);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }

    public function testSetErrorHandler()
    {
        $this->plugin->setErrorHandler(array(
            'module'     => 'myfoo',
            'controller' => 'bar',
            'action'     => 'boobaz',
        ));

        $this->assertEquals('myfoo', $this->plugin->getErrorHandlerModule());
        $this->assertEquals('bar', $this->plugin->getErrorHandlerController());
        $this->assertEquals('boobaz', $this->plugin->getErrorHandlerAction());
    }

    public function testSetErrorHandlerModule()
    {
        $this->plugin->setErrorHandlerModule('boobah');
        $this->assertEquals('boobah', $this->plugin->getErrorHandlerModule());
    }

    public function testSetErrorHandlerController()
    {
        $this->plugin->setErrorHandlerController('boobah');
        $this->assertEquals('boobah', $this->plugin->getErrorHandlerController());
    }

    public function testSetErrorHandlerAction()
    {
        $this->plugin->setErrorHandlerAction('boobah');
        $this->assertEquals('boobah', $this->plugin->getErrorHandlerAction());
    }

    public function testPostDispatchNoControllerException()
    {
        $this->response->setException(new Dispatcher\Exception('Testing controller exception'));
        $this->request->setModuleName('foo')
                      ->setControllerName('bar')
                      ->setActionName('baz');
        $this->plugin->postDispatch($this->request);

        $this->assertNotNull($this->request->getParam('error_handler'));
        $errorHandler = $this->request->getParam('error_handler');
        $this->assertTrue($errorHandler instanceof \ArrayObject);
        $this->assertEquals(Plugin\ErrorHandler::EXCEPTION_NO_CONTROLLER, $errorHandler->type);

        $this->assertEquals('error', $this->request->getActionName());
        $this->assertEquals('error', $this->request->getControllerName());
        $this->assertEquals('application', $this->request->getModuleName());
    }

    public function testPostDispatchNoActionException()
    {
        $this->response->setException(new \Zend\Controller\Action\Exception('Testing action exception', 404));
        $this->request->setModuleName('foo')
                      ->setControllerName('bar')
                      ->setActionName('baz');
        $this->plugin->postDispatch($this->request);

        $this->assertNotNull($this->request->getParam('error_handler'));
        $errorHandler = $this->request->getParam('error_handler');
        $this->assertTrue($errorHandler instanceof \ArrayObject);
        $this->assertEquals(Plugin\ErrorHandler::EXCEPTION_NO_ACTION, $errorHandler->type);

        $this->assertEquals('error', $this->request->getActionName());
        $this->assertEquals('error', $this->request->getControllerName());
        $this->assertEquals('application', $this->request->getModuleName());
    }

    public function testPostDispatchOtherException()
    {
        $this->response->setException(new \Exception('Testing other exception'));
        $this->request->setModuleName('foo')
                      ->setControllerName('bar')
                      ->setActionName('baz');
        $this->plugin->postDispatch($this->request);

        $this->assertNotNull($this->request->getParam('error_handler'));
        $errorHandler = $this->request->getParam('error_handler');
        $this->assertTrue($errorHandler instanceof \ArrayObject);
        $this->assertEquals(Plugin\ErrorHandler::EXCEPTION_OTHER, $errorHandler->type);

        $this->assertEquals('error', $this->request->getActionName());
        $this->assertEquals('error', $this->request->getControllerName());
        $this->assertEquals('application', $this->request->getModuleName());
    }

    public function testPostDispatchThrowsWhenCalledRepeatedly()
    {
        $this->response->setException(new \Exception('Testing other exception'));
        $this->request->setModuleName('foo')
                      ->setControllerName('bar')
                      ->setActionName('baz');
        $this->plugin->postDispatch($this->request);

        $this->response->setException(new Dispatcher\Exception('Another exception'));
        try {
            $this->plugin->postDispatch($this->request);
            $this->fail('Repeated calls with new exceptions should throw exceptions');
        } catch (\Exception $e) {
            $type = get_class($e);
            $this->assertEquals('Zend\Controller\Dispatcher\Exception', $type);
            $this->assertEquals('Another exception', $e->getMessage());
        }
    }

    public function testPostDispatchDoesNothingWhenCalledRepeatedlyWithoutNewExceptions()
    {
        $this->response->setException(new \Exception('Testing other exception'));
        $this->request->setModuleName('foo')
                      ->setControllerName('bar')
                      ->setActionName('baz');
        $this->plugin->postDispatch($this->request);

        try {
            $this->plugin->postDispatch($this->request);
        } catch (\Exception $e) {
            $this->fail('Repeated calls with no new exceptions should not throw exceptions');
        }
    }

    public function testPostDispatchWithoutException()
    {
        $this->request->setModuleName('foo')
                      ->setControllerName('bar')
                      ->setActionName('baz');
        $this->plugin->postDispatch($this->request);
        $this->assertEquals('baz', $this->request->getActionName());
        $this->assertEquals('bar', $this->request->getControllerName());
        $this->assertEquals('foo', $this->request->getModuleName());
    }

    public function testPostDispatchErrorRequestIsClone()
    {
        $this->response->setException(new Dispatcher\Exception('Testing controller exception'));
        $this->request->setModuleName('foo')
                      ->setControllerName('bar')
                      ->setActionName('baz');
        $this->plugin->postDispatch($this->request);

        $this->assertNotNull($this->request->getParam('error_handler'));
        $errorHandler = $this->request->getParam('error_handler');
        $this->assertTrue($errorHandler instanceof \ArrayObject);
        $this->assertTrue($errorHandler->request instanceof Request\Http);
        $this->assertNotSame($this->request, $errorHandler->request);
    }

    public function testPostDispatchQuitsWithFalseUserErrorHandlerParam()
    {
        $front = Controller\Front::getInstance();
        $front->resetInstance();
        $front->setParam('noErrorHandler', true);

        $this->response->setException(new Dispatcher\Exception('Testing controller exception'));
        $this->request->setModuleName('foo')
                      ->setControllerName('bar')
                      ->setActionName('baz');
        $this->plugin->postDispatch($this->request);

        $this->assertNull($this->request->getParam('error_handler'));
    }
}
