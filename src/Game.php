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
                'event' => 'possible_actions',
                'actions' => $this->normalizeActions(
                    $this->board->getPickActions($this->getPlayerId($this->currentPlayer))
                )
            ]));
        } else {
            $this->currentPlayer->send(json_encode([
                'event' => 'possible_actions',
                'actions' => $this->normalizeActions(
                    $this->board->getPutActions($this->getPlayerId($this->currentPlayer))
                )
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

    public function doAction($player, $action)
    {
        if ($player !== $this->currentPlayer) {
            return;
        }
        $action = $this->parseAction($action, $this->getPlayerId($this->currentPlayer));
        $action->do($this->board);

        if ($this->state === self::STATE_PICK_BOWL) {
            $this->state = self::STATE_PUT_BOWL;
        } else {
            $this->state = self::STATE_PICK_BOWL;
            $this->switchPlayer();
        }

        $this->sendState();
    }

    private function switchPlayer()
    {
        $others = array_filter($this->players, function ($player) {
            return $player !== $this->currentPlayer;
        });
        $this->currentPlayer = current($others);
    }

    private function getPlayerId($player)
    {
        return array_search($player, $this->players);
    }

    private function normalizeActions(array $actions): array
    {
        return array_map(function (ActionInterface $action) {
            return $action->normalize();
        }, $actions);
    }

    private function parseAction($action, $playerId)
    {
        if ($action->action === ActionPick::NAME) {
            return new ActionPick($playerId, intval($action->x), intval($action->y), intval($action->z));
        } else if ($action->action === ActionPut::NAME) {
            return new ActionPut($playerId, intval($action->x), intval($action->y), intval($action->z));
        }

        throw new \Exception(sprintf('Invalid action type: "%s"', $action->action));
    }
}
