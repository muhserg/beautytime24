<?php

namespace morozovsk\websocket;

class BtChatServerWin extends Server
{
    public function start() {
        $pid = @file_get_contents($this->config['pid']);
        /*if ($pid) {
            if (getmypid()) {
                die("already started\r\n");
            } else {
                unlink($this->config['pid']);
            }
        }*/

        if (empty($this->config['websocket']) && empty($this->config['localsocket']) && empty($this->config['master'])) {
            die("error: config: !websocket && !localsocket && !master\r\n");
        }

        $server = $service = $master = null;

        if (!empty($this->config['websocket'])) {
            //open server socket
            $server = stream_socket_server($this->config['websocket'], $errorNumber, $errorString);
            stream_set_blocking($server, 0);

            if (!$server) {
                die("error: stream_socket_server: $errorString ($errorNumber)\r\n");
            }
        }

        if (!empty($this->config['localsocket'])) {
            //create a socket for the processing of messages from scripts
            $service = stream_socket_server($this->config['localsocket'], $errorNumber, $errorString);
            stream_set_blocking($service, 0);

            if (!$service) {
                die("error: stream_socket_server: $errorString ($errorNumber)\r\n");
            }
        }

        if (!empty($this->config['master'])) {
            //create a socket for the processing of messages from slaves
            $master = stream_socket_client($this->config['master'], $errorNumber, $errorString);
            stream_set_blocking($master, 0);

            if (!$master) {
                die("error: stream_socket_client: $errorString ($errorNumber)\r\n");
            }
        }

        if (!empty($this->config['eventDriver']) && $this->config['eventDriver'] == 'libevent') {
            class_alias('morozovsk\websocket\GenericLibevent', 'Generic');
        } elseif (!empty($this->config['eventDriver']) && $this->config['eventDriver'] == 'event') {
            class_alias('morozovsk\websocket\GenericEvent', 'Generic');
        } else {
            class_alias('morozovsk\websocket\GenericSelect', 'Generic');
        }

        //file_put_contents($this->config['pid'], getmypid());

        $workerClass = $this->config['class'];
        $worker = new $workerClass ($server, $service, $master);
        if (!empty($this->config['timer'])) {
            $worker->timer = $this->config['timer'];
        }
        $worker->start();
    }
}
