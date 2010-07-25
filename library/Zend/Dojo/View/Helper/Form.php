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
 * @package    Zend_Dojo
 * @subpackage View
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @namespace
 */
namespace Zend\Dojo\View\Helper;

use Zend\View\Helper\Form as FormHelper;

/**
 * Dojo Form dijit
 *
 * @uses       \Zend\Dojo\View\Helper\Dijit
 * @uses       \Zend\View\Helper\Form
 * @package    Zend_Dojo
 * @subpackage View
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Form extends Dijit
{
    /**
     * Dijit being used
     * @var string
     */
    protected $_dijit  = 'dijit.form.Form';

    /**
     * Module being used
     * @var string
     */
    protected $_module = 'dijit.form.Form';

    /**
     * @var \Zend\View\Helper\Form
     */
    protected $_helper;

    /**
     * dijit.form.Form
     *
     * @param  string $id
     * @param  null|array $attribs HTML attributes
     * @param  false|string $content
     * @return string
     */
    public function direct($id = null, $attribs = null, $content = false)
    {
        if (!is_array($attribs)) {
            $attribs = (array) $attribs;
        }
        if (array_key_exists('id', $attribs)) {
            $attribs['name'] = $id;
        } else {
            $attribs['id'] = $id;
        }

        if (false === $content) {
            $content = '';
        }

        $attribs = $this->_prepareDijit($attribs, array(), 'layout');

        return $this->getFormHelper()->direct($id, $attribs, $content);
    }

    /**
     * Get standard form helper
     *
     * @return \Zend\View\Helper\Form
     */
    public function getFormHelper()
    {
        if (null === $this->_helper) {
            $this->_helper = new FormHelper();
            $this->_helper->setView($this->view);
        }
        return $this->_helper;
    }
}
