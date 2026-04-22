-- ============================================================
-- Base de Datos: MU Capital - Fondo de Inversión
-- Motor: MySQL 8.0+ / MariaDB (XAMPP / phpMyAdmin)
-- ============================================================

CREATE DATABASE IF NOT EXISTS mu_capital
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mu_capital;

-- ============================================================
-- TABLA: roles
-- ============================================================
CREATE TABLE roles (
    id          INT             NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(50)     NOT NULL UNIQUE,
    descripcion TEXT,
    permisos    JSON            NOT NULL,
    activo      TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: usuarios
-- ============================================================
CREATE TABLE usuarios (
    id               INT          NOT NULL AUTO_INCREMENT,
    rol_id           INT          NOT NULL,
    nombre           VARCHAR(100) NOT NULL,
    apellido         VARCHAR(100) NOT NULL,
    email            VARCHAR(255) NOT NULL UNIQUE,
    password_hash    VARCHAR(255) NOT NULL,
    telefono         VARCHAR(20),
    pais             VARCHAR(100),
    foto_perfil_url  TEXT,
    activo           TINYINT(1)   NOT NULL DEFAULT 1,
    email_verificado TINYINT(1)   NOT NULL DEFAULT 0,
    ultimo_login     DATETIME,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: perfil_riesgo_detalle
-- ============================================================
CREATE TABLE perfil_riesgo_detalle (
    id                 INT          NOT NULL AUTO_INCREMENT,
    usuario_id         INT          NOT NULL,
    nivel_riesgo       VARCHAR(20)  NOT NULL,   -- 'conservador', 'moderado', 'agresivo'
    horizonte_temporal INT,                      -- en meses
    tolerancia_perdida DECIMAL(5,2),             -- % máximo tolerable de pérdida
    objetivo_retorno   DECIMAL(5,2),             -- % de retorno esperado anual
    cuestionario_resp  JSON,
    puntuacion         INT,
    evaluado_en        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: activos
-- ============================================================
CREATE TABLE activos (
    id           INT          NOT NULL AUTO_INCREMENT,
    simbolo      VARCHAR(20)  NOT NULL UNIQUE,   -- BTC, ETH, USDT, etc.
    nombre       VARCHAR(150) NOT NULL,
    tipo         VARCHAR(50)  NOT NULL,           -- 'crypto', 'accion', 'etf', 'bono', 'commodity', 'fiat'
    descripcion  TEXT,
    exchange     VARCHAR(100),                    -- Binance, NYSE, etc.
    moneda_base  VARCHAR(10)  NOT NULL DEFAULT 'USD',
    decimales    INT          NOT NULL DEFAULT 8,
    activo       TINYINT(1)   NOT NULL DEFAULT 1,
    logo_url     TEXT,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: historial_precios
-- ============================================================
CREATE TABLE historial_precios (
    id             BIGINT        NOT NULL AUTO_INCREMENT,
    activo_id      INT           NOT NULL,
    precio         DECIMAL(24,8) NOT NULL,
    precio_max     DECIMAL(24,8),
    precio_min     DECIMAL(24,8),
    volumen        DECIMAL(30,8),
    market_cap     DECIMAL(30,2),
    variacion_24h  DECIMAL(8,4),                 -- % de cambio en 24h
    fuente         VARCHAR(50),                  -- 'binance', 'coingecko', 'manual'
    intervalo      VARCHAR(10)   NOT NULL DEFAULT '1d',  -- '1m','5m','1h','4h','1d'
    timestamp      DATETIME      NOT NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_activo_ts_intervalo (activo_id, timestamp, intervalo),
    FOREIGN KEY (activo_id) REFERENCES activos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: portafolios
-- ============================================================
CREATE TABLE portafolios (
    id                 INT           NOT NULL AUTO_INCREMENT,
    usuario_id         INT           NOT NULL,
    nombre             VARCHAR(150)  NOT NULL,
    descripcion        TEXT,
    estrategia         VARCHAR(50),              -- 'growth', 'income', 'balanced', 'especulativo'
    moneda             VARCHAR(10)   NOT NULL DEFAULT 'USD',
    valor_inicial      DECIMAL(20,2) NOT NULL DEFAULT 0,
    valor_actual       DECIMAL(20,2) NOT NULL DEFAULT 0,
    rendimiento_total  DECIMAL(10,4) DEFAULT 0,  -- % total
    benchmark          VARCHAR(50),              -- BTC, SP500, etc.
    activo             TINYINT(1)    NOT NULL DEFAULT 1,
    es_publico         TINYINT(1)    NOT NULL DEFAULT 0,
    color              VARCHAR(7),               -- color hex para UI (#06B6D4)
    created_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activos dentro de cada portafolio
CREATE TABLE portafolio_activos (
    id              INT           NOT NULL AUTO_INCREMENT,
    portafolio_id   INT           NOT NULL,
    activo_id       INT           NOT NULL,
    cantidad        DECIMAL(30,8) NOT NULL,
    precio_promedio DECIMAL(24,8),
    peso_objetivo   DECIMAL(5,2),                -- % objetivo en el portafolio
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_portafolio_activo (portafolio_id, activo_id),
    FOREIGN KEY (portafolio_id) REFERENCES portafolios(id) ON DELETE CASCADE,
    FOREIGN KEY (activo_id)     REFERENCES activos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: transacciones
-- ============================================================
CREATE TABLE transacciones (
    id              INT           NOT NULL AUTO_INCREMENT,
    portafolio_id   INT           NOT NULL,
    activo_id       INT           NOT NULL,
    usuario_id      INT           NOT NULL,
    tipo            VARCHAR(20)   NOT NULL,  -- 'compra','venta','deposito','retiro','dividendo','fee'
    estado          VARCHAR(20)   NOT NULL DEFAULT 'pendiente',  -- 'pendiente','completada','cancelada','fallida'
    cantidad        DECIMAL(30,8) NOT NULL,
    precio_unitario DECIMAL(24,8) NOT NULL,
    monto_total     DECIMAL(20,2) NOT NULL,
    comision        DECIMAL(20,8) DEFAULT 0,
    moneda          VARCHAR(10)   NOT NULL DEFAULT 'USD',
    exchange        VARCHAR(100),
    tx_hash         VARCHAR(255),             -- hash blockchain (si aplica)
    notas           TEXT,
    ejecutada_en    DATETIME,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (portafolio_id) REFERENCES portafolios(id),
    FOREIGN KEY (activo_id)     REFERENCES activos(id),
    FOREIGN KEY (usuario_id)    REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: metricas_riesgo
-- ============================================================
CREATE TABLE metricas_riesgo (
    id                 BIGINT        NOT NULL AUTO_INCREMENT,
    portafolio_id      INT           NOT NULL,
    fecha              DATE          NOT NULL,
    valor_en_riesgo    DECIMAL(20,8),            -- VaR 95%
    valor_en_riesgo_99 DECIMAL(20,8),            -- VaR 99%
    volatilidad        DECIMAL(10,6),            -- desviación estándar anualizada
    beta               DECIMAL(8,4),
    alpha              DECIMAL(8,4),
    sharpe_ratio       DECIMAL(8,4),
    sortino_ratio      DECIMAL(8,4),
    max_drawdown       DECIMAL(8,4),             -- máxima caída desde el pico
    correlacion_btc    DECIMAL(8,4),
    correlacion_sp500  DECIMAL(8,4),
    nivel_diversific   DECIMAL(5,2),             -- score 0-100
    periodo_calculo    VARCHAR(10)   NOT NULL DEFAULT '30d',  -- '7d','30d','90d','1y'
    created_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_portafolio_fecha_periodo (portafolio_id, fecha, periodo_calculo),
    FOREIGN KEY (portafolio_id) REFERENCES portafolios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: alertas
-- ============================================================
CREATE TABLE alertas (
    id              INT          NOT NULL AUTO_INCREMENT,
    usuario_id      INT          NOT NULL,
    activo_id       INT,
    portafolio_id   INT,
    tipo            VARCHAR(50)  NOT NULL,   -- 'precio_sobre','precio_bajo','variacion_porcentaje','drawdown','rebalanceo'
    nombre          VARCHAR(150) NOT NULL,
    descripcion     TEXT,
    condicion       JSON         NOT NULL,   -- {"campo":"precio","operador":">","valor":50000}
    canal           VARCHAR(20)  NOT NULL DEFAULT 'app',  -- 'app','email','sms','webhook'
    webhook_url     TEXT,
    activa          TINYINT(1)   NOT NULL DEFAULT 1,
    disparada       TINYINT(1)   NOT NULL DEFAULT 0,
    ultima_disparo  DATETIME,
    veces_disparada INT          NOT NULL DEFAULT 0,
    expira_en       DATETIME,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (usuario_id)    REFERENCES usuarios(id),
    FOREIGN KEY (activo_id)     REFERENCES activos(id),
    FOREIGN KEY (portafolio_id) REFERENCES portafolios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: reportes
-- ============================================================
CREATE TABLE reportes (
    id              INT          NOT NULL AUTO_INCREMENT,
    usuario_id      INT          NOT NULL,
    portafolio_id   INT,
    tipo            VARCHAR(50)  NOT NULL,   -- 'rendimiento','riesgo','transacciones','fiscal','comparativo'
    nombre          VARCHAR(200) NOT NULL,
    periodo_inicio  DATE         NOT NULL,
    periodo_fin     DATE         NOT NULL,
    formato         VARCHAR(10)  NOT NULL DEFAULT 'pdf',   -- 'pdf','xlsx','csv','json'
    estado          VARCHAR(20)  NOT NULL DEFAULT 'pendiente',  -- 'pendiente','generando','listo','error'
    url_descarga    TEXT,
    parametros      JSON,
    tamanio_bytes   INT,
    generado_en     DATETIME,
    expira_en       DATETIME,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (usuario_id)    REFERENCES usuarios(id),
    FOREIGN KEY (portafolio_id) REFERENCES portafolios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ÍNDICES DE RENDIMIENTO
-- ============================================================
CREATE INDEX idx_usuarios_rol          ON usuarios(rol_id);
CREATE INDEX idx_portafolios_usuario   ON portafolios(usuario_id);
CREATE INDEX idx_transac_portafolio    ON transacciones(portafolio_id);
CREATE INDEX idx_transac_usuario       ON transacciones(usuario_id);
CREATE INDEX idx_transac_fecha         ON transacciones(ejecutada_en);
CREATE INDEX idx_historial_activo_ts   ON historial_precios(activo_id, timestamp);
CREATE INDEX idx_metricas_portaf_fecha ON metricas_riesgo(portafolio_id, fecha);
CREATE INDEX idx_alertas_usuario       ON alertas(usuario_id);
CREATE INDEX idx_alertas_activo        ON alertas(activo_id);
CREATE INDEX idx_reportes_usuario      ON reportes(usuario_id);
CREATE INDEX idx_perfil_usuario        ON perfil_riesgo_detalle(usuario_id);
