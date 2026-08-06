import axios from 'axios';

const api = axios.create({
  // Asegúrate de que esta URL apunte al puerto donde corre tu Laravel
  baseURL: 'http://localhost:8000/api', 
});

// Este es el "Interceptor": Se ejecuta automáticamente antes de cada petición a Laravel
api.interceptors.request.use((config) => {
  
  // Le decimos a Laravel: "Pase lo que pase, respóndeme en formato JSON, no me redirijas"
  config.headers.Accept = 'application/json'; 

  // Busca el token en localStorage O en sessionStorage para no fallar en ningún escenario
  const token = localStorage.getItem('token') || sessionStorage.getItem('token'); 
  
  if (token) {
    // Si lo encuentra, lo adjunta como pase de seguridad
    config.headers.Authorization = `Bearer ${token}`;
  }
  
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Si Laravel responde con 401 (Token expirado o inválido)
    if (error.response && error.response.status === 401) {
      sessionStorage.clear();
      localStorage.clear();
      window.location.href = '/'; // Redirige al login de inmediato
    }
    return Promise.reject(error);
  }
);

export default api;