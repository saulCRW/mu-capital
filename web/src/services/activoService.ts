import api from './api';
import type { Activo } from '../models/types';

export const activoService = {
  getAll: (params?: { tipo?: string; search?: string }) =>
    api.get<Activo[]>('/activos', { params }).then(r => r.data),
  getById: (id: number) => api.get<Activo>(`/activos/${id}`).then(r => r.data),
  create: (data: Partial<Activo>) => api.post<Activo>('/activos', data).then(r => r.data),
  update: (id: number, data: Partial<Activo>) => api.put<Activo>(`/activos/${id}`, data).then(r => r.data),
  remove: (id: number) => api.delete(`/activos/${id}`).then(r => r.data),
};
