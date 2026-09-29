import api from './api';
import type { Transaccion } from '../models/types';

export const transaccionService = {
  getAll: () => api.get<Transaccion[]>('/transacciones').then(r => r.data),
  getById: (id: number) => api.get<Transaccion>(`/transacciones/${id}`).then(r => r.data),
  create: (data: Partial<Transaccion>) => api.post<Transaccion>('/transacciones', data).then(r => r.data),
  update: (id: number, data: Partial<Transaccion>) => api.put<Transaccion>(`/transacciones/${id}`, data).then(r => r.data),
  remove: (id: number) => api.delete(`/transacciones/${id}`).then(r => r.data),
};
