export interface Rol {
  id: number;
  nombre: string;
  descripcion?: string;
}

export interface Usuario {
  id: number;
  nombre: string;
  apellido: string;
  email: string;
  telefono?: string;
  pais?: string;
  foto_perfil_url?: string;
  activo: boolean;
  email_verificado: boolean;
  rol: Rol;
  ultimo_login?: string;
}

export interface Activo {
  id: number;
  simbolo: string;
  nombre: string;
  tipo: 'crypto' | 'accion' | 'etf' | 'bono' | 'commodity' | 'fiat';
  descripcion?: string;
  exchange?: string;
  moneda_base?: string;
  logo_url?: string;
  activo: boolean;
}

export interface PortafolioActivo {
  id: number;
  portafolio_id: number;
  activo_id: number;
  cantidad: number;
  precio_promedio: number;
  activo?: Activo;
}

export interface Portafolio {
  id: number;
  usuario_id: number;
  nombre: string;
  descripcion?: string;
  estrategia?: 'growth' | 'income' | 'balanced' | 'especulativo';
  moneda: string;
  benchmark?: string;
  color?: string;
  es_publico: boolean;
  activo: boolean;
  created_at?: string;
  activos?: PortafolioActivo[];
  transacciones?: Transaccion[];
}

export interface Transaccion {
  id: number;
  usuario_id: number;
  portafolio_id: number;
  activo_id: number;
  tipo: 'compra' | 'venta' | 'deposito' | 'retiro' | 'dividendo' | 'fee';
  estado?: string;
  cantidad: number;
  precio_unitario: number;
  monto_total: number;
  comision?: number;
  moneda?: string;
  notas?: string;
  created_at: string;
  activo?: Activo;
  portafolio?: Portafolio;
}

export interface AlertaCondicion {
  campo: string;
  operador: string;
  valor: number;
}

export interface Alerta {
  id: number;
  usuario_id: number;
  activo_id?: number;
  portafolio_id?: number;
  tipo: string;
  nombre: string;
  descripcion?: string;
  condicion: AlertaCondicion[];
  canal?: 'app' | 'email' | 'sms' | 'webhook';
  activa: boolean;
  activada_en?: string;
  expira_en?: string;
  activo?: Activo;
}

// ---------------------------------------------------------------------------
// Panel de administración: respuestas de GET /usuarios y GET /usuarios/{id}
// ---------------------------------------------------------------------------

export interface ActivoEnPortafolio {
  id: number;
  simbolo: string;
  nombre: string;
  tipo: Activo['tipo'];
  logo_url?: string;
  cantidad: number;
  precio_promedio: number;
  valor_invertido: number;
}

export interface PortafolioAdmin {
  id: number;
  usuario_id: number;
  nombre: string;
  descripcion?: string;
  estrategia?: Portafolio['estrategia'];
  moneda: string;
  benchmark?: string;
  color?: string;
  es_publico: boolean;
  activo: boolean;
  created_at?: string;
  valor_invertido: number;
  valor_actual: number;
  rendimiento: number;
  activos: ActivoEnPortafolio[];
}

/** Portafolio tal como viene en el listado de usuarios (sin activos). */
export interface PortafolioResumen {
  id: number;
  nombre: string;
  color?: string;
  valor_invertido: number;
  valor_actual: number;
}

export interface UsuarioResumen extends Usuario {
  portafolios_count: number;
  activos_count: number;
  valor_invertido: number;
  valor_actual: number;
  rendimiento: number;
  portafolios: PortafolioResumen[];
}

export interface UsuarioDetalle extends UsuarioResumen {
  portafolios: PortafolioAdmin[];
  transacciones: Transaccion[];
  alertas: Alerta[];
}
