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

class Progress
{
    /**
     * 
     * @var int
     */
    protected $start;

    /**
     * 
     * @var array
     */
    protected $status = [];
    
    /**
     * 
     * @var Result
     */
    protected $result;
    
    public function __construct() {
        $this->result = new Result();
    }
    
    public function start() {
        $this->status = [];
        $this->start = microtime(true);
    }
    
    /**
     * 
     * @return \Aln\Speedtest\Result
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * 
     * @param int $id
     * @param string $mode
     * @param int $size
     */
    public function progress($id, $mode, $size) {
        $this->status[$id] = $size;
        $duration = max(microtime(true) - $this->start, 0.001);
        $bytes = array_sum($this->status);
        $bits = $bytes * 8;
        $speed = $bits / $duration;
        
        if($mode == 'upload') {
            $this->result->setUpload($speed);
            $this->result->setBytesSent($bytes);
        } else {
            $this->result->setDownload($speed);
            $this->result->setBytesReceived($bytes);
        }
    }
}
