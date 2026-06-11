<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Finances\UseCase\NewEdit;

use BaksDev\Finances\Entity\Event\FinancesEventInterface;
use BaksDev\Finances\Type\Event\FinancesEventUid;
use BaksDev\Finances\UseCase\NewEdit\Invariable\NewEditFinancesInvariableDTO;
use BaksDev\Finances\UseCase\NewEdit\Marketplace\NewEditFinancesMarketplaceDTO;
use BaksDev\Finances\UseCase\NewEdit\Order\NewEditFinancesOrderDTO;
use BaksDev\Finances\UseCase\NewEdit\Product\FinancesProductDTO;
use BaksDev\Reference\Money\Type\Money;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;

/** @see FinancesEvent */
final class NewEditFinancesDTO implements FinancesEventInterface
{

    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    private ?FinancesEventUid $id = null;

    /** FinancesInvariable */
    #[Assert\Valid]
    private ?NewEditFinancesInvariableDTO $invariable = null;

    /** FinancesOrder */
    #[Assert\Valid]
    private ?NewEditFinancesOrderDTO $ord = null;

    /** FinancesMarketplace */
    #[Assert\Valid]
    private ?NewEditFinancesMarketplaceDTO $marketpace = null;

    /** FinancesProduct */
    private ?FinancesProductDTO $product = null;

    /** Стоимость */
    #[Assert\NotBlank]
    private Money $price;

    /** Комментарий */
    private ?string $comment;

    public function __construct()
    {
        $this->invariable = new NewEditFinancesInvariableDTO();
        $this->ord = new NewEditFinancesOrderDTO();
        $this->marketpace = new NewEditFinancesMarketplaceDTO();
        $this->product = new FinancesProductDTO();
    }

    /**
     * Идентификатор события
     */
    public function getEvent(): ?FinancesEventUid
    {
        return $this->id;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function setPrice(Money $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getInvariable(): ?NewEditFinancesInvariableDTO
    {
        return $this->invariable;
    }

    public function getOrd(): ?NewEditFinancesOrderDTO
    {
        return $this->ord;
    }

    public function getMarketpace(): ?NewEditFinancesMarketplaceDTO
    {
        return $this->marketpace;
    }

    public function getProduct(): ?FinancesProductDTO
    {
        return $this->product;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }
}