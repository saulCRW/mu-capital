<?php

namespace App\Modules\Portfolios\Policies;

use App\Modules\Clients\Models\Client;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Users\Models\User;

/** Quien puede ver al cliente, puede gestionar sus portafolios. */
class PortfolioPolicy
{
    public function create(User $user, Client $client): bool
    {
        return $user->can('view', $client);
    }

    public function view(User $user, Portfolio $portfolio): bool
    {
        return $user->can('view', $portfolio->client);
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
