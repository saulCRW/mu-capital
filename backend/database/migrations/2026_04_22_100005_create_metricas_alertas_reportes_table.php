<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metricas_riesgo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('portafolio_id')->constrained('portafolios')->onDelete('cascade');
            $table->date('fecha');
            $table->decimal('valor_en_riesgo', 20, 8)->nullable();
            $table->decimal('valor_en_riesgo_99', 20, 8)->nullable();
            $table->decimal('volatilidad', 10, 6)->nullable();
            $table->decimal('beta', 8, 4)->nullable();
            $table->decimal('alpha', 8, 4)->nullable();
            $table->decimal('sharpe_ratio', 8, 4)->nullable();
            $table->decimal('sortino_ratio', 8, 4)->nullable();
            $table->decimal('max_drawdown', 8, 4)->nullable();
            $table->decimal('correlacion_btc', 8, 4)->nullable();
            $table->decimal('correlacion_sp500', 8, 4)->nullable();
            $table->decimal('nivel_diversific', 5, 2)->nullable();
            $table->string('periodo_calculo', 10)->default('30d');
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['portafolio_id', 'fecha', 'periodo_calculo'], 'uq_portafolio_fecha_periodo');
        });

        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('activo_id')->nullable()->constrained('activos');
            $table->foreignId('portafolio_id')->nullable()->constrained('portafolios');
            $table->string('tipo', 50);
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->json('condicion');
            $table->string('canal', 20)->default('app');
            $table->text('webhook_url')->nullable();
            $table->boolean('activa')->default(true);
            $table->boolean('disparada')->default(false);
            $table->dateTime('ultima_disparo')->nullable();
            $table->integer('veces_disparada')->default(0);
            $table->dateTime('expira_en')->nullable();
            $table->timestamps();
        });

        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('portafolio_id')->nullable()->constrained('portafolios');
            $table->string('tipo', 50);
            $table->string('nombre', 200);
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->string('formato', 10)->default('pdf');
            $table->string('estado', 20)->default('pendiente');
            $table->text('url_descarga')->nullable();
            $table->json('parametros')->nullable();
            $table->integer('tamanio_bytes')->nullable();
            $table->dateTime('generado_en')->nullable();
            $table->dateTime('expira_en')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes');
        Schema::dropIfExists('alertas');
        Schema::dropIfExists('metricas_riesgo');
    }
};
