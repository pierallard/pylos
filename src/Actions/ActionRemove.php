<?php

namespace Pylos\Actions;

use Pylos\Board;

class ActionRemove extends AbstractAction
{
    public const NAME = 'remove';

    public function do(Board &$board): bool
    {
        $board->removeBowl($this->x, $this->y, $this->z);

        if ($board->getRemoveCounter() === 2) {
            $board->setRemoveCounter(1);
        } else {
            $board->setState(Board::STATE_PICK_BOWL);
            $board->switchPlayer();
        }

        return false;
    }

    public function undo(Board &$board)
    {
        $board->addBowl($this->playerId, $this->x, $this->y, $this->z);

        if ($board->getRemoveCounter() === 1) {
            $board->setState(Board::STATE_REMOVE);
            $board->setRemoveCounter(2);
        } else {
            $board->setState(Board::STATE_PUT_BOWL);
        }
    }

    function getName(): string
    {
        return self::NAME;
    }
}
