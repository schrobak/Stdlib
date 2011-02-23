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
 * @package    Zend_Application
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace ZendTest\Application\Resource;

use Zend\Loader\Autoloader,
    Zend\Application\Resource\Layout as LayoutResource,
    Zend\Application,
    Zend\Controller\Front as FrontController;

/**
 * @category   Zend
 * @package    Zend_Application
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Application
 */
class LayoutTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->application = new Application\Application('testing');

        $this->bootstrap = new Application\Bootstrap($this->application);

        FrontController::getInstance()->resetInstance();
    }

    public function tearDown()
    {
    }

    public function testInitializationInitializesLayoutObject()
    {
        $resource = new LayoutResource(array());
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
        $this->assertTrue($resource->getLayout() instanceof \Zend\Layout\Layout);
    }

    public function testInitializationReturnsLayoutObject()
    {
        $resource = new LayoutResource(array());
        $resource->setBootstrap($this->bootstrap);
        $test = $resource->init();
        $this->assertTrue($test instanceof \Zend\Layout\Layout);
    }

    public function testOptionsPassedToResourceAreUsedToSetLayoutState()
    {
        $options = array(
            'layout'     => 'foo.phtml',
            'layoutPath' => __DIR__,
        );

        $resource = new LayoutResource($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
        $layout   = $resource->getLayout();
        $test     = array(
            'layout'     => $layout->getLayout(),
            'layoutPath' => $layout->getLayoutPath(),
        );
        $this->assertEquals($options, $test);
    }
}
