<?php

namespace Pylos\Actions;

use Pylos\Board;

class UndoAction implements ActionInterface
{
    const NAME = 'undo';

    /** @var ActionInterface */
    protected $action;

    public function __construct(ActionInterface $action)
    {
        $this->action = $action;
    }

    public function do(Board &$board)
    {
        $this->action->undo($board);
    }

    public function undo(Board &$board)
    {
        $this->action->do($board);
    }

    public function normalize(): array
    {
        return [
            'action' => self::NAME
        ];
    }
}
