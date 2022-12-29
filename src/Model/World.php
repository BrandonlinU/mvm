<?php

namespace App\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class World
{
    private string $name;

    private string $path;

    private Collection $behaviourPacks;

    private Collection $resourcesPacks;

    public function __construct(string $name, string $path, array $behaviourPacks, array $resourcesPacks)
    {
        $this->name = $name;
        $this->path = $path;
        $this->behaviourPacks = new ArrayCollection($behaviourPacks);
        $this->resourcesPacks = new ArrayCollection($resourcesPacks);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getBehaviourPacks(): array
    {
        return $this->behaviourPacks->toArray();
    }

    public function hasBehaviorPack(ContentPack $behaviorPack): bool
    {
        return isset($this->behaviourPacks[$behaviorPack->getUuid()]);
    }

    public function addBehaviorPack(ContentPack $behaviorPack): void
    {
        $this->behaviourPacks[$behaviorPack->getUuid()] = $behaviorPack;
    }

    public function removeBehaviorPack(ContentPack $behaviorPack): void
    {
        unset($this->behaviourPacks[$behaviorPack->getUuid()]);
    }

    public function getResourcePacks(): array
    {
        return $this->resourcesPacks->toArray();
    }

    public function hasResourcePack(ContentPack $resourcePack): bool
    {
        return isset($this->resourcesPacks[$resourcePack->getUuid()]);
    }

    public function addResourcePack(ContentPack $resourcePack): void
    {
        $this->resourcesPacks[$resourcePack->getUuid()] = $resourcePack;
    }

    public function removeResourcePack(ContentPack $resourcePack): void
    {
        unset($this->resourcesPacks[$resourcePack->getUuid()]);
    }
}