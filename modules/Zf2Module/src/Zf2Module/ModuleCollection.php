<?php

namespace Zf2Module;

use Zend\Config\Config;

class ModuleCollection
{
    /**
     * @var ModuleResolver
     */
    protected $loader;

    /**
     * @var array An array of Information classes of loaded modules
     */
    protected $modules = array();

    /**
     * getLoader 
     * 
     * @return ModuleResolver
     */
    public function getLoader()
    {
        if (!$this->loader instanceof ModuleResolver) {
            $this->setLoader(new ModuleLoader);
        }
        return $this->loader;
    }

    /**
     * setLoader 
     * 
     * @param ModuleResolver $loader 
     * @return ModuleCollection
     */
    public function setLoader(ModuleResolver $loader)
    {
        $this->loader = $loader;
        return $this;
    }

    /**
     * loadModules 
     * 
     * @param array $modules 
     * @return ModuleCollection
     */
    public function loadModules(array $modules)
    {
        foreach ($modules as $moduleName) {
            $this->loadModule($moduleName);
        }
        return $this->modules;
    }

    /**
     * loadModule 
     * 
     * @param string $moduleName 
     * @return mixed Module's information class
     */
    public function loadModule($moduleName)
    {
        if (!isset($this->modules[$moduleName])) {
            $infoClass = $this->getLoader()->load($moduleName);
            $this->modules[$moduleName] = new $infoClass;
        }
        return $this->modules[$moduleName];
    }

    /**
     * getMergedConfig
     * Build a merged config object for all loaded modules
     * 
     * @return Zend\Config\Config
     */
    public function getMergedConfig()
    {
        $config = new Config(array(), true);
        foreach ($this->modules as $module) {
            if (is_callable(array($module, 'getConfig'))) {
                $config->merge($module->getConfig());
            }
        }
        $config->setReadOnly();
        return $config;
    }
}
