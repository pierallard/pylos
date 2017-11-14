<?php

namespace MyApp;

class ActionPick implements ActionInterface
{
    public const NAME = 'pick_bowl';
    private const BOARD_PICK = -1;

    private $playerId;
    private $x;
    private $y;
    private $z;

    public function __construct(int $playerId, int $x, int $y, int $z)
    {
        $this->playerId = $playerId;
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    public static function createBoardPick(int $playerId)
    {
        return new ActionPick($playerId, self::BOARD_PICK, self::BOARD_PICK, self::BOARD_PICK);
    }

    public function do(Board &$board): void
    {
        if (!$this->isBoardPick()) {
            $board->removeBowl($this->x, $this->y, $this->z);
        }
    }

    public function undo(Board &$board): void
    {
        if (!$this->isBoardPick()) {
            $board->addBowl($this->playerId, $this->x, $this->y, $this->z);
        }
    }

    public function normalize(): array
    {
        return [
            'action' => self::NAME,
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z
        ];
    }

    private function isBoardPick(): bool
    {
        return $this->x === self::BOARD_PICK && $this->y === self::BOARD_PICK && $this->z === self::BOARD_PICK;
    }
}
