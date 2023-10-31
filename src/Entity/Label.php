<?php

declare(strict_types=1);

namespace App\Entity;
use App\Enum\TextAlignmentEnum;

class Label 
{
    private Item|Collection|array|null $object = null;
    private ?array $fields = null;
    private ?string $labelSize = null;
    private ?string $orientation = null;
    private int $fontSize = 12;
    private int $qrSize = 25;
    private string $textAlignment = TextAlignmentEnum::TEXT_ALIGN_LEFT;

    public function getObject(): Item|Collection|array|null
    {
        return $this->object;
    }
    
    public function setObject(Item|Collection|array|null $object): self
    {
        $this->object = $object;
        
        return $this;
    }

    public function getFields(): array|null
    {
        return $this->fields;
    }
    
    public function setFields(array|null $fields): self
    {
        $this->fields = $fields;
        
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

    public function getFontSize(): int
    {
        return $this->fontSize;
    }

    public function setFontSize(int $fontSize): self
    {
        $this->fontSize = $fontSize;

        return $this;
    }

    public function getQrSize(): int
    {
        return $this->qrSize;
    }

    public function setQrSize(int $qrSize): self
    {
        $this->qrSize = $qrSize;

        return $this;
    }

    public function getTextAlignment(): string
    {
        return $this->textAlignment;
    }

    public function setTextAlignment(string $textAlignment): self
    {
        $this->textAlignment = $textAlignment;

        return $this;
    }
}

