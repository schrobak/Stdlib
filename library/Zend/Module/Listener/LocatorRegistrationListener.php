<?php

namespace Zend\Module\Listener;

use Zend\EventManager\StaticEventManager,
    Zend\EventManager\Event,
    Zend\Module\ModuleEvent,
    Zend\Module\Consumer\LocatorRegistered,
    Zend\EventManager\EventCollection,
    Zend\EventManager\ListenerAggregate;

class LocatorRegistrationListener extends AbstractListener implements ListenerAggregate
{
    /**
     * @var array
     */
    protected $modules = array();

    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * __invoke 
     *
     * Convenience method for manually attaching an instance of this class as a listener.
     * 
     * @param ModuleEvent $e 
     * @return void
     */
    public function __invoke(ModuleEvent $e)
    {
        return $this->loadModule($e);
    }

    /**
     * loadModule 
     *
     * Check each loaded module to see if it implements LocatorRegistered. If it 
     * does, we add it to an internal array for later.
     * 
     * @param ModuleEvent $e 
     * @return void
     */
    public function loadModule(ModuleEvent $e)
    {
        if (!$e->getModule() instanceof LocatorRegistered) {
            return;
        }
        $this->modules[] = $e->getModule();
    }

    /**
     * loadModulesPost 
     *
     * Once all the modules are loaded, loop 
     * 
     * @param Event $e 
     * @return void
     */
    public function loadModulesPost(Event $e)
    {
        if (0 === count($this->modules)) {
            return;
        }

        // Attach to the bootstrap event if there are modules we need to process
        $events = StaticEventManager::getInstance();
        $events->attach('bootstrap', 'bootstrap', array($this, 'addTypePreference'), 1000);
    }

    /**
     * addTypePreference 
     *
     * This is ran during the MVC bootstrap event because it requires access to 
     * the DI container.
     *
     * @TODO: Check the application / locator / etc a bit better to make sure 
     * the env looks how we're expecting it to?
     * @param Event $e 
     * @return void
     */
    public function addTypePreference(Event $e)
    {
        $im = $e->getParam('application')->getLocator()->instanceManager();

        foreach ($this->modules as $module) {
            $moduleClassName = get_class($module);
            if (!$im->hasTypePreferences($moduleClassName)) {
                $im->addTypePreference($moduleClassName, $module);
            }
        }
    }

    /**
     * Attach one or more listeners
     *
     * @param EventCollection $events
     * @return LocatorRegistrationListener
     */
    public function attach(EventCollection $events)
    {
        $this->listeners[] = $events->attach('loadModule', array($this, 'loadModule'), 1000);
        $this->listeners[] = $events->attach('loadModules.post', array($this, 'loadModulesPost'), 9000);
        return $this;
    }

    /**
     * Detach all previously attached listeners
     *
     * @param EventCollection $events
     * @return LocatorRegistrationListener
     */
    public function detach(EventCollection $events)
    {
        foreach ($this->listeners as $key => $listener) {
            $events->detach($listener);
            unset($this->listeners[$key]);
        }
        $this->listeners = array();
        return $this;
    }
}
