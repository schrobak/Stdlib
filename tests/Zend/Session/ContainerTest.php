<?php

namespace ZendTest\Session;

use Zend\Session\Container,
    Zend\Session\Manager,
    Zend\Session;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SESSION = array();
        $this->manager = $manager = new Manager(array(
            'class'   => 'Zend\\Session\\Configuration\\StandardConfiguration',
            'storage' => 'Zend\\Session\\Storage\\ArrayStorage',
            'handler' => 'ZendTest\\Session\\TestAsset\\TestHandler',
        ));
        $this->container = new Container('Default', $manager);
    }

    public function tearDown()
    {
        $_SESSION = array();
    }

    public function testInstantiatingContainerWithoutNameUsesDefaultAsName()
    {
        $this->assertEquals('Default', $this->container->getName());
    }

    public function testPassingNameToConstructorInstantiatesContainerWithThatName()
    {
        $container = new Container('foo');
        $this->assertEquals('foo', $container->getName());
    }

    public function testPassingInvalidNameToConstructorRaisesException()
    {
        $tries = array(
            'f!',
            'foo bar',
            '_foo',
            '__foo',
            '0foo',
        );
        foreach ($tries as $try) {
            try {
                $container = new Container($try);
                $this->fail('Invalid container name should raise exception');
            } catch (\Zend\Session\Exception $e) {
                $this->assertContains('invalid', $e->getMessage());
            }
        }
    }

    public function testContainerActsAsArray()
    {
        $this->container['foo'] = 'bar';
        $this->assertTrue(isset($this->container['foo']));
        $this->assertEquals('bar', $this->container['foo']);
        unset($this->container['foo']);
        $this->assertFalse(isset($this->container['foo']));
    }

    public function testContainerActsAsObject()
    {
        $this->container->foo = 'bar';
        $this->assertTrue(isset($this->container->foo));
        $this->assertEquals('bar', $this->container->foo);
        unset($this->container->foo);
        $this->assertFalse(isset($this->container->foo));
    }

    public function testContainerInstantiatesManagerWithDefaultsWhenNotInjected()
    {
        $container = new Container();
        $manager   = $container->getManager();
        $this->assertTrue($manager instanceof Session\Manager);
        $config  = $manager->getConfig();
        $this->assertTrue($config instanceof Session\Configuration\SessionConfiguration);
        $storage = $manager->getStorage();
        $this->assertTrue($storage instanceof Session\Storage\SessionStorage);
        $handler = $manager->getHandler();
        $this->assertTrue($handler instanceof Session\Handler\SessionHandler);
    }

    public function testContainerAllowsInjectingManagerViaConstructor()
    {
        $manager = new Manager(array(
            'class'   => 'Zend\\Session\\Configuration\\StandardConfiguration',
            'storage' => 'Zend\\Session\\Storage\\ArrayStorage',
            'handler' => 'ZendTest\\Session\\TestAsset\\TestHandler',
        ));
        $container = new Container('Foo', $manager);
        $this->assertSame($manager, $container->getManager());
    }

    public function testContainerWritesToStorage()
    {
        $this->container->foo = 'bar';
        $storage = $this->manager->getStorage();
        $this->assertTrue(isset($storage['Default']));
        $this->assertTrue(isset($storage['Default']['foo']));
        $this->assertEquals('bar', $storage['Default']['foo']);

        unset($this->container->foo);
        $this->assertFalse(isset($storage['Default']['foo']));
    }

    public function testSettingExpirationSecondsUpdatesStorageMetadataForFullContainer()
    {
        $this->container->setExpirationSeconds(3600);
        $storage = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        $this->assertTrue(array_key_exists('EXPIRE', $metadata));
        $this->assertEquals($_SERVER['REQUEST_TIME'] + 3600, $metadata['EXPIRE']);
    }

    public function testSettingExpirationSecondsForIndividualKeyUpdatesStorageMetadataForThatKey()
    {
        $this->container->foo = 'bar';
        $this->container->setExpirationSeconds(3600, 'foo');
        $storage = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        $this->assertTrue(array_key_exists('EXPIRE_KEYS', $metadata));
        $this->assertTrue(array_key_exists('foo', $metadata['EXPIRE_KEYS']));
        $this->assertEquals($_SERVER['REQUEST_TIME'] + 3600, $metadata['EXPIRE_KEYS']['foo']);
    }

    public function testMultipleCallsToExpirationSecondsAggregates()
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $this->container->baz = 'bat';
        $this->container->bat = 'bas';
        $this->container->setExpirationSeconds(3600);
        $this->container->setExpirationSeconds(1800, 'foo');
        $this->container->setExpirationSeconds(900, array('baz', 'bat'));
        $storage = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        $this->assertEquals($_SERVER['REQUEST_TIME'] + 1800, $metadata['EXPIRE_KEYS']['foo']);
        $this->assertEquals($_SERVER['REQUEST_TIME'] +  900, $metadata['EXPIRE_KEYS']['baz']);
        $this->assertEquals($_SERVER['REQUEST_TIME'] +  900, $metadata['EXPIRE_KEYS']['bat']);
        $this->assertEquals($_SERVER['REQUEST_TIME'] + 3600, $metadata['EXPIRE']);
    }

    public function testPassingUnsetKeyToSetExpirationSecondsDoesNothing()
    {
        $this->container->setExpirationSeconds(3600, 'foo');
        $storage = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        $this->assertFalse(isset($metadata['EXPIRE_KEYS']['foo']));
    }

    public function testPassingUnsetKeyInArrayToSetExpirationSecondsDoesNothing()
    {
        $this->container->setExpirationSeconds(3600, array('foo'));
        $storage = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        $this->assertFalse(isset($metadata['EXPIRE_KEYS']['foo']));
    }

    public function testGetKeyWithContainerExpirationInPastResetsToNull()
    {
        $this->container->foo = 'bar';
        $storage = $this->manager->getStorage();
        $storage->setMetadata('Default', array('EXPIRE' => $_SERVER['REQUEST_TIME'] - 18600));
        $this->assertNull($this->container->foo);
    }

    public function testGetKeyWithExpirationInPastResetsToNull()
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $storage = $this->manager->getStorage();
        $storage->setMetadata('Default', array('EXPIRE_KEYS' => array('foo' => $_SERVER['REQUEST_TIME'] - 18600)));
        $this->assertNull($this->container->foo);
        $this->assertEquals('baz', $this->container->bar);
    }

    public function testKeyExistsWithContainerExpirationInPastReturnsFalse()
    {
        $this->container->foo = 'bar';
        $storage = $this->manager->getStorage();
        $storage->setMetadata('Default', array('EXPIRE' => $_SERVER['REQUEST_TIME'] - 18600));
        $this->assertFalse(isset($this->container->foo));
    }

    public function testKeyExistsWithExpirationInPastReturnsFalse()
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $storage = $this->manager->getStorage();
        $storage->setMetadata('Default', array('EXPIRE_KEYS' => array('foo' => $_SERVER['REQUEST_TIME'] - 18600)));
        $this->assertFalse(isset($this->container->foo));
        $this->assertTrue(isset($this->container->bar));
    }

    public function testSettingExpiredKeyOverwritesExpiryMetadataForThatKey()
    {
        $this->container->foo = 'bar';
        $storage = $this->manager->getStorage();
        $storage->setMetadata('Default', array('EXPIRE' => $_SERVER['REQUEST_TIME'] - 18600));
        $this->container->foo = 'baz';
        $this->assertTrue(isset($this->container->foo));
        $this->assertEquals('baz', $this->container->foo);
        $metadata = $storage->getMetadata('Default');
        $this->assertFalse(isset($metadata['EXPIRE_KEYS']['foo']));
    }

    /**
     * @todo expiration hops
     */
}
