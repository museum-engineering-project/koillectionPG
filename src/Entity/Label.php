<?php

declare(strict_types=1);

namespace App\Entity;

class Label 
{
    private ?Item $item = null;
    private ?string $labelSize = null;

    public function getItem(): ?Item
    {
        return $this->item;
    }
    
    public function setItem(?Item $item): self
    {
        $this->item = $item;
        
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
}

