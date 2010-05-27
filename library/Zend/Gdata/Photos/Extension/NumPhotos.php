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
 * @subpackage Photos
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @namespace
 */
namespace Zend\GData\Photos\Extension;

/**
 * Represents the gphoto:numphotos element used by the API.
 * This indicates the number of photos in an album.
 *
 * @uses       \Zend\GData\Extension\Extension
 * @uses       \Zend\GData\Photos\Photos
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Photos
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class NumPhotos extends \Zend\GData\Extension\Extension
{

    protected $_rootNamespace = 'gphoto';
    protected $_rootElement = 'numphotos';

    /**
     * Constructs a new Zend_Gdata_Photos_Extension_NumPhotos object.
     *
     * @param string $text (optional) The value to represent.
     */
    public function __construct($text = null)
    {
        $this->registerAllNamespaces(\Zend\GData\Photos\Photos::$namespaces);
        parent::__construct();
        $this->setText($text);
    }

}
