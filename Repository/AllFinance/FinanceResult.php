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

namespace BaksDev\Finances\Repository\AllFinance;

use BaksDev\Finances\Type\Id\FinancesUid;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\User\Type\Id\UserUid;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/** @see FinanceResult */
final readonly class FinanceResult
{
    public function __construct(
        private string $id,
        private string $usr,
        private string $date,
        private ?string $first,

        private ?string $identifier,
        private ?string $number,

        private ?string $payment_id,
        private ?string $payment_name,


        private int $price,
        private ?string $comment,

        private ?string $order,
        private ?string $posting,
    ) {}

    public function getId(): FinancesUid
    {
        return new FinancesUid($this->id);
    }

    public function getUser(): UserUid
    {
        return new UserUid($this->usr);
    }

    public function getPrice(): Money
    {
        return new Money($this->price, true);
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getPaymentId(): ?string
    {
        return $this->payment_id;
    }

    public function getPaymentName(): ?string
    {
        return $this->payment_name;
    }

    public function getOrderPosting(): ?string
    {
        return $this->posting;
    }

    public function getOrderId(): ?OrderUid
    {
        return $this->order ? new OrderUid($this->order) : null;
    }

    public function getDate(): string
    {
        return new DateTimeImmutable($this->date)->format('d.m.Y');
    }

    public function getFirst(): ?string
    {
        return $this->first ? new DateTimeImmutable($this->first)->format('d.m.Y') : null;
    }


}