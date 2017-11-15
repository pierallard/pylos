<?php

namespace Pylos;

use Pylos\Actions\ActionInterface;
use Pylos\Actions\ActionPick;
use Pylos\Actions\ActionPut;
use Ratchet\ConnectionInterface;

class Game
{
    private const MAX_PLAYERS = 2;

    /** @var Board */
    private $board;

    /** @var ConnectionInterface[] */
    private $players;

    /** @var ActionInterface[] */
    private $actions;

    public function __construct()
    {
        $this->board = new Board();
        $this->players = [];
        $this->actions = [];
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
        $this->board->start($this->getPlayerId($this->players[0]));

        $this->sendState();
    }

    private function sendState()
    {
        $possible_actions = $this->board->getPossibleActions();

        $actions = [];
        foreach ($possible_actions as $action) {
            $playerId = $action->getPlayerId();
            if (!isset($actions[$playerId])) {
                $actions[$playerId] = [];
            }

            $actions[$playerId][] = $action;
        }

        foreach (array_keys($this->players) as $playerId) {
            $this->getPlayer($playerId)->send(json_encode([
                'event' => 'possible_actions',
                'actions' => $this->normalizeActions(isset($actions[$playerId]) ? $actions[$playerId] : [])
            ]));

            $this->getPlayer($playerId)->send(json_encode([
                'event' => 'update_undo',
                'value' => $this->board->canUndo($playerId)
            ]));
        }

        $normalizedBoard = $this->board->normalize();
        foreach ($this->players as $player) {
            $player->send(json_encode(['event' => 'board_changed', 'board' => $normalizedBoard]));
        }
    }

    public function doAction($player, $action)
    {
        if ($this->getPlayerId($player) !== $this->board->getCurrentPlayerId()) {
            echo "Nope.";

            return;
        }

        $action = $this->parseAction($action, $this->board->getCurrentPlayerId());
        $action->do($this->board);
        $this->actions[] = $action;

        $this->sendState();
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

    /**
     * @param int $playerId
     *
     * @return ConnectionInterface
     */
    private function getPlayer($playerId)
    {
        return $this->players[$playerId];
    }

    public function undo()
    {
        $lastAction = array_pop($this->actions);
        $lastAction->undo($this->board);
        $this->sendState();
    }
}
