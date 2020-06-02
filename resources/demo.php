<?php
if($_REQUEST['speedtest']) {
    require '../vendor/autoload.php';
    
    function output($data) {
        echo json_encode($data) . "\n";
        ob_flush();
        flush();
    }
    $config = new Aln\Speedtest\Config();
    $config->setCallback(function ($result) {
        $download = round((float)$result->getDownload() / 1000 / 1000, 2);
        $upload = round((float)$result->getUpload() / 1000 / 1000, 2);
        output(["result" => ['download' => $download, 'upload' => $upload]]);
    });
    $speedtest = new Aln\Speedtest\Speedtest($config);
    $clientInfo = $speedtest->clientInfo();
    output(['client' => ['isp' => $clientInfo['isp'], 'ip' => $clientInfo['ip']]]);
    $speedtest->getServers();
    $best = $speedtest->getBestServer();
    output(["server" => ["id" => $best['id'], "sponsor" => $best['sponsor'], "name" => $best['name'], "distance" => $best['d'], "latency" => $best['latency']]]);
    $speedtest->download();
    $speedtest->upload();
} else {
?><!DOCTYPE>
<html>
    <head>
        <title>speedtest-php one page demo</title>
        <meta charset="UTF-8" />
        <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <style>
        .wrapper {
            height: 100%;
            width: 100%;
        }
        .header {
            height: 21vh;
        }
        .body {
            height: 77vh;
            width: 80vh;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }
        .footer {
            width: 55vh;
            margin-left: auto;
            margin-right: auto;
            text-align: left;
            font-size: 2vh;
        }
        .speed {
            text-align: right;
            padding-right: 20vh;
        }
        .speed > span {
            font-size: 20vh;
        }
        .lds-ripple {
            display: none;
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
            float: left;
            top: -1vh;
        }
        .lds-ripple div {
            position: absolute;
            border: 4px solid #000;
            opacity: 1;
            border-radius: 50%;
            animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        }
        .lds-ripple div:nth-child(2) {
            animation-delay: -0.5s;
        }
        @keyframes lds-ripple {
            0% {
                top: 36px;
                left: 36px;
                width: 0;
                height: 0;
                opacity: 1;
            }
                100% {
                top: 0px;
                left: 0px;
                width: 72px;
                height: 72px;
                opacity: 0;
            }
        }
        </style>

        <script type="text/javascript">
        $( document ).ready(function() {
            var responseLength = false;
            $(".lds-ripple").show();
            $.ajax('?speedtest=speedtest', {
                xhrFields: {
                    onprogress: function(e) {
                        var response = e.currentTarget.response;
                        var chunk = "";
                        if(responseLength === false) {
                            chunk = response;
                            responseLength = response.length;
                        } else {
                            chunk = response.substring(responseLength);
                            responseLength = response.length;
                        }
                        var lines = chunk.match(/[^\r\n]+/g);
                        var data = JSON.parse(lines[lines.length - 1]);
                        if(data.hasOwnProperty("client")) {
                            $("#client").html(data.client.isp + " " + data.client.ip);
                        }
                        if(data.hasOwnProperty("server")) {
                            $("#latency").html(data.server.latency);
                            $("#server").html(data.server.sponsor + " " + data.server.name + " " + data.server.distance + " Km");
                        }
                        if(data.hasOwnProperty("result")) {
    	                    $("#download").html(data.result.download.toFixed(0));
    	                    $("#upload").html(data.result.upload.toFixed(0));
                        }
                    }
                }
            })
            .done(function(data)
            {
                $(".lds-ripple").hide();
            })
            .fail(function(data)
            {
                $(".lds-ripple").hide();
            });
        });
        </script>
    </head>
    <body>
        <div class="wrapper">
        	<div class="header"></div>
        	<div class="body">
                <div class="speed"><span id="download">--</span> Mbps&darr;</div>
                <div class="speed"><span id="upload">--</span> Mbps&uarr;</div>
            	<div class="lds-ripple"><div></div><div></div></div>
            	<div class="footer">
                    <div>Latency <span id="latency">0</span> ms</div>
                    <div>Client <span id="client">&nbsp;</span></div>
                    <div>Server <span id="server">&nbsp;</span></div>
            	</div>
            </div>
        </div>
    </body>
</html>
<?php } ?>