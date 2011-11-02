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
 * @package    Zend_Config
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace ZendTest\Config\Reader;

use \PHPUnit_Framework_TestCase as TestCase,
    \Zend\Config\Reader\Reader;

/**
 * @category   Zend
 * @package    Zend_Config
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Config
 */
abstract class AbstractReaderTestCase extends TestCase
{
    /**
     * @var Reader
     */
    protected $reader;
    
    /**
     * Get test asset name for current test case.
     * 
     * @return string
     */
    abstract protected function getTestAssetPath($name);
    
    public function testInclude()
    {
        $config = $this->reader->readFile($this->getTestAssetPath('include-base'));
        $this->assertEquals('foo', $config->base->foo);
    }
    
    public function testConstants()
    {
        if (!defined('ZEND_CONFIG_TEST_CONSTANT')) {
            define('ZEND_CONFIG_TEST_CONSTANT', 'test');
        }

        $config = $this->reader->readFile($this->getTestAssetPath('constants'));

        $this->assertEquals('foo-test-bar-test', $config->base->foo);
        $this->assertEquals('ZEND_CONFIG_TEST_CONSTANT', $config->base->bar->const->name);
    }

    public function testNonExistentConstant()
    {
        $this->setExpectedException('Zend\Config\Exception\RuntimeException', 'Constant with name "ZEND_CONFIG_TEST_NON_EXISTENT_CONSTANT" was not defined');
        $config = $this->reader->readFile($this->getTestAssetPath('non-existent-constant'));
    }
}
