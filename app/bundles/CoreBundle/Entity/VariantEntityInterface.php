<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

interface VariantEntityInterface
{
    /**
     * @return int|null
     */
    public function getId();

    public function getVariantParent(): ?self;

    /**
     * @return $this
     */
    public function setVariantParent(?self $parent = null): static;

    public function removeVariantParent(): void;

    public function getVariantChildren(): ArrayCollection|Collection;

    /**
     * @return $this
     */
    public function addVariantChild(self $child): static;

    public function removeVariantChild(self $child): void;

    /**
     * @return array<mixed>
     */
    public function getVariantSettings(): array;

    public function getVariantStartDate(): mixed;

    /**
     * @return array<int, mixed>
     */
    public function getVariants(): array;

    public function isVariant(bool $isChild = false): bool;
}
