import { useState, useEffect } from 'react';
import { usuarioService } from '../services/usuarioService';
import type { UsuarioDetalle } from '../models/types';

/**
 * Carga el detalle completo de un inversionista (portafolios, activos,
 * transacciones y alertas). Pasa `null` para no cargar nada.
 */
export function useUsuarioDetalle(id: number | null) {
  const [usuario, setUsuario] = useState<UsuarioDetalle | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (id === null) {
      setUsuario(null);
      setError(null);
      return;
    }

    let cancelado = false;

    const cargar = async () => {
      try {
        setLoading(true);
        setError(null);
        const data = await usuarioService.getById(id);
        if (!cancelado) setUsuario(data);
      } catch {
        if (!cancelado) {
          setError('No se pudo cargar el detalle del inversionista');
          setUsuario(null);
        }
      } finally {
        if (!cancelado) setLoading(false);
      }
    };

    cargar();
    return () => { cancelado = true; };
  }, [id]);

  return { usuario, loading, error };
}
