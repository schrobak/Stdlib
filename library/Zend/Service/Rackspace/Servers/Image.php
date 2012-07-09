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
 * @package    Zend\Service\Rackspace
 * @subpackage Servers
 */

namespace Zend\Service\Rackspace\Servers;

use Zend\Service\Rackspace\Servers as RackspaceServers;

class Image 
{
    const ERROR_PARAM_CONSTRUCT = 'You must pass a Zend\Service\Rackspace\Servers object and an array';
    const ERROR_PARAM_NO_NAME   = 'You must pass the image\'s name in the array (name)';
    const ERROR_PARAM_NO_ID     = 'You must pass the image\'s id in the array (id)';
    /**
     * Name of the image
     * 
     * @var string 
     */
    protected $name;
    /**
     * Id of the image
     * 
     * @var string 
     */
    protected $id;
    /**
     * Server Id of the image
     * 
     * @var string 
     */
    protected $serverId;
    /**
     * Updated data
     * 
     * @var string 
     */
    protected $updated;
    /**
     * Created data
     * 
     * @var string 
     */
    protected $created;
    /**
     * Status
     * 
     * @var string 
     */
    protected $status;
    /**
     * Status progress
     * 
     * @var integer 
     */
    protected $progress;
    /**
     * The service that has created the image object
     *
     * @var Zend\Service\Rackspace\Servers
     */
    protected $service;
    /**
     * Construct
     * 
     * @param array $data
     * @return void
     */
    public function __construct(RackspaceServers $service, $data)
    {
        if (!($service instanceof RackspaceServers) || !is_array($data)) {
            throw new Exception\InvalidArgumentException(self::ERROR_PARAM_CONSTRUCT);
        }
        if (!array_key_exists('name', $data)) {
            throw new Exception\InvalidArgumentException(self::ERROR_PARAM_NO_NAME);
        }
        if (!array_key_exists('id', $data)) {
            throw new Exception\InvalidArgumentException(self::ERROR_PARAM_NO_ID);
        }
        $this->service= $service;
        $this->name = $data['name'];
        $this->id = $data['id'];
        if (isset($data['serverId'])) {
            $this->serverId= $data['serverId'];
        }
        if (isset($data['updated'])) {
            $this->updated= $data['updated'];
        }
        if (isset($data['created'])) {
            $this->created= $data['created'];
        }
        if (isset($data['status'])) {
            $this->status= $data['status'];
        }
        if (isset($data['progress'])) {
            $this->progress= $data['progress'];
        }
    }
    /**
     * Get the name of the image
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * Get the image's id
     * 
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Get the server's id of the image
     * 
     * @return string 
     */
    public function getServerId()
    {
        return $this->serverId;
    }
    /**
     * Get the updated data
     * 
     * @return string 
     */
    public function getUpdated()
    {
        return $this->updated;
    }
    /**
     * Get the created data
     * 
     * @return string 
     */
    public function getCreated()
    {
        return $this->created;
    }
    /**
     * Get the image's status
     * 
     * @return string|boolean
     */
    public function getStatus()
    {
        $data= $this->service->getImage($this->id);
        if ($data!==false) {
            $data= $data->toArray();
            $this->status= $data['status'];
            return $this->status;
        }
        return false;
    }
    /**
     * Get the progress's status
     * 
     * @return integer|boolean
     */
    public function getProgress()
    {
        $data= $this->service->getImage($this->id);
        if ($data!==false) {
            $data= $data->toArray();
            $this->progress= $data['progress'];
            return $this->progress;
        }
        return false;
    }
    /**
     * To Array
     * 
     * @return array 
     */
    public function toArray()
    {
        return array (
            'name'     => $this->name,
            'id'       => $this->id,
            'serverId' => $this->serverId,
            'updated'  => $this->updated,
            'created'  => $this->created,
            'status'   => $this->status,
            'progress' => $this->progress
        );
    }
}
