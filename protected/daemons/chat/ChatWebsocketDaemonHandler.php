<?php

namespace morozovsk\websocket;



class ChatWebsocketDaemonHandler extends Daemon
{
    protected function onOpen($connectionId, $info)
    {
        //call when new client connect to server
    }

    protected function onClose($connectionId)
    {
        //call when existing client close connection
    }

    /**
     * Обработка сообщений от клиента и отправка его всем клиентам
     *
     * @param $connectionId
     * @param string $dataJson сообщение от WebSocket клиента
     * @param $type
     */
    protected function onMessage($connectionId, $dataJson, $type)
    {
        $data = json_decode($dataJson, true);
        $message = 'Пользователь "' . $data['login'] . '" ответил: ' . $data['message'];

        \BtChatLogger::getLogger()->info('Chat', [$data]);

        foreach ($this->clients as $clientId => $client) {
            $this->sendToClient($clientId, $message);
        }
    }
}
