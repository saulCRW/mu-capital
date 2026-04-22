import { createContext, useContext, useEffect, useState } from 'react';
import api from '../services/api';

interface Usuario {
  id: number;
  nombre: string;
  apellido: string;
  email: string;
  rol: { nombre: string };
}

interface AuthContextType {
  usuario: Usuario | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (data: any) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType>({} as AuthContextType);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [usuario, setUsuario] = useState<Usuario | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (token) {
      api.get('/me')
        .then((res) => setUsuario(res.data))
        .catch(() => localStorage.removeItem('token'))
        .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  const login = async (email: string, password: string) => {
    const res = await api.post('/login', { email, password });
    localStorage.setItem('token', res.data.token);
    setUsuario(res.data.usuario);
  };

  const register = async (data: any) => {
    const res = await api.post('/register', data);
    localStorage.setItem('token', res.data.token);
    setUsuario(res.data.usuario);
  };

  const logout = async () => {
    await api.post('/logout').catch(() => {});
    localStorage.removeItem('token');
    setUsuario(null);
  };

  return (
    <AuthContext.Provider value={{ usuario, loading, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
