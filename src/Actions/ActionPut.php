<?php

namespace Pylos\Actions;

use Pylos\Board;

class ActionPut extends AbstractAction
{
    public const NAME = 'put_bowl';

    public function do(Board &$board): void
    {
        $board->addBowl($this->playerId, $this->x, $this->y, $this->z);

        if ($board->isSquare($this->playerId)) {
            $board->setState(Board::STATE_REMOVE);
            $board->setRemoveCounter(2);
        } else {
            $board->setState(Board::STATE_PICK_BOWL);
            $board->switchPlayer();
        }
    }

    public function undo(Board &$board): void
    {
        $board->removeBowl($this->x, $this->y, $this->z);
        
        $board->setState(Board::STATE_PICK_BOWL);
    }

    function getName(): string
    {
        return self::NAME;
    }
}
