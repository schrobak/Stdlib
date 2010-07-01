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
 * @subpackage Form
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Dojo\Form;

use Zend\Loader\PrefixPathMapper as PluginLoader,
    Zend\View\ViewInterface as View;

/**
 * Dijit-enabled DisplayGroup
 *
 * @uses       \Zend\Form\DisplayGroup
 * @package    Zend_Dojo
 * @subpackage Form
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
class DisplayGroup extends \Zend\Form\DisplayGroup
{
    /**
     * Constructor
     *
     * @param  string $name
     * @param  \Zend\Loader\PrefixPathMapper $loader
     * @param  array|\Zend\Config\Config|null $options
     * @return void
     */
    public function __construct($name, PluginLoader $loader, $options = null)
    {
        parent::__construct($name, $loader, $options);
        $this->addPrefixPath('Zend\Dojo\Form\Decorator', 'Zend/Dojo/Form/Decorator');
    }

    /**
     * Set the view object
     *
     * Ensures that the view object has the dojo view helper path set.
     *
     * @param  \Zend\View\ViewInterface $view
     * @return \Zend\Dojo\Form\Element\Dijit
     */
    public function setView(View $view = null)
    {
        if (null !== $view) {
            if (false === $view->getPluginLoader('helper')->getPaths('Zend\Dojo\View\Helper')) {
                $view->addHelperPath('Zend/Dojo/View/Helper', 'Zend\Dojo\View\Helper');
            }
        }
        return parent::setView($view);
    }
}
