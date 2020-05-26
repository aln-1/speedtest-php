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

class Speedtest
{
    const VERSION = "1.0.2";
    
    /**
     * 
     * @var Config
     */
    protected $config;
    
    /**
     * 
     * @var array
     */
    protected $servers = [];
    
    /**
     *
     * @var array
     */
    protected $best = null;
    
    /**
     * 
     * @var Progress
     */
    protected $progress;
    
    /**
     * 
     * @var string
     */
    protected $mode;
    
    public function __construct(Config $config = null) {
        $this->progress = new Progress();
        $this->config = $config ?: new Config();
        $this->getRemoteConfig();
    }
    
    /**
     * 
     * @param \SimpleXMLElement $element
     * @return string[]
     */
    protected function xmlAttributesToArray(\SimpleXMLElement $element) {
        $attributes = [];
        foreach ($element->attributes() as $key => $value) {
            $attributes[(string)$key] = (string)$value;
        };
        return $attributes;
    }
    
    /**
     * 
     * @param float $lata
     * @param float $lona
     * @param float $latb
     * @param float $lonb
     * @return float
     * 
     */
    protected function haversine($lata, $lona, $latb, $lonb) {
        if (($lata == $latb) && ($lona == $lonb)) {
            return 0;
        }

        $r = 6371; // earth radius, Km
        $lata = deg2rad($lata);
        $lona = deg2rad($lona);
        $latb = deg2rad($latb);
        $lonb = deg2rad($lonb);
        
        return 2 * $r * asin(sqrt(pow(sin(($lata - $latb) / 2), 2) + cos($lata) * cos($latb) * pow(sin(($lona - $lonb) / 2), 2)));
    }
    
    /**
     * 
     * @throws SpeedtestException
     */
    protected function getRemoteConfig() {
        try {
            $xml = new \SimpleXMLElement("https://www.speedtest.net/speedtest-config.php", null, true);
            $server_config = $xml->{'server-config'};
            $download = $xml->download;
            $upload = $xml->upload;
            $client = $this->xmlAttributesToArray($xml->client);
            $ignore_servers = explode(',', $server_config['ignoreids']);
            $ratio = (int)$upload['ratio'];
            $upload_max = (int)$upload['maxchunkcount'];
            $up_sizes = [32768, 65536, 131072, 262144, 524288, 1048576, 7340032];
            $sizes = [
                'upload' => array_slice($up_sizes, $ratio - 1),
                'download' => [350, 500, 750, 1000, 1500, 2000, 2500, 3000, 3500, 4000]
            ];
            
            $size_count = count($sizes['upload']);
            $upload_count = (int)ceil($upload_max / $size_count);
            
            $counts = ['upload' => $upload_count, 'download' => (int)$download['threadsperurl']];
            $threads = ['upload' => (int)$upload['threads'], 'download' => ((int) $server_config['threadcount']) * 2];
            $length = ['upload' => (int)$upload['testlength'], 'download' => (int)$download['testlength']];
            
            $this->config->setClient($client);
            $this->config->setIgnoreServers(
                array_values(array_unique(array_merge($ignore_servers, $this->config->getIgnoreServers())))
                );
            $this->config->setSizes($sizes);
            $this->config->setCounts($counts);
            $this->config->setThreads($threads);
            $this->config->setLength($length);
            $this->config->setUploadMax($upload_count * $size_count);
        } catch (\Exception $e) {
            throw new SpeedtestException("Can not retrieve speedtest configuration");
        }
    }

    /**
     * 
     * @throws SpeedtestException
     * @return string[][]
     */
    public function getServers() {
        $list = [];
        
        $urls = [
            'https://www.speedtest.net/speedtest-servers-static.php',
            'https://c.speedtest.net/speedtest-servers-static.php',
            'https://www.speedtest.net/speedtest-servers.php',
            'https://c.speedtest.net/speedtest-servers.php',
        ];
        
        foreach ($urls as $url) {
            try {
                $xml = new \SimpleXMLElement($url, null, true);
                foreach($xml->servers->server as $server) {
                    $server = $this->xmlAttributesToArray($server);
                    $id = (int)$server['id'];
                    
                    if((!$this->config->getUseServers() || in_array($id, $this->config->getUseServers()))
                        && !in_array($id, $this->config->getIgnoreServers())) {
                        $server['d'] = $this->haversine(
                            (float)$this->config->getClient()['lat'],
                            (float)$this->config->getClient()['lon'],
                            (float)$server['lat'],
                            (float)$server['lon']
                            );
                        $list[(int)$server['id']] = $server;
                    }
                }
            } catch (\Exception $e) {
                throw new SpeedtestException("Can not retrieve server list");
            }
        }
        
        if(!$list) {
            throw new SpeedtestException("No matched servers");
        }
        
        usort($list, function($a, $b) {
            if ($a['d'] == $b['d']) {
                return 0;
            }
            return ($a['d'] < $b['d']) ? -1 : 1;
        });
        
        $this->servers = $list;
        return $this->servers;
    }
    
    /**
     * return string[]
     */
    public function getBestServer() {
        $servers = array_filter($this->servers, function($server) {
            return $server['d'] < 250;
        });
        if($servers) {
            $this->servers = $servers;
        }
        
        $latency = PHP_INT_MAX;
        $fastest = null;
        
        $ch = curl_init();
        if($this->config->getSourceAddress()) {
            curl_setopt($ch, CURLOPT_INTERFACE, $this->config->getSourceAddress());
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getTimeout());
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        
        for($i = 0; $i < min([100, count($this->servers)]); $i++) {
            $url = $this->servers[$i]['url'];
            $parts = parse_url($url);
            $url = preg_replace('/\?.*/', '', $url);
            $cum = [];
            for($j = 0; $j < 3; $j++) {
                $url = str_replace($parts['path'], $parts['path'] . '/latency.txt?x=' . microtime(true) , $url);
                curl_setopt($ch, CURLOPT_URL, $url);
                $response = curl_exec($ch);
                $info = curl_getinfo($ch);
                if(($info['http_code'] == 200) && (trim($response) == 'test=test')) {
                    $cum[] = ($info['starttransfer_time'] - $info['pretransfer_time']) * 1000;
                } else {
                    $cum[] = 3600;
                }
            }
            if((array_sum($cum) / count($cum)) < $latency) {
                $latency = array_sum($cum) / count($cum);
                $fastest = $this->servers[$i];
            }
        }
        
        curl_close($ch);
        $latency = number_format($latency, 2);
        $fastest['latency'] = $latency;
        $this->progress->getResult()->setLatency($latency);
        $fastest['d'] = number_format($fastest['d'], 2);
        $this->best = $fastest;
        return $this->best;
    }
    
    /**
     * 
     * @param resource $ch
     * @param int $download_size
     * @param int $downloaded
     * @param int $upload_size
     * @param int $uploaded
     */
    protected function progress($ch, $download_size, $downloaded, $upload_size, $uploaded) {
        $this->progress->progress(intval($ch), $this->mode, ($this->mode == 'download') ? $downloaded : $uploaded);
        if($this->config->getCallback()) {
            call_user_func($this->config->getCallback(), $this->progress->getResult());
        }
    }
    
    /**
     * 
     * @param int $threads
     */
    public function download($threads = null) {
        $this->mode = 'download';
        
        $urls = [];
        $parts = parse_url($this->best['url']);
        foreach ($this->config->getSizes()['download'] as $size) {
            $urls[] = str_replace($parts['path'], $parts['path'] . '/random' . $size . 'x' . $size . '.jpg', $this->best['url']);
        }
        
        $mh = curl_multi_init();
        $maxConnections = $this->config->isSingle() ? 1 : $threads ?: $this->config->getThreads()['download'];
        curl_multi_setopt($mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, $maxConnections);
        $conn = [];
        foreach ($urls as $url) {
            $ch = curl_init();
            if($this->config->getSourceAddress()) {
                curl_setopt($ch, CURLOPT_INTERFACE, $this->config->getSourceAddress());
            }
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getTimeout());
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, [$this, 'progress']);
            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_multi_add_handle($mh, $ch);
            $conn[] = $ch;
        }
        
        $this->progress->start();
        
        do {
            $active = false;
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);
        
        foreach ($conn as $ch) {
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    }

    /**
     *
     * @param int $threads
     */
    public function upload($threads = null) {
        $this->mode = 'upload';
        
        $datas = [];
        $sizes = $this->config->getSizes()['upload'];
        $requestCount = $this->config->getUploadMax();
        foreach ($sizes as $size) {
            $datas[] = random_bytes($size);
        }
        
        $mh = curl_multi_init();
        $maxConnections = $this->config->isSingle() ? 1 : $threads ?: $this->config->getThreads()['upload'];
        curl_multi_setopt($mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, $maxConnections);
        $conn = [];
        $dataCount = 0;
        for($i = 0; $i < $requestCount; $i++) {
            $data = $datas[$dataCount++];
            if($dataCount == count($datas)) {
                $dataCount = 0;
            }
            $ch = curl_init();
            if($this->config->getSourceAddress()) {
                curl_setopt($ch, CURLOPT_INTERFACE, $this->config->getSourceAddress());
            }
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getTimeout());
            curl_setopt($ch, CURLOPT_URL, $this->best['url']);
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, [$this, 'progress']);
            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-length:' . strlen($data)]);
            curl_multi_add_handle($mh, $ch);
            $conn[] = $ch;
        }
        
        $this->progress->start();
        
        do {
            $active = false;
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);
        
        foreach ($conn as $ch) {
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    }
    
    /**
     * @return string
     */
    public function share() {
        $results = $this->results();
        $latency = (int)round($results->getLatency(), 0);
        $download = (int)round($results->getDownload() / 1000, 0);
        $upload = (int)round($results->getUpload() / 1000, 0);
        $bytesReceived = (int)$results->getBytesReceived();
        $bytesSent = (int)$results->getBytesSent();
        $hash = md5("$latency-$upload-$download-297aae72");
        
        $apiData = [
            'recommendedserverid' => $this->best['id'],
            'ping' => $latency,
            'screenresolution' => '',
            'promo' => '',
            'download' => $download,
            'screendpi' => '',
            'upload' => $upload,
            'testmethod' => 'https',
            'hash' => $hash,
            'touchscreen' => 'none',
            'startmode' => 'pingselect',
            'accuracy' => '1',
            'bytesreceived' => $bytesReceived,
            'bytessent' => $bytesSent,
            'serverid' => $this->best['id']
        ];
        
        $ch = curl_init();
        if($this->config->getSourceAddress()) {
            curl_setopt($ch, CURLOPT_INTERFACE, $this->config->getSourceAddress());
        }
        curl_setopt($ch, CURLOPT_URL, 'https://www.speedtest.net/api/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, 'http://c.speedtest.net/flash/speedtest.swf');
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getTimeout());
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $resultId = null;
        $params = [];
        if($info['http_code'] == 200) {
            parse_str($response, $params);
            if(isset($params['resultid'])) {
                $resultId = $params['resultid'];
            }
        }
        
        if(!$resultId) {
            throw new SpeedtestException("Could not submit results to speedtest.net");
        }
        
        return "https://www.speedtest.net/result/$resultId.png";
    }
    
    /**
     * 
     * @return \Aln\Speedtest\Result
     */
    public function results() {
        return $this->progress->getResult();
    }

    /**
     * @return array
     */
    public function clientInfo()
    {
        return $this->config->getClient();
    }
}
