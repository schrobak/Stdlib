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
 */

namespace Zend\Dojo\View\Helper;

/**
 * Dojo StackContainer dijit
 *
 * @package    Zend_Dojo
 * @subpackage View
  */
class StackContainer extends DijitContainer
{
    /**
     * Dijit being used
     * @var string
     */
    protected $_dijit  = 'dijit.layout.StackContainer';

    /**
     * Dojo module to use
     * @var string
     */
    protected $_module = 'dijit.layout.StackContainer';

    /**
     * dijit.layout.StackContainer
     *
     * @param  string $id
     * @param  string $content
     * @param  array $params  Parameters to use for dijit creation
     * @param  array $attribs HTML attributes
     * @return string
     */
    public function __invoke($id = null, $content = '', array $params = array(), array $attribs = array())
    {
        if (0 === func_num_args()) {
            return $this;
        }

        return $this->_createLayoutContainer($id, $content, $params, $attribs);
    }
}
