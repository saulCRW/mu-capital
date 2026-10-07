<?php

namespace App\Modules\Portfolios\Policies;

use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Users\Models\User;

/** Cada usuario crea y gestiona sus propios portafolios; nadie más los ve (ni el superadmin). */
class PortfolioPolicy
{
    public function create(User $user): bool
    {
        return $user->rol === User::ROL_USUARIO;
    }

    public function view(User $user, Portfolio $portfolio): bool
    {
        return $portfolio->user_id === $user->id;
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return $this->view($user, $portfolio);
    }
}
