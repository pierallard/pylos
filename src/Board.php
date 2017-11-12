<?php

namespace MyApp;

class Board
{
    private $positions;
    private const SIZE = 4;

    public function __construct()
    {
        $this->positions = [];
        for ($z = 0; $z < self::SIZE; $z++) {
            for ($x = 0; $x < self::SIZE - $z; $x++) {
                for ($y = 0; $y < self::SIZE - $z; $y++) {
                    $this->positions[serialize([$x,$y,$z])] = null;
                }
            }
        }
    }

    public function getAvailablePositions()
    {
        $result = [];
        foreach (array_keys($this->positions) as $position) {
            [$x, $y, $z] = unserialize($position);
            if ($this->positions[$position] === null) {
                if ($z === 0) {
                    $result[] = [$x, $y, $z];
                } else if (($this->positions[serialize([$x, $y, $z - 1])] !== null)
                    && ($this->positions[serialize([$x + 1, $y, $z - 1])] !== null)
                    && ($this->positions[serialize([$x + 1, $y + 1, $z - 1])] !== null)
                    && ($this->positions[serialize([$x, $y + 1, $z - 1])] !== null)) {
                    $result[] = [$x, $y, $z];
                }
            }
        }

        return $result;
    }

    public function addBowl($playerId, $position)
    {
        echo sprintf("Update (%s, %s, %s) to put %s\n", $position->x, $position->y, $position->z, $playerId);

        $this->positions[serialize([intval($position->x), intval($position->y), intval($position->z)])] = $playerId;


    }

    public function normalize()
    {
        $result = [];
        foreach (array_keys($this->positions) as $position) {
            [$x, $y, $z] = unserialize($position);
            $result[] = [
                'x' => $x,
                'y' => $y,
                'z' => $z,
                'value' => $this->positions[$position]
            ];
        }

        return $result;
    }
}
