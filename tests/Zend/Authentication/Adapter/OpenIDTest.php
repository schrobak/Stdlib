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
 * @package    Zend_Auth
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @namespace
 */
namespace ZendTest\Authentication\Adapter;

use Zend\Authentication\Adapter\OpenID as OpenIDAdapter,
    Zend\OpenID\Consumer\Storage\File as OpenIDFileStorage,
    Zend\OpenID\Extension\Sreg as OpenIDSregExtension;

/**
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Auth
 */
class OpenIdTest extends \PHPUnit_Framework_TestCase
{
    const ID       = "http://id.myopenid.com/";
    const REAL_ID  = "http://real_id.myopenid.com/";
    const SERVER   = "http://www.myopenid.com/";

    const HANDLE   = "d41d8cd98f00b204e9800998ecf8427e";
    const MAC_FUNC = "sha1";
    const SECRET   = "\x83\x82\xae\xa9\x22\x56\x0e\xce\x83\x3b\xa5\x5f\xa5\x3b\x7a\x97\x5f\x59\x73\x70";

    public function testAuthenticateInvalid()
    {
        $adapter = new OpenIDAdapter(null, new OpenIDFileStorage(__DIR__ . "/TestAsset/OpenId"));
        $ret = $adapter->authenticate();
        $this->assertFalse($ret->isValid());
        $this->assertSame("", $ret->getIdentity());
        $this->assertSame(0, $ret->getCode());
        $msgs = $ret->getMessages();
        $this->assertTrue(is_array($msgs));
        $this->assertSame(2, count($msgs));
        $this->assertSame("Authentication failed", $msgs[0]);
        $this->assertSame("Missing openid.mode", $msgs[1]);
    }

    public function testAuthenticateLoginInvalid()
    {
        $adapter = new OpenIDAdapter("%sd", new OpenIDFileStorage(__DIR__."/TestAsset/OpenId"));
        $ret = $adapter->authenticate();
        $this->assertFalse($ret->isValid());
        $this->assertSame("%sd", $ret->getIdentity());
        $this->assertSame(0, $ret->getCode());
        $msgs = $ret->getMessages();
        $this->assertTrue(is_array($msgs));
        $this->assertSame(2, count($msgs));
        $this->assertSame("Authentication failed", $msgs[0]);
        $this->assertSame("Normalisation failed", $msgs[1]);
    }

    public function testAuthenticateLoginValid()
    {
        $expiresIn = time() + 600;
        $storage = new OpenIDFileStorage(__DIR__."/TestAsset/OpenId");
        $storage->delDiscoveryInfo(self::ID);
        $storage->addDiscoveryInfo(self::ID, self::REAL_ID, self::SERVER, 1.1, $expiresIn);
        $storage->delAssociation(self::SERVER);
        $storage->addAssociation(self::SERVER, self::HANDLE, self::MAC_FUNC, self::SECRET, $expiresIn);

        $response = new TestAsset\OpenIdResponseHelper(true);

        $_SERVER['SCRIPT_URI'] = "http://www.zf-test.com/test.php";

        $adapter = new OpenIDAdapter(self::ID, $storage);
        $this->assertSame($adapter, $adapter->setResponse($response));
        $ret = $adapter->authenticate();
        $this->assertTrue(is_null($ret));
        $headers = $response->getHeaders();
        $this->assertSame( '', $response->getBody() );
        $this->assertTrue( is_array($headers) );
        $this->assertSame( 1, count($headers) );
        $this->assertTrue( is_array($headers[0]) );
        $this->assertSame( 3, count($headers[0]) );
        $this->assertSame( 'Location', $headers[0]['name'] );
        $this->assertSame( true, $headers[0]['replace'] );
        $url = $headers[0]['value'];
        $url = parse_url($url);
        $this->assertSame( "http", $url['scheme'] );
        $this->assertSame( "www.myopenid.com", $url['host'] );
        $this->assertSame( "/", $url['path'] );
        $q = explode("&", $url['query']);
        $query = array();
        foreach($q as $var) {
            if (list($key, $val) = explode("=", $var, 2)) {
                $query[$key] = $val;
            }
        }
        $this->assertTrue( is_array($query) );
        $this->assertSame( 6, count($query) );
        $this->assertSame( 'checkid_setup', $query['openid.mode'] );
        $this->assertSame( 'http%3A%2F%2Freal_id.myopenid.com%2F', $query['openid.identity'] );
        $this->assertSame( 'http%3A%2F%2Fid.myopenid.com%2F', $query['openid.claimed_id'] );
        $this->assertSame( self::HANDLE, $query['openid.assoc_handle'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com%2Ftest.php', $query['openid.return_to'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com', $query['openid.trust_root'] );
    }

    public function testSetIdentity()
    {
        $expiresIn = time() + 600;
        $storage = new OpenIDFileStorage(__DIR__."/TestAsset/OpenId");
        $storage->delDiscoveryInfo(self::ID);
        $storage->addDiscoveryInfo(self::ID, self::REAL_ID, self::SERVER, 1.1, $expiresIn);
        $storage->delAssociation(self::SERVER);
        $storage->addAssociation(self::SERVER, self::HANDLE, self::MAC_FUNC, self::SECRET, $expiresIn);

        $response = new TestAsset\OpenIdResponseHelper(true);

        $_SERVER['SCRIPT_URI'] = "http://www.zf-test.com/test.php";

        $adapter = new OpenIDAdapter(null, $storage);
        $this->assertSame($adapter, $adapter->setIdentity(self::ID));
        $adapter->setResponse($response);
        $ret = $adapter->authenticate();
        $this->assertTrue(is_null($ret));
        $headers = $response->getHeaders();
        $this->assertSame( '', $response->getBody() );
        $this->assertTrue( is_array($headers) );
        $this->assertSame( 1, count($headers) );
        $this->assertTrue( is_array($headers[0]) );
        $this->assertSame( 3, count($headers[0]) );
        $this->assertSame( 'Location', $headers[0]['name'] );
        $this->assertSame( true, $headers[0]['replace'] );
        $url = $headers[0]['value'];
        $url = parse_url($url);
        $this->assertSame( "http", $url['scheme'] );
        $this->assertSame( "www.myopenid.com", $url['host'] );
        $this->assertSame( "/", $url['path'] );
        $q = explode("&", $url['query']);
        $query = array();
        foreach($q as $var) {
            if (list($key, $val) = explode("=", $var, 2)) {
                $query[$key] = $val;
            }
        }
        $this->assertTrue( is_array($query) );
        $this->assertSame( 6, count($query) );
        $this->assertSame( 'checkid_setup', $query['openid.mode'] );
        $this->assertSame( 'http%3A%2F%2Freal_id.myopenid.com%2F', $query['openid.identity'] );
        $this->assertSame( 'http%3A%2F%2Fid.myopenid.com%2F', $query['openid.claimed_id'] );
        $this->assertSame( self::HANDLE, $query['openid.assoc_handle'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com%2Ftest.php', $query['openid.return_to'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com', $query['openid.trust_root'] );
    }

    public function testSetStorage()
    {
        $expiresIn = time() + 600;
        $storage = new OpenIDFileStorage(__DIR__."/TestAsset/OpenId");
        $storage->delDiscoveryInfo(self::ID);
        $storage->addDiscoveryInfo(self::ID, self::REAL_ID, self::SERVER, 1.1, $expiresIn);
        $storage->delAssociation(self::SERVER);
        $storage->addAssociation(self::SERVER, self::HANDLE, self::MAC_FUNC, self::SECRET, $expiresIn);

        $response = new TestAsset\OpenIdResponseHelper(true);

        $_SERVER['SCRIPT_URI'] = "http://www.zf-test.com/test.php";

        $adapter = new OpenIDAdapter(self::ID);
        $this->assertSame($adapter, $adapter->setStorage($storage));
        $adapter->setResponse($response);
        $ret = $adapter->authenticate();
        $this->assertTrue(is_null($ret));
        $headers = $response->getHeaders();
        $this->assertSame( '', $response->getBody() );
        $this->assertTrue( is_array($headers) );
        $this->assertSame( 1, count($headers) );
        $this->assertTrue( is_array($headers[0]) );
        $this->assertSame( 3, count($headers[0]) );
        $this->assertSame( 'Location', $headers[0]['name'] );
        $this->assertSame( true, $headers[0]['replace'] );
        $url = $headers[0]['value'];
        $url = parse_url($url);
        $this->assertSame( "http", $url['scheme'] );
        $this->assertSame( "www.myopenid.com", $url['host'] );
        $this->assertSame( "/", $url['path'] );
        $q = explode("&", $url['query']);
        $query = array();
        foreach($q as $var) {
            if (list($key, $val) = explode("=", $var, 2)) {
                $query[$key] = $val;
            }
        }
        $this->assertTrue( is_array($query) );
        $this->assertSame( 6, count($query) );
        $this->assertSame( 'checkid_setup', $query['openid.mode'] );
        $this->assertSame( 'http%3A%2F%2Freal_id.myopenid.com%2F', $query['openid.identity'] );
        $this->assertSame( 'http%3A%2F%2Fid.myopenid.com%2F', $query['openid.claimed_id'] );
        $this->assertSame( self::HANDLE, $query['openid.assoc_handle'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com%2Ftest.php', $query['openid.return_to'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com', $query['openid.trust_root'] );
    }

    public function testSetReturnTo()
    {
        $expiresIn = time() + 600;
        $storage = new OpenIDFileStorage(__DIR__."/TestAsset/OpenId");
        $storage->delDiscoveryInfo(self::ID);
        $storage->addDiscoveryInfo(self::ID, self::REAL_ID, self::SERVER, 1.1, $expiresIn);
        $storage->delAssociation(self::SERVER);
        $storage->addAssociation(self::SERVER, self::HANDLE, self::MAC_FUNC, self::SECRET, $expiresIn);

        $response = new TestAsset\OpenIdResponseHelper(true);

        $_SERVER['SCRIPT_URI'] = "http://www.zf-test.com/test.php";

        $adapter = new OpenIDAdapter(self::ID, $storage);
        $adapter->setResponse($response);
        $this->assertSame($adapter, $adapter->setReturnTo("http://www.zf-test.com/return.php"));
        $ret = $adapter->authenticate();
        $this->assertTrue(is_null($ret));
        $headers = $response->getHeaders();
        $this->assertSame( '', $response->getBody() );
        $this->assertTrue( is_array($headers) );
        $this->assertSame( 1, count($headers) );
        $this->assertTrue( is_array($headers[0]) );
        $this->assertSame( 3, count($headers[0]) );
        $this->assertSame( 'Location', $headers[0]['name'] );
        $this->assertSame( true, $headers[0]['replace'] );
        $url = $headers[0]['value'];
        $url = parse_url($url);
        $this->assertSame( "http", $url['scheme'] );
        $this->assertSame( "www.myopenid.com", $url['host'] );
        $this->assertSame( "/", $url['path'] );
        $q = explode("&", $url['query']);
        $query = array();
        foreach($q as $var) {
            if (list($key, $val) = explode("=", $var, 2)) {
                $query[$key] = $val;
            }
        }
        $this->assertTrue( is_array($query) );
        $this->assertSame( 6, count($query) );
        $this->assertSame( 'checkid_setup', $query['openid.mode'] );
        $this->assertSame( 'http%3A%2F%2Freal_id.myopenid.com%2F', $query['openid.identity'] );
        $this->assertSame( 'http%3A%2F%2Fid.myopenid.com%2F', $query['openid.claimed_id'] );
        $this->assertSame( self::HANDLE, $query['openid.assoc_handle'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com%2Freturn.php', $query['openid.return_to'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com', $query['openid.trust_root'] );
    }

    public function testSetRoot()
    {
        $expiresIn = time() + 600;
        $storage = new OpenIDFileStorage(__DIR__."/TestAsset/OpenId");
        $storage->delDiscoveryInfo(self::ID);
        $storage->addDiscoveryInfo(self::ID, self::REAL_ID, self::SERVER, 1.1, $expiresIn);
        $storage->delAssociation(self::SERVER);
        $storage->addAssociation(self::SERVER, self::HANDLE, self::MAC_FUNC, self::SECRET, $expiresIn);

        $response = new TestAsset\OpenIdResponseHelper(true);

        $_SERVER['SCRIPT_URI'] = "http://www.zf-test.com/test.php";

        $adapter = new OpenIDAdapter(self::ID, $storage);
        $adapter->setResponse($response);
        $this->assertSame($adapter, $adapter->setRoot("http://www.zf-test.com/root.php"));
        $ret = $adapter->authenticate();
        $this->assertTrue(is_null($ret));
        $headers = $response->getHeaders();
        $this->assertSame( '', $response->getBody() );
        $this->assertTrue( is_array($headers) );
        $this->assertSame( 1, count($headers) );
        $this->assertTrue( is_array($headers[0]) );
        $this->assertSame( 3, count($headers[0]) );
        $this->assertSame( 'Location', $headers[0]['name'] );
        $this->assertSame( true, $headers[0]['replace'] );
        $url = $headers[0]['value'];
        $url = parse_url($url);
        $this->assertSame( "http", $url['scheme'] );
        $this->assertSame( "www.myopenid.com", $url['host'] );
        $this->assertSame( "/", $url['path'] );
        $q = explode("&", $url['query']);
        $query = array();
        foreach($q as $var) {
            if (list($key, $val) = explode("=", $var, 2)) {
                $query[$key] = $val;
            }
        }
        $this->assertTrue( is_array($query) );
        $this->assertSame( 6, count($query) );
        $this->assertSame( 'checkid_setup', $query['openid.mode'] );
        $this->assertSame( 'http%3A%2F%2Freal_id.myopenid.com%2F', $query['openid.identity'] );
        $this->assertSame( 'http%3A%2F%2Fid.myopenid.com%2F', $query['openid.claimed_id'] );
        $this->assertSame( self::HANDLE, $query['openid.assoc_handle'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com%2Ftest.php', $query['openid.return_to'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com%2Froot.php', $query['openid.trust_root'] );
    }

    public function testAuthenticateVerifyInvalid()
    {
        $_SERVER['SCRIPT_URI'] = "http://www.zf-test.com/test.php";
        unset($_SERVER['REQUEST_METHOD']);
        $_GET = array('openid_mode'=>'id_res',
            "openid_return_to" => "http://www.zf-test.com/test.php",
            "openid_assoc_handle" => self::HANDLE,
            "openid_claimed_id" => self::ID,
            "openid_identity" => self::REAL_ID,
            "openid_response_nonce" => "2007-08-14T12:52:33Z46c1a59124fff",
            "openid_signed" => "assoc_handle,return_to,claimed_id,identity,response_nonce,mode,signed",
            "openid_sig" => "h/5AFD25NpzSok5tzHEGCVUkQSw="
        );
        $storage = new OpenIDFileStorage(__DIR__."/TestAsset/OpenId");
        $adapter = new OpenIDAdapter(null, $storage);
        $ret = $adapter->authenticate();
        $this->assertFalse($ret->isValid());
        $this->assertSame(self::ID, $ret->getIdentity());
        $this->assertSame(0, $ret->getCode());
        $msgs = $ret->getMessages();
        $this->assertTrue(is_array($msgs));
        $this->assertSame(2, count($msgs));
        $this->assertSame("Authentication failed", $msgs[0]);
        $this->assertSame("Signature check failed", $msgs[1]);
    }

    public function testAuthenticateVerifyGetValid()
    {
        $expiresIn = time() + 600;
        $storage = new OpenIDFileStorage(__DIR__."/TestAsset/OpenId");
        $storage->delDiscoveryInfo(self::ID);
        $storage->addDiscoveryInfo(self::ID, self::REAL_ID, self::SERVER, 1.1, $expiresIn);
        $storage->delAssociation(self::SERVER);
        $storage->addAssociation(self::SERVER, self::HANDLE, self::MAC_FUNC, self::SECRET, $expiresIn);
        $storage->purgeNonces();

        $_SERVER['SCRIPT_URI'] = "http://www.zf-test.com/test.php";
        unset($_SERVER['REQUEST_METHOD']);
        $_GET = array(
            "openid_return_to" => "http://www.zf-test.com/test.php",
            "openid_assoc_handle" => self::HANDLE,
            "openid_claimed_id" => self::ID,
            "openid_identity" => self::REAL_ID,
            "openid_response_nonce" => "2007-08-14T12:52:33Z46c1a59124ffe",
            "openid_mode" => "id_res",
            "openid_signed" => "assoc_handle,return_to,claimed_id,identity,response_nonce,mode,signed",
            "openid_sig" => "h/5AFD25NpzSok5tzHEGCVUkQSw="
        );
        $adapter = new OpenIDAdapter(null, $storage);
        $ret = $adapter->authenticate();
        $this->assertTrue($ret->isValid());
    }

    public function testAuthenticateVerifyPostValid()
    {
        $expiresIn = time() + 600;
        $storage = new OpenIDFileStorage(__DIR__."/TestAsset/OpenId");
        $storage->delDiscoveryInfo(self::ID);
        $storage->addDiscoveryInfo(self::ID, self::REAL_ID, self::SERVER, 1.1, $expiresIn);
        $storage->delAssociation(self::SERVER);
        $storage->addAssociation(self::SERVER, self::HANDLE, self::MAC_FUNC, self::SECRET, $expiresIn);
        $storage->purgeNonces();

        $_SERVER['SCRIPT_URI'] = "http://www.zf-test.com/test.php";
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = array();
        $_POST = array(
            "openid_return_to" => "http://www.zf-test.com/test.php",
            "openid_assoc_handle" => self::HANDLE,
            "openid_claimed_id" => self::ID,
            "openid_identity" => self::REAL_ID,
            "openid_response_nonce" => "2007-08-14T12:52:33Z46c1a59124ffe",
            "openid_mode" => "id_res",
            "openid_signed" => "assoc_handle,return_to,claimed_id,identity,response_nonce,mode,signed",
            "openid_sig" => "h/5AFD25NpzSok5tzHEGCVUkQSw="
        );
        $adapter = new OpenIDAdapter(null, $storage);
        $ret = $adapter->authenticate();
        $this->assertTrue($ret->isValid());
    }

    public function testSetExtensions()
    {
        $expiresIn = time() + 600;
        $storage = new OpenIDFileStorage(__DIR__."/TestAsset/OpenId");
        $this->assertTrue( $storage->delDiscoveryInfo(self::ID) );
        $this->assertTrue( $storage->addDiscoveryInfo(self::ID, self::REAL_ID, self::SERVER, 2.0, $expiresIn) );
        $storage->delAssociation(self::SERVER);
        $storage->addAssociation(self::SERVER, self::HANDLE, self::MAC_FUNC, self::SECRET, $expiresIn);
        $storage->purgeNonces();

        $_SERVER['SCRIPT_URI'] = "http://www.zf-test.com/test.php";
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = array(
            "openid_ns"        => \Zend\OpenID\OpenID::NS_2_0,
            "openid_return_to" => "http://www.zf-test.com/test.php",
            "openid_assoc_handle" => self::HANDLE,
            "openid_claimed_id" => self::ID,
            "openid_identity" => self::REAL_ID,
            "openid_response_nonce" => "2007-08-14T12:52:33Z46c1a59124ffe",
            "openid_op_endpoint" => self::SERVER,
            "openid_mode" => "id_res",
            "openid_ns_sreg" => "http://openid.net/extensions/sreg/1.1",
            "openid_sreg_nickname" => "test",
            "openid_signed" => "ns,assoc_handle,return_to,claimed_id,identity,response_nonce,mode,ns.sreg,sreg.nickname,signed",
            "openid_sig" => "jcV5K517GrjOxjRzi0QNLX2D+1s="
        );
        $_POST = array();
        $adapter = new OpenIDAdapter(null, $storage);
        $sreg= new OpenIDSregExtension(array("nickname"=>true,"email"=>false));
        $this->assertSame($adapter, $adapter->setExtensions($sreg));
        $ret = $adapter->authenticate();
        $this->assertTrue($ret->isValid());
        $sreg_data = $sreg->getProperties();
        $this->assertSame("test", $sreg_data['nickname']);
    }

    function testSetCheckImmediate() 
    {
        $expiresIn = time() + 600;
        $storage = new OpenIDFileStorage(__DIR__."/TestAsset/OpenId");
        $storage->delDiscoveryInfo(self::ID);
        $storage->addDiscoveryInfo(self::ID, self::REAL_ID, self::SERVER, 1.1, $expiresIn);
        $storage->delAssociation(self::SERVER);
        $storage->addAssociation(self::SERVER, self::HANDLE, self::MAC_FUNC, self::SECRET, $expiresIn);

        $response = new TestAsset\OpenIdResponseHelper(true);

        $_SERVER['SCRIPT_URI'] = "http://www.zf-test.com/test.php";

        $adapter = new OpenIDAdapter(self::ID, $storage);
        $adapter->setCheckImmediate(true);
        $this->assertSame($adapter, $adapter->setResponse($response));
        $ret = $adapter->authenticate();
        $this->assertTrue(is_null($ret));
        $headers = $response->getHeaders();
        $this->assertSame( '', $response->getBody() );
        $this->assertTrue( is_array($headers) );
        $this->assertSame( 1, count($headers) );
        $this->assertTrue( is_array($headers[0]) );
        $this->assertSame( 3, count($headers[0]) );
        $this->assertSame( 'Location', $headers[0]['name'] );
        $this->assertSame( true, $headers[0]['replace'] );
        $url = $headers[0]['value'];
        $url = parse_url($url);
        $this->assertSame( "http", $url['scheme'] );
        $this->assertSame( "www.myopenid.com", $url['host'] );
        $this->assertSame( "/", $url['path'] );
        $q = explode("&", $url['query']);
        $query = array();
        foreach($q as $var) {
            if (list($key, $val) = explode("=", $var, 2)) {
                $query[$key] = $val;
            }
        }
        $this->assertTrue( is_array($query) );
        $this->assertSame( 6, count($query) );
        $this->assertSame( 'checkid_immediate', $query['openid.mode'] );
        $this->assertSame( 'http%3A%2F%2Freal_id.myopenid.com%2F', $query['openid.identity'] );
        $this->assertSame( 'http%3A%2F%2Fid.myopenid.com%2F', $query['openid.claimed_id'] );
        $this->assertSame( self::HANDLE, $query['openid.assoc_handle'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com%2Ftest.php', $query['openid.return_to'] );
        $this->assertSame( 'http%3A%2F%2Fwww.zf-test.com', $query['openid.trust_root'] );
    }

    function testSetHttpClient() 
    {
        $storage = new OpenIDFileStorage(__DIR__."/TestAsset/OpenId");
        $storage->delDiscoveryInfo(self::ID);
        $storage->delAssociation(self::SERVER);
        $adapter = new OpenIDAdapter(self::ID, $storage);
        $http = new \Zend\HTTP\Client(null,
            array(
                'maxredirects' => 4,
                'timeout'      => 15,
                'useragent'    => 'Zend_OpenId'
            ));
        $test = new \Zend\HTTP\Client\Adapter\Test();
        $http->setAdapter($test);
        $adapter->setHttpClient($http);
        $ret = $adapter->authenticate();
        $this->assertSame("GET / HTTP/1.1\r\n".
                          "Host: id.myopenid.com\r\n".
                          "Connection: close\r\n".
                          "Accept-encoding: gzip, deflate\r\n".
                          "User-Agent: Zend_OpenId\r\n\r\n",
                          $http->getLastRequest());
    }

}
