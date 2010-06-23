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
 * @package    Zend_Application
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

namespace ZendTest\Application;

use Zend\Loader\Autoloader,
    Zend\Loader\ResourceAutoloader,
    Zend\Loader\PluginLoader\PluginLoader,
    Zend\Registry,
    Zend\Application,
    Zend\Application\Resource\AbstractResource;

/**
 * @category   Zend
 * @package    Zend_Application
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Application
 */
class AbstractBootstrapTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // Store original autoloaders
        $this->loaders = spl_autoload_functions();
        if (!is_array($this->loaders)) {
            // spl_autoload_functions does not return empty array when no
            // autoloaders registered...
            $this->loaders = array();
        }

        Autoloader::resetInstance();
        $this->autoloader = Autoloader::getInstance();

        $this->application = new Application\Application('testing');
        $this->error = false;
    }

    public function tearDown()
    {
        // Restore original autoloaders
        $loaders = spl_autoload_functions();
        foreach ($loaders as $loader) {
            spl_autoload_unregister($loader);
        }

        foreach ($this->loaders as $loader) {
            spl_autoload_register($loader);
        }

        // Reset autoloader instance so it doesn't affect other tests
        Autoloader::resetInstance();
    }

    public function handleError($errno, $errstr)
    {
        $this->error = $errstr;
        return true;
    }

    public function testConstructorShouldPopulateApplication()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $this->assertSame($this->application, $bootstrap->getApplication());
    }

    public function testConstructorShouldPopulateOptionsFromApplicationObject()
    {
        $options = array(
            'foo' => 'bar',
            'bar' => 'baz',
        );
        $this->application->setOptions($options);
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $this->assertSame($options, $bootstrap->getOptions());
    }

    public function testConstructorShouldAllowPassingAnotherBootstrapObject()
    {
        $bootstrap1 = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap2 = new TestAssett\ZfAppBootstrap($bootstrap1);
        $this->assertSame($bootstrap1, $bootstrap2->getApplication());
    }

    public function testConstructorShouldRaiseExceptionForInvalidApplicationArgument()
    {
        $this->setExpectedException('Zend\\Application\\BootstrapException');
        $bootstrap = new TestAssett\ZfAppBootstrap(new \stdClass);
    }

    public function testSettingOptionsShouldProxyToInternalSetters()
    {
        $options = array(
            'arbitrary' => 'foo',
        );
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->setOptions($options);
        $this->assertEquals('foo', $bootstrap->getArbitrary());
    }

    /**
     * @group ZF-6459
     */
    public function testCallingSetOptionsMultipleTimesShouldMergeOptionsRecursively()
    {
        $options = array(
            'deep' => array(
                'foo' => 'bar',
                'bar' => 'baz',
            ),
        );
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->setOptions($options);
        $options2 = array(
            'deep' => array(
                'bar' => 'bat',
                'baz' => 'foo',
            ),
        );
        $bootstrap->setOptions($options2);
        $expected = $bootstrap->mergeOptions($options, $options2);
        $test     = $bootstrap->getOptions();
        $this->assertEquals($expected, $test);
    }

    public function testPluginPathsOptionKeyShouldAddPrefixPathsToPluginLoader()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->setOptions(array(
            'pluginPaths' => array(
                'Foo' => 'foo/bar/path/',
            ),
        ));
        $loader = $bootstrap->getPluginLoader();
        $paths = $loader->getPaths('Foo');
        $this->assertTrue(is_array($paths));
    }

    public function testResourcesOptionKeyShouldRegisterBootstrapPluginResources()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->setOptions(array(
            'resources' => array(
                'view' => array(
                    'basePath' => __DIR__ . '/TestAssett/views/scripts',
                ),
            ),
        ));
        $this->assertTrue($bootstrap->hasPluginResource('view'));
    }

    public function testHasOptionShouldReturnFalseWhenOptionUnavailable()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $this->assertFalse($bootstrap->hasOption('foo'));
    }

    public function testHasOptionShouldReturnTrueWhenOptionPresent()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->setOptions(array('foo' => 'bar'));
        $this->assertTrue($bootstrap->hasOption('foo'));
    }

    public function testGetOptionShouldReturnNullWhenOptionUnavailable()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $this->assertNull($bootstrap->getOption('foo'));
    }

    public function testGetOptionShouldReturnOptionValue()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->setOptions(array('foo' => 'bar'));
        $this->assertEquals('bar', $bootstrap->getOption('foo'));
    }

    public function testInternalIntializersShouldBeRegisteredAsClassResources()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $test      = $bootstrap->getClassResources();
        $resources = array('foo' => '_initFoo', 'bar' => '_initBar', 'barbaz' => '_initBarbaz');
        $this->assertEquals($resources, $test);
    }

    public function testInternalInitializersShouldRegisterResourceNames()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $test      = $bootstrap->getClassResourceNames();
        $resources = array('foo', 'bar', 'barbaz');
        $this->assertEquals($resources, $test);
    }

    public function testRegisterPluginResourceShouldThrowExceptionForInvalidResourceType()
    {
        $this->setExpectedException('Zend\\Application\\BootstrapException');
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->registerPluginResource(array());
    }

    public function testShouldAllowRegisteringConcretePluginResources()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $resource  = new Application\Resource\View();
        $bootstrap->registerPluginResource($resource);
        $test = $bootstrap->getPluginResource('view');
        $this->assertSame($resource, $test);
    }

    public function testRegisteringSecondPluginResourceOfSameTypeShouldOverwrite()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $resource1  = new Application\Resource\View();
        $resource2  = new Application\Resource\View();
        $bootstrap->registerPluginResource($resource1)
                  ->registerPluginResource($resource2);
        $test = $bootstrap->getPluginResource('view');
        $this->assertSame($resource2, $test);
    }

    public function testShouldAllowRegisteringPluginResourceUsingNameOnly()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->registerPluginResource('view');
        $test = $bootstrap->getPluginResource('view');
        $this->assertEquals('Zend\\Application\\Resource\\View', get_class($test));
    }

    public function testShouldAllowUnregisteringPluginResourcesUsingConcreteInstance()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $resource  = new Application\Resource\View();
        $bootstrap->registerPluginResource($resource);
        $bootstrap->unregisterPluginResource($resource);
        $this->assertFalse($bootstrap->hasPluginResource('view'));
    }

    public function testAttemptingToUnregisterPluginResourcesUsingInvalidResourceTypeShouldThrowException()
    {
        $this->setExpectedException('Zend\\Application\\BootstrapException');
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->registerPluginResource('view');
        $bootstrap->unregisterPluginResource(array());
    }

    public function testShouldAllowUnregisteringPluginResourcesByName()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->registerPluginResource('view');
        $bootstrap->unregisterPluginResource('view');
        $this->assertFalse($bootstrap->hasPluginResource('view'));
    }

    public function testRetrievingNonExistentPluginResourceShouldReturnNull()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $this->assertNull($bootstrap->getPluginResource('view'));
    }

    public function testRetrievingPluginResourcesShouldRetrieveConcreteInstances()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->registerPluginResource('view');
        $test = $bootstrap->getPluginResources();
        foreach ($test as $type => $resource) {
            $this->assertTrue($resource instanceof Application\Resource);
        }
    }

    public function testShouldAllowRetrievingOnlyPluginResourceNames()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->registerPluginResource('view');
        $test = $bootstrap->getPluginResourceNames();
        $this->assertEquals(array('view'), $test);
    }

    public function testShouldAllowSettingAlternativePluginLoaderInstance()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $loader    = new PluginLoader();
        $bootstrap->setPluginLoader($loader);
        $this->assertSame($loader, $bootstrap->getPluginLoader());
    }

    public function testDefaultPluginLoaderShouldRegisterPrefixPathForResources()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $loader = $bootstrap->getPluginLoader();
        $paths  = $loader->getPaths('Zend\\Application\\Resource');
        $this->assertFalse(empty($paths));
    }

    public function testEnvironmentShouldMatchApplicationEnvironment()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $this->assertSame($this->application->getEnvironment(), $bootstrap->getEnvironment());
    }

    public function testBootstrappingShouldOnlyExecuteEachInitializerOnce()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->bootstrap('foo');
        $bootstrap->bootstrap('foo');
        $this->assertEquals(1, $bootstrap->fooExecuted);
    }

    /**
     * @group ZF-7955
     */
    public function testBootstrappingIsCaseInsensitive()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->bootstrap('Foo');
        $bootstrap->bootstrap('Foo');
        $bootstrap->bootstrap('foo');
        $bootstrap->bootstrap('foo');
        $this->assertEquals(1, $bootstrap->fooExecuted);
    }

    public function testBootstrappingShouldFavorInternalResourcesOverPlugins()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->getPluginLoader()->addPrefixPath('ZendTest\\Application\\TestAssett\\Resource', __DIR__ . '/_files/resources');
        $bootstrap->bootstrap('foo');
        $this->assertFalse($bootstrap->executedFooResource);
    }

    public function testBootstrappingShouldAllowPassingAnArrayOfResources()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->bootstrap(array('foo', 'bar'));
        $this->assertEquals(1, $bootstrap->fooExecuted);
        $this->assertEquals(1, $bootstrap->barExecuted);
    }

    /**
     * @group fml
     */
    public function testPassingNoValuesToBootstrapExecutesAllResources()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->getPluginLoader()->addPrefixPath('ZendTest\\Application\\TestAssett\\Resource', __DIR__ . '/TestAssett/resources');
        $bootstrap->registerPluginResource('foobar');
        $bootstrap->bootstrap();
        $this->assertEquals(1, $bootstrap->fooExecuted);
        $this->assertEquals(1, $bootstrap->barExecuted);
        $this->assertTrue($bootstrap->executedFoobarResource);
    }

    public function testPassingInvalidResourceArgumentToBootstrapShouldThrowException()
    {
        $this->setExpectedException('Zend\\Application\\BootstrapException');
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->bootstrap(new \stdClass);
    }

    public function testPassingUnknownResourceToBootstrapShouldThrowException()
    {
        $this->setExpectedException('Zend\\Application\\BootstrapException');
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->bootstrap('bazbat');
    }

    public function testCallShouldOverloadToBootstrap()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->bootstrapFoo();
        $this->assertEquals(1, $bootstrap->fooExecuted);
    }

    public function testCallShouldThrowExceptionForInvalidMethodCall()
    {
        $this->setExpectedException('Zend\\Application\\BootstrapException');
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->initFoo();
    }

    public function testDependencyTrackingShouldDetectCircularDependencies()
    {
        $this->setExpectedException('Zend\\Application\\BootstrapException');
        $bootstrap = new TestAssett\BootstrapBaseCircularDependency($this->application);
        $bootstrap->bootstrap();
    }

    public function testContainerShouldBeRegistryInstanceByDefault()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $container = $bootstrap->getContainer();
        $this->assertTrue($container instanceof Registry);
    }

    public function testContainerShouldAggregateReturnValuesFromClassResources()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->bootstrap('barbaz');
        $container = $bootstrap->getContainer();
        $this->assertEquals('Baz', $container->barbaz->baz);
    }

    public function testContainerShouldAggregateReturnValuesFromPluginResources()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->getPluginLoader()->addPrefixPath('ZendTest\\Application\\TestAssett\\Resource', __DIR__ . '/TestAssett/resources');
        $bootstrap->registerPluginResource('baz');
        $bootstrap->bootstrap('baz');
        $container = $bootstrap->getContainer();
        $this->assertEquals('Baz', $container->baz->baz);
    }

    public function testClassResourcesShouldBeAvailableFollowingBootstrapping()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->bootstrap('barbaz');
        $this->assertTrue($bootstrap->hasResource('barbaz'));

        $resource = $bootstrap->getResource('barbaz');
        $this->assertEquals('Baz', $resource->baz);
    }

    public function testPluginResourcesShouldBeAvailableFollowingBootstrapping()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->getPluginLoader()->addPrefixPath('ZendTest\\Application\\TestAssett\\Resource', __DIR__ . '/TestAssett/resources');
        $bootstrap->registerPluginResource('baz');
        $bootstrap->bootstrap('baz');

        $this->assertTrue($bootstrap->hasResource('baz'));
        $resource = $bootstrap->getResource('baz');
        $this->assertEquals('Baz', $resource->baz);
    }

    public function testMagicMethodsForPluginResources()
    {
        $bootstrap = new TestAssett\ZfAppBootstrap($this->application);
        $bootstrap->getPluginLoader()->addPrefixPath('ZendTest\\Application\\TestAssett\\Resource', __DIR__ . '/TestAssett/resources');
        $bootstrap->registerPluginResource('baz');
        $bootstrap->bootstrap('baz');

        $this->assertTrue(isset($bootstrap->baz));
        $resource = $bootstrap->baz;
        $this->assertEquals('Baz', $resource->baz);
    }

    /**
     * @group ZF-6543
     */
    public function testPassingPluginResourcesByFullClassNameWithMatchingPluginPathShouldRegisterAsShortName()
    {
        $this->application->setOptions(array(
            'resources' => array(
                'ZendTest\\Application\\View' => array(),
            ),
            'pluginPaths' => array(
                'ZendTest\\Application' => __DIR__,
            ),
        ));
        $bootstrap = new Application\Bootstrap($this->application);
        $this->assertTrue($bootstrap->hasPluginResource('View'), var_export(array_keys($bootstrap->getPluginResources()), 1));
    }

    /**
     * @group ZF-6543
     */
    public function testPassingFullViewClassNameNotMatchingARegisteredPrefixShouldRegisterAsTheClassName()
    {
        $this->application->setOptions(array(
            'resources' => array(
                'ZendTest\\Application\\View' => array(),
            ),
        ));
        $bootstrap = new Application\Bootstrap($this->application);
        $this->assertTrue($bootstrap->hasPluginResource('ZendTest\\Application\\View'));
    }

    /**
     * @group ZF-6543
     */
    public function testPassingFullViewClassNameNotMatchingARegisteredPrefixShouldReturnAppropriateResource()
    {
        $this->application->setOptions(array(
            'resources' => array(
                'ZendTest\\Application\\View' => array(),
            ),
        ));
        $bootstrap = new Application\Bootstrap($this->application);
        $bootstrap->bootstrap('ZendTest\\Application\\View');
        $resource = $bootstrap->getResource('ZendTest\\Application\\View');
        $this->assertTrue($resource instanceof View, var_export(array_keys($bootstrap->getPluginResources()), 1));
    }

    /**
     * @group ZF-6543
     */
    public function testCanMixAndMatchPluginResourcesAndFullClassNames()
    {
        $this->application->setOptions(array(
            'resources' => array(
                'ZendTest\\Application\\View' => array(),
                'view' => array(),
            ),
        ));
        $bootstrap = new Application\Bootstrap($this->application);
        $bootstrap->bootstrap('ZendTest\\Application\\View');
        $resource1 = $bootstrap->getResource('ZendTest\\Application\\View');
        $bootstrap->bootstrap('view');
        $resource2 = $bootstrap->getResource('view');
        $this->assertNotSame($resource1, $resource2);
        $this->assertTrue($resource1 instanceof View, var_export(array_keys($bootstrap->getPluginResources()), 1));
        $this->assertTrue($resource2 instanceof \Zend\View\View);
    }

    /**
     * @group ZF-6543
     */
    public function testPluginClassesDefiningExplicitTypeWillBeRegisteredWithThatValue()
    {
        $this->application->setOptions(array(
            'resources' => array(
                'ZendTest\\Application\\Layout' => array(),
                'layout' => array(),
            ),
        ));
        $bootstrap = new Application\Bootstrap($this->application);
        $bootstrap->bootstrap('BootstrapAbstractTestLayout');
        $resource1 = $bootstrap->getResource('BootstrapAbstractTestLayout');
        $bootstrap->bootstrap('layout');
        $resource2 = $bootstrap->getResource('layout');
        $this->assertNotSame($resource1, $resource2);
        $this->assertTrue($resource1 instanceof Layout, var_export(array_keys($bootstrap->getPluginResources()), 1));
        $this->assertTrue($resource2 instanceof \Zend\Layout\Layout);
    }

    /**
     * @group ZF-6471
     */
    public function testBootstrapShouldPassItselfToResourcePluginConstructor()
    {
        $this->application->setOptions(array(
            'pluginPaths' => array(
                'ZendTest\\Application' => __DIR__,
            ),
            'resources' => array(
                'Foo' => array(),
            ),
        ));
        $bootstrap = new Application\Bootstrap($this->application);
        $resource = $bootstrap->getPluginResource('foo');
        $this->assertTrue($resource->bootstrapSetInConstructor, var_export(get_object_vars($resource), 1));
    }

    /**
     * @group ZF-6591
     */
    public function testRequestingPluginsByShortNameShouldNotRaiseFatalErrors()
    {
        $this->autoloader->setFallbackAutoloader(true)
                         ->suppressNotFoundWarnings(false);
        $this->application->setOptions(array(
            'resources' => array(
                'FrontController' => array(),
            ),
        ));
        set_error_handler(array($this, 'handleError'));
        $bootstrap = new Application\Bootstrap($this->application);
        $resource = $bootstrap->getPluginResource('FrontController');
        restore_error_handler();
        $this->assertTrue(false === $this->error, $this->error);
    }

    /**
     * @group ZF-7550
     */
    public function testRequestingPluginsByAutoloadableClassNameShouldNotRaiseFatalErrors()
    {
        // Using namesapce 'zabt' to prevent conflict with Zend namespace
        $rl = new ResourceAutoloader(array(
            'namespace' => 'Zabt',
            'basePath'  => __DIR__ . '/TestAssett',
        ));
        $rl->addResourceType('resources', 'resources', 'Resource');
        $options = array(
            'resources' => array(
                'Zabt\\Resource\\Autoloaded' => array('bar' => 'baz')
            ),
        );
        $this->application->setOptions($options);
        $bootstrap = new Application\Bootstrap($this->application);
        $bootstrap->bootstrap();
    }

    /**
     * @group ZF-7690
     */
    public function testCallingSetOptionsMultipleTimesShouldUpdateOptionKeys()
    {
        $this->application->setOptions(array(
            'resources' => array(
                'layout' => array(),
            ),
        ));
        $bootstrap = new OptionKeys($this->application);
        $bootstrap->setOptions(array(
            'pluginPaths' => array(
                'Foo' => __DIR__,
            ),
        ));
        $expected = array('resources', 'pluginpaths');
        $actual   = $bootstrap->getOptionKeys();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @group ZF-9110
     */
    public function testPassingSameBootstrapAsApplicationShouldNotCauseRecursion()
    {
        $this->setExpectedException('Zend\\Application\\BootstrapException');
        $bootstrap = new Application\Bootstrap($this->application);
        $bootstrap->setApplication($bootstrap);
    }
}

class View extends AbstractResource
{
    public function init()
    {
        return $this;
    }
}

class Layout extends AbstractResource
{
    public $_explicitType = 'BootstrapAbstractTestLayout';
    public $bootstrapSetInConstructor = false;

    public function __construct($options = null)
    {
        parent::__construct($options);
        if (null !== $this->getBootstrap()) {
            $this->bootstrapSetInConstructor = true;
        }
    }

    public function init()
    {
        return $this;
    }
}

class Foo extends AbstractResource
{
    public $bootstrapSetInConstructor = false;

    public function __construct($options = null)
    {
        parent::__construct($options);
        if (null !== $this->getBootstrap()) {
            $this->bootstrapSetInConstructor = true;
        }
    }

    public function init()
    {
        return $this;
    }
}

class OptionKeys extends Application\Bootstrap
{
    public function getOptionKeys()
    {
        return $this->_optionKeys;
    }
}
