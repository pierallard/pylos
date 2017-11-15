<?php

namespace Pylos;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    private $games;

    public function __construct() {
        $this->games = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $conn->send(json_encode(['event' => 'connected']));
        $game = $this->getOrCreateGame();
        $game->addPlayer($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onClose(ConnectionInterface $conn) {
        $game = $this->getGameOfPlayer($conn);
        $game->removePlayer($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $game = $this->getGameOfPlayer($from);
        $message = json_decode($msg);
        if ($message->action !== 'undo') {
            $game->doAction($from, json_decode($msg));
        } else {
            $game->undo();
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
    }

    private function getOrCreateGame(): Game
    {
        foreach($this->games as $game) {
            if (!$game->isFull()) {
                return $game;
            }
        }

        echo "Create a new game\n";
        $game = new Game();
        $this->games[] = $game;

        return $game;
    }

    private function getGameOfPlayer($conn): Game
    {
        foreach($this->games as $game) {
            if ($game->hasPlayer($conn)) {
                return $game;
            }
        }

        throw new \Exception('No game found');
    }
}
