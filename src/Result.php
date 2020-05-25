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

class Result
{
    /**
     * 
     * @var float
     */
    protected $latency;
    
    /**
     *
     * @var float
     */
    protected $download;
    
    /**
     *
     * @var float
     */
    protected $upload;
    
    /**
     *
     * @var int
     */
    protected $bytesReceived = 0;
    
    /**
     *
     * @var int
     */
    protected $bytesSent = 0;
    
    /**
     * @return int
     */
    public function getBytesReceived()
    {
        return $this->bytesReceived;
    }

    /**
     * @param int $bytesReceived
     */
    public function setBytesReceived($bytesReceived)
    {
        $this->bytesReceived = $bytesReceived;
    }

    /**
     * @return int
     */
    public function getBytesSent()
    {
        return $this->bytesSent;
    }

    /**
     * @param int $bytesSent
     */
    public function setBytesSent($bytesSent)
    {
        $this->bytesSent = $bytesSent;
    }

    /**
     * @return float
     */
    public function getLatency()
    {
        return $this->latency;
    }

    /**
     * @param float $latency
     */
    public function setLatency($latency)
    {
        $this->latency = $latency;
    }

    /**
     * @return float
     */
    public function getDownload()
    {
        return $this->download;
    }

    /**
     * @param float $download
     */
    public function setDownload($download)
    {
        $this->download = $download;
    }

    /**
     * @return float
     */
    public function getUpload()
    {
        return $this->upload;
    }

    /**
     * @param float $upload
     */
    public function setUpload($upload)
    {
        $this->upload = $upload;
    }

}
