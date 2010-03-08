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
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


PHPUnit_Util_Filter::addFileToFilter(__FILE__);


/**
 * Mock file for testbed
 *
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class FooController extends Zend_Controller_Action
{

    /**
     * Test Function for preDispatch
     *
     * @return void
     */
    public function preDispatch()
    {
        $this->_response->appendBody("preDispatch called\n");
    }

    /**
     * Test Function for postDispatch
     *
     * @return void
     */
    public function postDispatch()
    {
        $this->_response->appendBody("postDispatch called\n");
    }

    /**
     * Test Function for barAction
     *
     * @return void
     */
    public function barAction()
    {
        $this->_response->appendBody("Bar action called\n");
    }

}
