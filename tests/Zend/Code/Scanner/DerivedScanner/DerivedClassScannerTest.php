<?php

namespace ZendTest\Code\Scanner\DerivedScanner;

use Zend\Code\Scanner\DirectoryScanner,
    Zend\Code\Scanner\DerivedScanner\AggregateDirectoryScanner,
    Zend\Code\Scanner\DerivedScanner\DerivedClassScanner;

class DerivedClassScannerTest extends \PHPUnit_Framework_TestCase
{
    
    public function testCreatesClass()
    {
        $ds = new DirectoryScanner();
        $ds->addDirectory(__DIR__ . '/../TestAsset');
        $ads = new AggregateDirectoryScanner();
        $ads->addScanner($ds);
        $c = $ads->getClass('ZendTest\Code\Scanner\TestAsset\MapperExample\RepositoryB');
        //echo $c->getName();
        //var_dump($c->getMethods(true));
        var_dump($c->getProperties());
    }
    
    
}