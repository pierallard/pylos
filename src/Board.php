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

    private function bowlAt($x, $y, $z) {
        if (!array_key_exists(serialize([$x, $y, $z]), $this->positions)) {
            return null;
        }

        return $this->positions[serialize([$x, $y, $z])];
    }

    private function normalizeBowl($x, $y, $z) {
        return ['x' => $x, 'y' => $y, 'z' => $z, 'value' => $this->bowlAt($x, $y, $z)];
    }

    public function getAvailablePositions($currentBowl)
    {
        $result = [];
        foreach (array_keys($this->positions) as $position) {
            [$x, $y, $z] = unserialize($position);
            if ($this->positions[$position] === null) {
                if (($currentBowl === null) || ($z > $currentBowl->z)) {
                    if ($z === 0) {
                        $result[] = $this->normalizeBowl($x, $y, $z);
                    } else if ($this->isBowlUnder($x, $y, $z)) {
                        $result[] = $this->normalizeBowl($x, $y, $z);
                    }
                }
            }
        }

        return $result;
    }

    private function isBowlUnder($x, $y, $z) {
        return (($this->bowlAt($x, $y, $z - 1) !== null)
            && ($this->bowlAt($x + 1, $y, $z - 1) !== null)
            && ($this->bowlAt($x + 1, $y + 1, $z - 1) !== null)
            && ($this->bowlAt($x, $y + 1, $z - 1) !== null));
    }

    public function addBowl($playerId, $position)
    {
        $this->positions[serialize([intval($position->x), intval($position->y), intval($position->z)])] = $playerId;
    }

    public function removeBowl($position)
    {
        $result = $this->positions[serialize([intval($position->x), intval($position->y), intval($position->z)])];
        $this->positions[serialize([intval($position->x), intval($position->y), intval($position->z)])] = null;

        return $result;
    }

    public function normalize()
    {
        $result = [];
        foreach (array_keys($this->positions) as $position) {
            [$x, $y, $z] = unserialize($position);
            $result[] = $this->normalizeBowl($x, $y, $z);
        }

        return $result;
    }

    public function getAvailableBowls($playerId)
    {
        $result = [];
        $result[] = $this->normalizeBowl(-1, -1, -1);
        foreach (array_keys($this->positions) as $position) {
            [$x, $y, $z] = unserialize($position);
            if ($this->positions[$position] !== null) {
                if (($this->bowlAt($x, $y, $z) === $playerId) &&
                    ($this->isMovable($x, $y, $z))) {
                    $result[] = $this->normalizeBowl($x, $y, $z);
                }
            }
        }

        return $result;
    }

    private function isMovable($x, $y, $z)
    {
        return !$this->isSupport($x, $y, $z);
    }

    private function isSupport($x, $y, $z)
    {
        return (($this->bowlAt($x - 1, $y - 1, $z + 1) !== null) ||
            ($this->bowlAt($x - 1, $y, $z + 1) !== null) ||
            ($this->bowlAt($x, $y - 1, $z + 1) !== null) ||
            ($this->bowlAt($x, $y, $z + 1) !== null));
    }

    public function moveBowl($currentBowl, $position)
    {
        $this->addBowl($this->removeBowl($currentBowl), $position);
    }
}
