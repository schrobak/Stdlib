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
 * @subpackage Books
 */

namespace Zend\GData\Books\Extension;

/**
 * Describes an info link
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Books
 */
class InfoLink extends
    BooksLink
{

    /**
     * Constructor for Zend_Gdata_Books_Extension_InfoLink which
     * Describes an info link
     *
     * @param string|null $href Linked resource URI
     * @param string|null $rel Forward relationship
     * @param string|null $type Resource MIME type
     * @param string|null $hrefLang Resource language
     * @param string|null $title Human-readable resource title
     * @param string|null $length Resource length in octets
     */
    public function __construct($href = null, $rel = null, $type = null,
            $hrefLang = null, $title = null, $length = null)
    {
        $this->registerAllNamespaces(\Zend\GData\Books::$namespaces);
        parent::__construct($href, $rel, $type, $hrefLang, $title, $length);
    }

}
