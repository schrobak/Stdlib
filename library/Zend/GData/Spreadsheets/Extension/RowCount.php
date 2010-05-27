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
 * @package    Zend_Gdata
 * @subpackage Spreadsheets
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @namespace
 */
namespace Zend\GData\Spreadsheets\Extension;

/**
 * Concrete class for working with RowCount elements.
 *
 * @uses       \Zend\GData\Entry
 * @uses       \Zend\GData\Extension\Extension
 * @uses       \Zend\GData\Spreadsheets\Spreadsheets
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Spreadsheets
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class RowCount extends \Zend\GData\Extension\Extension
{

    protected $_rootElement = 'rowCount';
    protected $_rootNamespace = 'gs';

    /**
     * Constructs a new Zend_Gdata_Spreadsheets_Extension_RowCount object.
     * @param string $text (optional) The text content of the element.
     */
    public function __construct($text = null)
    {
        $this->registerAllNamespaces(\Zend\GData\Spreadsheets\Spreadsheets::$namespaces);
        parent::__construct();
        $this->_text = $text;
    }

}
