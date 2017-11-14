<?php

namespace MyApp;

class ActionPut implements ActionInterface
{
    public const NAME = 'put_bowl';

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

    public function do(Board &$board): void
    {
        $board->addBowl($this->playerId, $this->x, $this->y, $this->z);
    }

    public function undo(Board &$board): void
    {
        $board->removeBowl($this->x, $this->y, $this->z);
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
}
