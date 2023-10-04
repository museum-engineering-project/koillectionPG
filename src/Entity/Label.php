<?php

declare(strict_types=1);

namespace App\Entity;

class Label 
{
    private Item|Collection|null $object = null;
    private ?string $labelSize = null;
    private ?string $orientation = null;

    public function getObject(): Item|Collection|null
    {
        return $this->object;
    }
    
    public function setObject(Item|Collection|null $object): self
    {
        $this->object = $object;
        
        return $this;
    }

    public function getLabelSize(): ?string
    {
        return $this->labelSize;
    }

    public function setLabelSize(?string $labelSize): self
    {
        $this->labelSize = $labelSize;

        return $this;
    }

    public function getOrientation(): ?string
    {
        return $this->orientation;
    }

    public function setOrientation(?string $orientation): self
    {
        $this->orientation = $orientation;

        return $this;
    }
}

