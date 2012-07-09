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
 * @package    Zend_GData_App
 * @subpackage UnitTests
 */

namespace ZendTest\GData\App;
use Zend\GData\App;

/**
 * @category   Zend
 * @package    Zend_GData_App
 * @subpackage UnitTests
 * @group      Zend_GData
 * @group      Zend_GData_App
 */
class CaptchaRequiredExceptionTest extends \PHPUnit_Framework_TestCase
{

    public function setUp() {
        $this->exampleException = new App\CaptchaRequiredException('testtoken', 'Captcha?ctoken=testtoken');
    }

    public function testExceptionContainsValidInformation() {
        $this->assertEquals('testtoken', $this->exampleException->getCaptchaToken());
        $this->assertEquals('https://www.google.com/accounts/Captcha?ctoken=testtoken', $this->exampleException->getCaptchaUrl());
    }

    public function testExceptionIsThrowable() {
        $caught = false;
        try {
            throw $this->exampleException;
        }
        catch(App\CaptchaRequiredException $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

}
