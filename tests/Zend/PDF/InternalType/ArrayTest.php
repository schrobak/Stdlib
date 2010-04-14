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
 * @package    Zend_PDF
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @namespace
 */
namespace ZendTest\PDF\InternalType;
use Zend\PDF\InternalType;
use Zend\PDF;

/**
 * \Zend\PDF\InternalType\ArrayObject
 */

/**
 * PHPUnit Test Case
 */

/**
 * @category   Zend
 * @package    Zend_PDF
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_PDF
 */
class ArrayTest extends \PHPUnit_Framework_TestCase
{
    public function testPDFArray1()
    {
        $arrayObj = new InternalType\ArrayObject();
        $this->assertTrue($arrayObj instanceof InternalType\ArrayObject);
    }

    public function testPDFArray2()
    {
        $srcArray = array();
        $srcArray[] = new InternalType\BooleanObject(false);
        $srcArray[] = new InternalType\NumericObject(100.426);
        $srcArray[] = new InternalType\NameObject('MyName');
        $srcArray[] = new InternalType\StringObject('some text');
        $srcArray[] = new InternalType\BinaryStringObject('some text');

        $arrayObj = new InternalType\ArrayObject($srcArray);
        $this->assertTrue($arrayObj instanceof InternalType\ArrayObject);
    }

    public function testPDFArrayBadInput1()
    {
        try {
            $arrayObj = new InternalType\ArrayObject(346);
        } catch (PDF\Exception $e) {
            $this->assertContains('must be an array', $e->getMessage());
            return;
        }
        $this->fail('Expected \Zend\PDF\Exception to be thrown');
    }

    public function testPDFArrayBadInput2()
    {
        try {
            $srcArray = array();
            $srcArray[] = new InternalType\BooleanObject(false);
            $srcArray[] = new InternalType\NumericObject(100.426);
            $srcArray[] = new InternalType\NameObject('MyName');
            $srcArray[] = new InternalType\StringObject('some text');
            $srcArray[] = new InternalType\BinaryStringObject('some text');
            $srcArray[] = 24;
            $arrayObj = new InternalType\ArrayObject($srcArray);
        } catch (PDF\Exception $e) {
            $this->assertContains('must be \Zend\PDF\InternalType\AbstractTypeObject', $e->getMessage());
            return;
        }
        $this->fail('No exception thrown.');
    }

    public function testGetType()
    {
        $arrayObj = new InternalType\ArrayObject();
        $this->assertEquals($arrayObj->getType(), InternalType\AbstractTypeObject::TYPE_ARRAY);
    }

    public function testToString()
    {
        $srcArray = array();
        $srcArray[] = new InternalType\BooleanObject(false);
        $srcArray[] = new InternalType\NumericObject(100.426);
        $srcArray[] = new InternalType\NameObject('MyName');
        $srcArray[] = new InternalType\StringObject('some text');
        $arrayObj = new InternalType\ArrayObject($srcArray);
        $this->assertEquals($arrayObj->toString(), '[false 100.426 /MyName (some text) ]');
    }

    /**
     * @todo \Zend\PDF\InternalType\ArrayObject::add() does not exist
     */
    /*
    public function testAdd()
    {
        $arrayObj = new \Zend\PDF\InternalType\ArrayObject($srcArray);
        $arrayObj->add(new \Zend\PDF\InternalType\BooleanObject(false));
        $arrayObj->add(new \Zend\PDF\InternalType\NumericObject(100.426));
        $arrayObj->add(new \Zend\PDF\InternalType\NameObject('MyName'));
        $arrayObj->add(new \Zend\PDF\InternalType\StringObject('some text'));
        $this->assertEquals($arrayObj->toString(), '[false 100.426 /MyName (some text) ]' );
    }
    //*/

    /**
     * @todo \Zend\PDF\InternalType\ArrayObject::add() does not exist
     */
    /*
    public function testAddBadArgument()
    {
        try {
            $arrayObj = new ZPDFPDFArray();
            $arrayObj->add(100.426);
        } catch (Z\end\PDF\Exception $e) {
            $this->assertContains('must be \Zend\PDF\InternalType', $e->getMessage());
            return;
        }
        $this->fail('Expected \Zend\PDF\Exception to be thrown');
    }
    //*/
}
