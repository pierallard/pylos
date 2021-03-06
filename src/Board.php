<?php

namespace Pylos;

use Pylos\Actions\ActionInterface;
use Pylos\Actions\ActionPick;
use Pylos\Actions\ActionPut;
use Pylos\Actions\ActionRemove;

class Board
{
    private const STATE_WAITING = 'waiting';

    public const SIZE = 4;
    public const STATE_PICK_BOWL = 'pick_bowl';
    public const STATE_PUT_BOWL = 'put_bowl';
    public const STATE_REMOVE = 'remove';

    private $positions;

    private $pickedBowlZ;

    private $state;

    private $currentPlayerId;

    private $removeCounter;

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

        $this->state = self::STATE_WAITING;
    }

    public function start($currentPlayerId) {
        $this->state = self::STATE_PICK_BOWL;
        $this->currentPlayerId = $currentPlayerId;
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


    private function isBowlUnder($x, $y, $z) {
        return (($this->bowlAt($x, $y, $z - 1) !== null)
            && ($this->bowlAt($x + 1, $y, $z - 1) !== null)
            && ($this->bowlAt($x + 1, $y + 1, $z - 1) !== null)
            && ($this->bowlAt($x, $y + 1, $z - 1) !== null));
    }

    public function addBowl($playerId, $x, $y, $z)
    {
        $this->positions[serialize([$x, $y, $z])] = $playerId;
    }

    public function removeBowl($x, $y, $z)
    {
        $result = $this->bowlAt($x, $y, $z);
        $this->positions[serialize([$x, $y, $z])] = null;

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

    public function getPickActions($playerId)
    {
        $result = [];
        $result[] = ActionPick::createBoardPick($playerId);
        foreach (array_keys($this->positions) as $position) {
            [$x, $y, $z] = unserialize($position);
            if ($this->positions[$position] !== null) {
                if (($this->bowlAt($x, $y, $z) === $playerId) &&
                    ($this->isMovable($x, $y, $z))) {
                    $result[] = new ActionPick($playerId, $x, $y, $z);
                }
            }
        }

        return $result;
    }

    public function getRemoveActions($playerId, $counter) {
        $result = [];
        foreach (array_keys($this->positions) as $position) {
            [$x, $y, $z] =unserialize($position);
            if ($this->positions[$position] !== null) {
                if (($this->bowlAt($x, $y, $z) === $playerId) &&
                    !$this->isSupport($x, $y, $z)) {
                    $result[] = new ActionRemove($playerId, $x, $y, $z, $counter);
                }
            }
        }

        return $result;
    }

    public function getPutActions($playerId)
    {
        $result = [];
        foreach (array_keys($this->positions) as $position) {
            [$x, $y, $z] = unserialize($position);
            if ($this->positions[$position] === null) {
                if ($this->pickedBowlZ === ActionPick::BOARD_PICK || $this->pickedBowlZ < $z) {
                    if ($z === 0) {
                        $result[] = new ActionPut($playerId, $x, $y, $z);
                    } else if ($this->isBowlUnder($x, $y, $z)) {
                        $result[] = new ActionPut($playerId, $x, $y, $z);
                    }
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

    public function setPickedBowlZ(?int $z)
    {
        $this->pickedBowlZ = $z;
    }

    /**
     * @return ActionInterface[]
     */
    public function getPossibleActions(): array
    {
        if ($this->state === self::STATE_PICK_BOWL) {
            return $this->getPickActions($this->currentPlayerId);
        } else if ($this->state === self::STATE_PUT_BOWL) {
            return $this->getPutActions($this->currentPlayerId);
        } else if ($this->state === self::STATE_REMOVE) {
            return $this->getRemoveActions($this->currentPlayerId, 2);
        }
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function switchPlayer()
    {
        $this->currentPlayerId = ($this->currentPlayerId + 1) % 2;
    }

    public function getCurrentPlayerId()
    {
        return $this->currentPlayerId;
    }

    public function canUndo($playerId)
    {
        return (
            $this->currentPlayerId === $playerId &&
            (
                ($this->state === self::STATE_PUT_BOWL) ||
                ($this->state === self::STATE_REMOVE)
            )
        );
    }

    public function isSquare($playerId)
    {
        for ($z = 0; $z < self::SIZE - 1; $z++) {
            for ($x = 0; $x < self::SIZE - $z - 1; $x++) {
                for ($y = 0; $y < self::SIZE - $z - 1; $y++) {
                    $color = $this->bowlAt($x, $y, $z);
                    if (($color !== null) &&
                        ($color === $this->bowlAt($x + 1, $y, $z)) &&
                        ($color === $this->bowlAt($x, $y + 1, $z)) &&
                        ($color === $this->bowlAt($x + 1, $y + 1, $z))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function setRemoveCounter($counter)
    {
        $this->removeCounter = $counter;
    }

    public function getRemoveCounter()
    {
        return $this->removeCounter;
    }
}
