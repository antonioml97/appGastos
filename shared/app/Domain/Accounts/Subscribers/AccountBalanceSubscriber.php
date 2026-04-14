<?php

namespace App\Domain\Accounts\Subscribers;

use App\Domain\Accounts\Projectors\NormalAccountProjector;
use App\Domain\Accounts\Projectors\SavingsAccountProjector;
use App\Domain\Movements\Events\ExpenseCreated;
use App\Domain\Movements\Events\ExpenseDeleted;
use App\Domain\Movements\Events\ExpenseUpdated;
use App\Domain\Movements\Events\IncomeCreated;
use App\Domain\Movements\Events\IncomeDeleted;
use App\Domain\Movements\Events\IncomeUpdated;

/**
 * Suscribe las proyecciones de cuentas a los eventos de movimientos.
 */
class AccountBalanceSubscriber
{
    public function __construct(
        private readonly NormalAccountProjector $normalAccounts,
        private readonly SavingsAccountProjector $savingsAccounts,
    ) {}

    public function subscribe($events): array
    {
        return [
            ExpenseCreated::class => 'handleExpenseCreated',
            ExpenseUpdated::class => 'handleExpenseUpdated',
            ExpenseDeleted::class => 'handleExpenseDeleted',
            IncomeCreated::class => 'handleIncomeCreated',
            IncomeUpdated::class => 'handleIncomeUpdated',
            IncomeDeleted::class => 'handleIncomeDeleted',
        ];
    }

    public function handleExpenseCreated(ExpenseCreated $event): void
    {
        $this->normalAccounts->rebuild();

        if ($this->savingsAccounts->isSavingsCategory($event->categoryName, $event->categoryIcon)) {
            $this->savingsAccounts->addContribution($event->amount);
        }
    }

    public function handleExpenseUpdated(ExpenseUpdated $event): void
    {
        $this->normalAccounts->rebuild();

        if ($this->savingsAccounts->isSavingsCategory($event->previousCategoryName, $event->previousCategoryIcon)) {
            $this->savingsAccounts->removeContribution($event->previousAmount);
        }

        if ($this->savingsAccounts->isSavingsCategory($event->currentCategoryName, $event->currentCategoryIcon)) {
            $this->savingsAccounts->addContribution($event->currentAmount);
        }
    }

    public function handleExpenseDeleted(ExpenseDeleted $event): void
    {
        $this->normalAccounts->rebuild();

        if ($this->savingsAccounts->isSavingsCategory($event->categoryName, $event->categoryIcon)) {
            $this->savingsAccounts->removeContribution($event->amount);
        }
    }

    public function handleIncomeCreated(IncomeCreated $event): void
    {
        $this->normalAccounts->rebuild();
    }

    public function handleIncomeUpdated(IncomeUpdated $event): void
    {
        $this->normalAccounts->rebuild();
    }

    public function handleIncomeDeleted(IncomeDeleted $event): void
    {
        $this->normalAccounts->rebuild();
    }
}
