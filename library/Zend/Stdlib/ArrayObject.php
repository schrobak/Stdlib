<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

/**
 * If the version is less than 5.3.4, we'll use Zend\Stdlib\ArrayObject\PhpLegacyCompatibility
 * which extend sthe native PHP ArrayObject implementation. For versions greater than or equal
 * to 5.3.4, we'll use Zend\Stdlib\ArrayObject\PhpReferenceCompatibility, which corrects
 * issues with how PHP handles references inside ArrayObject.
 * 
 * class_alias is a global construct, so we can alias either one to Zend\Stdlib\ArrayObject,
 * and from this point forward, that alias will be used.
 */
if (version_compare(PHP_VERSION, '5.3.4', 'lt')
    && !class_exists('Zend\Stdlib\ArrayObject', false)
) {
    class_alias('Zend\Stdlib\ArrayObject\PhpLegacyCompatibility', 'Zend\Stdlib\ArrayObject');
} else {
    class_alias('Zend\Stdlib\ArrayObject\PhpReferenceCompatibility', 'Zend\Stdlib\ArrayObject');
}
