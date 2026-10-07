<?php

namespace App\Modules\Portfolios\Policies;

use App\Modules\Clients\Models\Client;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Users\Models\User;

/** Quien puede ver al cliente ve sus portafolios; quien lo gestiona, los gestiona. */
class PortfolioPolicy
{
    public function create(User $user, Client $client): bool
    {
        return $user->can('update', $client);
    }

    public function view(User $user, Portfolio $portfolio): bool
    {
        return $user->can('view', $portfolio->client);
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return $user->can('update', $portfolio->client);
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return $this->update($user, $portfolio);
    }
}
