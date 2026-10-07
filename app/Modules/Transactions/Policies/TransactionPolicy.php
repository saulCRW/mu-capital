<?php

namespace App\Modules\Transactions\Policies;

use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;

/** Quien gestiona el portafolio gestiona sus transacciones (el usuario cliente solo las consulta). */
class TransactionPolicy
{
    public function create(User $user, Portfolio $portfolio): bool
    {
        return $user->can('update', $portfolio);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->can('update', $transaction->portfolio);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }
}
