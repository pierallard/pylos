<?php

namespace Pylos\Actions;

use Pylos\Board;

interface ActionInterface
{
    public function do(Board &$board): bool;

    public function undo(Board &$board);

    public function normalize(): array;

    public function getPlayerId(): int;
}
