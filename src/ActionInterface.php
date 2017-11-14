<?php

namespace MyApp;

interface ActionInterface
{
    public function do(Board &$board);

    public function undo(Board &$board);

    public function normalize(): array;
}
