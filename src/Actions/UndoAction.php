<?php

namespace Pylos\Actions;

use Pylos\Board;

class UndoAction implements ActionInterface
{
    const NAME = 'undo';

    /** @var int */
    protected $playerId;

    public function __construct($playerId)
    {
        $this->playerId = $playerId;
    }

    public function do(Board &$board)
    {
//        $this->action->undo($board);
    }

    public function undo(Board &$board)
    {
//        $this->action->do($board);
    }

    public function normalize(): array
    {
        return [
            'action' => self::NAME
        ];
    }

    public function getPlayerId(): int
    {
        return $this->playerId;
    }
}
