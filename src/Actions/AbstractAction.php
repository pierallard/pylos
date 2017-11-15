<?php

namespace Pylos\Actions;

use Pylos\Board;

abstract class AbstractAction implements ActionInterface
{
    protected $playerId;
    protected $x;
    protected $y;
    protected $z;

    public function __construct(int $playerId, int $x, int $y, int $z)
    {
        $this->playerId = $playerId;
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    public function normalize(): array
    {
        return [
            'action' => $this->getName(),
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z
        ];
    }

    public function getPlayerId(): int
    {
        return $this->playerId;
    }

    abstract function getName(): string;
}
