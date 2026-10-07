<?php

namespace App\Modules\Transactions\Policies;

use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;

/** Quien puede ver el portafolio, puede gestionar sus transacciones. */
class TransactionPolicy
{
    public function create(User $user, Portfolio $portfolio): bool
    {
        return $user->can('view', $portfolio);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->can('view', $transaction->portfolio);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }
}
