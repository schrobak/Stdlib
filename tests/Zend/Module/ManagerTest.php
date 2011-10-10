<?php

namespace ZendTest\Module;

use PHPUnit_Framework_TestCase as TestCase,
    Zend\Loader\ModuleAutoloader,
    Zend\Module\Manager,
    Zend\Module\ManagerOptions,
    InvalidArgumentException;

class ManagerTest extends TestCase
{

    public function setUp()
    {
        $this->tmpdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zend_module_cache_dir';
        @mkdir($this->tmpdir);
        $this->configCache = $this->tmpdir . DIRECTORY_SEPARATOR . 'config.cache.php';
        // Store original autoloaders
        $this->loaders = spl_autoload_functions();
        if (!is_array($this->loaders)) {
            // spl_autoload_functions does not return empty array when no
            // autoloaders registered...
            $this->loaders = array();
        }

        // Store original include_path
        $this->includePath = get_include_path();

        $autoloader = new ModuleAutoloader(array(
            __DIR__ . '/TestAsset',
        ));
        $autoloader->register();
    }

    public function tearDown()
    {
        $file = glob($this->tmpdir . DIRECTORY_SEPARATOR . '*');
        @unlink($file[0]); // change this if there's ever > 1 file 
        @rmdir($this->tmpdir);
        // Restore original autoloaders
        $loaders = spl_autoload_functions();
        if (is_array($loaders)) {
            foreach ($loaders as $loader) {
                spl_autoload_unregister($loader);
            }
        }

        foreach ($this->loaders as $loader) {
            spl_autoload_register($loader);
        }

        // Restore original include_path
        set_include_path($this->includePath);
    }

    public function testDefaultManagerOptions()
    {
        $moduleManager = new Manager(array());
        $this->assertInstanceOf('Zend\Module\ManagerOptions', $moduleManager->getOptions());
    }

    public function testCanSetManagerOptionsInConstructor()
    {
        $options = new ManagerOptions(array('cache_dir' => __DIR__));
        $moduleManager = new Manager(array(), $options);
        $this->assertSame(__DIR__, $moduleManager->getOptions()->cache_dir);
    }

    public function testCanLoadSomeModule()
    {
        $moduleManager = new Manager(array('SomeModule'));
        $loadedModules = $moduleManager->getLoadedModules();
        $this->assertInstanceOf('SomeModule\Module', $loadedModules['SomeModule']);
        $config = $moduleManager->getMergedConfig();
        $this->assertSame($config->some, 'thing');
    }

    public function testCanLoadMultipleModules()
    {
        $moduleManager = new Manager(array('BarModule', 'BazModule'));
        $loadedModules = $moduleManager->getLoadedModules();
        $this->assertInstanceOf('BarModule\Module', $loadedModules['BarModule']);
        $this->assertInstanceOf('BazModule\Module', $loadedModules['BazModule']);
        $config = $moduleManager->getMergedConfig();
        $this->assertSame('foo', $config->bar);
        $this->assertSame('bar', $config->baz);
    }

    public function testCanCacheMerchedConfig()
    {
        $options = new ManagerOptions(array(
            'enable_config_cache' => true,
            'cache_dir' => $this->tmpdir,
        ));
        // build the cache
        $moduleManager = new Manager(array('BarModule', 'BazModule'), $options);
        $config = $moduleManager->getMergedConfig();
        $this->assertSame('foo', $config->bar);
        $this->assertSame('bar', $config->baz);

        // use the cache
        $moduleManager = new Manager(array('BarModule', 'BazModule'), $options);
        $config = $moduleManager->getMergedConfig();
        $this->assertSame('foo', $config->bar);
        $this->assertSame('bar', $config->baz);
    }

    public function testConstructorThrowsInvalidArgumentException()
    {
        $this->setExpectedException('InvalidArgumentException');
        $moduleManager = new Manager('stringShouldBeArray');
    }
    
    public function testDoubleProvisionException()
    {
    	 $this->setExpectedException('RuntimeException');
    	 $options = new ManagerOptions(array(
            'enable_config_cache' => true,
            'cache_dir' => $this->tmpdir,
        ));
        // build the cache
        $moduleManager = new Manager(array('BarModule', 'DoubleModule'), $options);
        $config = $moduleManager->getMergedConfig();
        var_dump($moduleManager->getDependencies());
    }
    
    public function testGetOfDependanciesPostLoad()
    {
    	 $options = new ManagerOptions(array(
            'enable_config_cache' => true,
            'cache_dir' => $this->tmpdir,
        ));
        // build the cache
        $moduleManager = new Manager(array('BarModule'), $options);
        $dependencies = $moduleManager->getDependencies();
        $this->assertInternalType('array', $dependencies);
        $this->assertArrayHasKey('php', $dependencies);
    }

	public function testGetOfProvisionsPostLoad()
    {
    	 $options = new ManagerOptions(array(
            'enable_config_cache' => true,
            'cache_dir' => $this->tmpdir,
        ));
        // build the cache
        $moduleManager = new Manager(array('BarModule'), $options);
        $provisions = $moduleManager->getProvisions();
        $this->assertInternalType('array', $provisions);
        $this->assertArrayHasKey('BarModule', $provisions);
    }
    
    public function testResolutionOfMinimumPhpVersion()
    {
    	$this->setExpectedException('RuntimeException');
    	$options = new ManagerOptions(array(
            'enable_config_cache' => true,
            'cache_dir' => $this->tmpdir,
        ));
        // build the cache
        $moduleManager = new Manager(array('ImpossibleModule', 'BarModule'), $options);
        $moduleManager->resolveDependencies();
        $dependencies = $moduleManager->getDependencies();
		
    }
    
    public function testForDissatisfactionForPhpVersion()
    {
    	$options = new ManagerOptions(array(
            'enable_config_cache' => true,
            'cache_dir' => $this->tmpdir,
        ));
        // build the cache
        $moduleManager = new Manager(array('ImpossibleModule', 'BooModule'), $options);
        $moduleManager->resolveDependencies();
        $dependencies = $moduleManager->getDependencies();
        $this->assertInternalType('array', $dependencies);
        $this->assertFalse($dependencies['php']['satisfied']);
    }
    
	public function testForDissatisfactionForExtension()
    {
    	$options = new ManagerOptions(array(
            'enable_config_cache' => true,
            'cache_dir' => $this->tmpdir,
        ));
        // build the cache
        $moduleManager = new Manager(array('ImpossibleModule', 'BooModule'), $options);
        $moduleManager->resolveDependencies();
        $dependencies = $moduleManager->getDependencies();
        $this->assertInternalType('array', $dependencies);
        $this->assertFalse($dependencies['php']['satisfied']);
    }
    
	public function testResolutionOnInvalidExtension()
    {
    	$this->setExpectedException('RuntimeException');
    	$options = new ManagerOptions(array(
            'enable_config_cache' => true,
            'cache_dir' => $this->tmpdir,
        ));
        // build the cache
        $moduleManager = new Manager(array('BooModule', 'BorModule'), $options);
        $moduleManager->resolveDependencies();
        $dependencies = $moduleManager->getDependencies();
    }
    
	public function testResolutionOnInvalidModule()
    {
    	$this->setExpectedException('RuntimeException');
    	$options = new ManagerOptions(array(
            'enable_config_cache' => true,
            'cache_dir' => $this->tmpdir,
        ));
        // build the cache
        $moduleManager = new Manager(array('BafModule'), $options);
        $moduleManager->resolveDependencies();
        $dependencies = $moduleManager->getDependencies();
    }
    
	public function testForDissatisfactionForModule()
    {
    	$options = new ManagerOptions(array(
            'enable_config_cache' => true,
            'cache_dir' => $this->tmpdir,
        ));
        // build the cache
        $moduleManager = new Manager(array('BamModule'), $options);
        $moduleManager->resolveDependencies();
        $dependencies = $moduleManager->getDependencies();
        $this->assertInternalType('array', $dependencies);
        $this->assertFalse($dependencies['BooModule']['satisfied']);
    }
    
    
}
