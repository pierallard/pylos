<?php

namespace Pylos\Actions;

use Pylos\Board;

class ActionPick extends AbstractAction
{
    public const NAME = 'pick_bowl';
    public const BOARD_PICK = -1;

    public static function createBoardPick(int $playerId)
    {
        return new ActionPick($playerId, self::BOARD_PICK, self::BOARD_PICK, self::BOARD_PICK);
    }

    public function do(Board &$board): void
    {
        if (!$this->isBoardPick()) {
            $board->removeBowl($this->x, $this->y, $this->z);
        }
        $board->setPickedBowlZ($this->z);

        $board->setState(Board::STATE_PUT_BOWL);
    }

    public function undo(Board &$board): void
    {
        if (!$this->isBoardPick()) {
            $board->addBowl($this->playerId, $this->x, $this->y, $this->z);
        }

        $board->setState(Board::STATE_PICK_BOWL);
    }

    private function isBoardPick(): bool
    {
        return $this->x === self::BOARD_PICK && $this->y === self::BOARD_PICK && $this->z === self::BOARD_PICK;
    }

    function getName(): string
    {
        return self::NAME;
    }
}
