<?php

namespace App\Modules\Prices\Services\Providers;

use RuntimeException;

/** Un proveedor de mercado no respondió o no tiene el dato pedido. */
class ProviderException extends RuntimeException {}
