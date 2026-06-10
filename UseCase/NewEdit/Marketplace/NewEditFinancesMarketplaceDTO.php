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

namespace BaksDev\Finances\UseCase\NewEdit\Marketplace;

use BaksDev\Core\Type\UidType\UidType;
use BaksDev\Finances\Entity\Event\Marketplace\FinancesMarketplaceInterface;
use BaksDev\Payment\Type\Id\PaymentUid;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see FinancesMarketplace */
final class NewEditFinancesMarketplaceDTO implements FinancesMarketplaceInterface
{

    /** Идентификатор внешнего сервиса */
    #[Assert\NotBlank]
    private string $identifier;

    /** Идентификатор заказа или услуги */
    #[Assert\NotBlank]
    private string $number;

    /** ID токена маркетплейса */
    #[Assert\NotBlank]
    private Uuid|null $token = null;

    /** Тип оплаты */
    #[Assert\NotBlank]
    private PaymentUid $payment;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(mixed $identifier): self
    {
        $this->identifier = (string) $identifier;
        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(mixed $number): self
    {
        $this->number = (string) $number;
        return $this;
    }

    public function getToken(): ?Uuid
    {
        return $this->token;
    }

    public function setToken(?Uuid $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getPayment(): PaymentUid
    {
        return $this->payment;
    }

    public function setPayment(PaymentUid $payment): self
    {
        $this->payment = $payment;
        return $this;
    }
}