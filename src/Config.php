<?php

/*
 * Copyright 2020 Alon Noy
 * All Rights Reserved.
 *
 * Original Python version (https://github.com/sivel/speedtest-cli) is
 * Copyright 2012 Matt Martz
 * All Rights Reserved.
 *
 *    Licensed under the Apache License, Version 2.0 (the "License"); you may
 *    not use this file except in compliance with the License. You may obtain
 *    a copy of the License at
 *
 *         http://www.apache.org/licenses/LICENSE-2.0
 *
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 *    WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 *    License for the specific language governing permissions and limitations
 *    under the License.
 */

namespace Aln\Speedtest;

class Config
{
    /**
     * 
     * @var string
     */
    protected $sourceAddress = null;
    
    /**
     * 
     * @var int
     */
    protected $timeout = 10;
    
    /**
     *
     * @var bool
     */
    protected $single = false;
    
    /**
     * 
     * @var array
     */
    protected $client = [];
    
    /**
     * 
     * @var array
     */
    protected $ignoreServers = [];
    
    /**
     *
     * @var array
     */
    protected $useServers = [];
    
    /**
     * 
     * @var array
     */
    protected $sizes = [];
    
    /**
     * 
     * @var array
     */
    protected $counts = [];
    
    /**
     * 
     * @var array
     */
    protected $threads = [];
    
    /**
     * 
     * @var array
     */
    protected $length = [];
    
    /**
     * 
     * @var integer
     */
    protected $uploadMax = 0;
    
    /**
     * 
     * @var Callable
     */
    protected $callback = null;
    
    /**
     * @return Callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param Callable $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return boolean
     */
    public function isSingle()
    {
        return $this->single;
    }

    /**
     * @param boolean $single
     */
    public function setSingle($single)
    {
        $this->single = $single;
    }

    /**
     * @return string
     */
    public function getSourceAddress()
    {
        return $this->sourceAddress;
    }

    /**
     * @param string $sourceAddress
     */
    public function setSourceAddress($sourceAddress)
    {
        $this->sourceAddress = $sourceAddress;
    }

    /**
     * @return number
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param number $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return array
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param array $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return array
     */
    public function getIgnoreServers()
    {
        return $this->ignoreServers;
    }

    /**
     * @param array $ignoreServers
     */
    public function setIgnoreServers($ignoreServers)
    {
        $this->ignoreServers = $ignoreServers;
    }

    /**
     * @return array
     */
    public function getUseServers()
    {
        return $this->useServers;
    }
    
    /**
     * @param array $useServers
     */
    public function setUseServers($useServers)
    {
        $this->useServers = $useServers;
    }
    
    /**
     * @return array
     */
    public function getSizes()
    {
        return $this->sizes;
    }

    /**
     * @param array $sizes
     */
    public function setSizes($sizes)
    {
        $this->sizes = $sizes;
    }

    /**
     * @return array
     */
    public function getCounts()
    {
        return $this->counts;
    }

    /**
     * @param array $counts
     */
    public function setCounts($counts)
    {
        $this->counts = $counts;
    }

    /**
     * @return array
     */
    public function getThreads()
    {
        return $this->threads;
    }

    /**
     * @param array $threads
     */
    public function setThreads($threads)
    {
        $this->threads = $threads;
    }

    /**
     * @return array
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param array $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return int
     */
    public function getUploadMax()
    {
        return $this->uploadMax;
    }

    /**
     * @param int $uploadMax
     */
    public function setUploadMax($uploadMax)
    {
        $this->uploadMax = $uploadMax;
    }
}
