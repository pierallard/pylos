<?php

namespace MyApp;

use Ratchet\ConnectionInterface;

class Game
{
    private const MAX_PLAYERS = 2;
    private const STATE_WAITING = 'waiting';
    private const STATE_PICK_BOWL = 'pick_bowl';
    private const STATE_PUT_BOWL = 'put_bowl';

    /** @var Board */
    private $board;

    /** @var ConnectionInterface[] */
    private $players;

    /** @var ConnectionInterface */
    private $currentPlayer = null;

    /** @var array */
    private $currentBowl = null;

    public function __construct()
    {
        $this->state = self::STATE_WAITING;
        $this->board = new Board();
        $this->players = [];
    }

    public function addPlayer(ConnectionInterface $player)
    {
        $player->send(json_encode(['event' => 'game_joined']));

        $this->players[] = $player;

        if ($this->isFull()) {
            $this->start();
        }
    }

    public function isFull()
    {
        return count($this->players) >= self::MAX_PLAYERS;
    }

    public function hasPlayer(ConnectionInterface $conn)
    {
        return in_array($conn, $this->players);
    }

    public function removePlayer(ConnectionInterface $player)
    {
        if (($key = array_search($player, $this->players)) !== false) {
            unset($this->players[$key]);
        }
    }

    private function start()
    {
        $this->state = self::STATE_PICK_BOWL;
        $this->currentPlayer = $this->players[0];

        foreach ($this->players as $player) {
            $player->send(json_encode(['event' => 'game_started']));
        }

        $this->sendState();
    }

    private function sendState()
    {
        echo "Send your turn to " . $this->getPlayerId($this->currentPlayer) . "\n";
        if ($this->state === self::STATE_PICK_BOWL) {
            $this->currentPlayer->send(json_encode([
                'event' => 'pick_bowl',
                'actions' => $this->board->getAvailableBowls($this->getPlayerId($this->currentPlayer))
            ]));
        } else {
            $this->currentPlayer->send(json_encode([
                'event' => 'put_bowl',
                'actions' => $this->board->getAvailablePositions($this->currentBowl)
            ]));
        }

        $others = array_filter($this->players, function ($player) {
            return $player !== $this->currentPlayer;
        });
        echo "Send waiting to " . $this->getPlayerId(current($others)) . "\n";
        current($others)->send(json_encode(['event' => 'waiting']));

        $normalizedBoard = $this->board->normalize();
        foreach ($this->players as $player) {
            $player->send(json_encode(['event' => 'board_changed', 'board' => $normalizedBoard]));
        }
    }

    public function doAction($player, $position)
    {
        if ($this->state === self::STATE_PICK_BOWL) {
            if (intval($position->x) !== -1) {
                $this->currentBowl = $position;
            } else {
                $this->currentBowl = null;
            }
            $this->state = self::STATE_PUT_BOWL;
        } else {
            if ($this->currentBowl !== null) {
                $this->board->moveBowl($this->currentBowl, $position);
            } else {
                $this->board->addBowl($this->getPlayerId($player), $position);
            }
            $this->state = self::STATE_PICK_BOWL;

            $others = array_filter($this->players, function ($player) {
                return $player !== $this->currentPlayer;
            });
            $this->currentPlayer = current($others);
        }

        $this->sendState();
    }

    private function getPlayerId($player)
    {
        return array_search($player, $this->players);
    }
}
