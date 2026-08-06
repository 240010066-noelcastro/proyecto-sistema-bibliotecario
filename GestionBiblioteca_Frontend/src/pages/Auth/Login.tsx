import React, { useState, useEffect } from 'react';
import { IonContent, IonPage, IonIcon } from '@ionic/react';
import { GoogleOAuthProvider, GoogleLogin } from '@react-oauth/google'; 
// @ts-ignore
import api from '../../services/api';
import './Login.css';

const Login: React.FC = () => {
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  // Redirección si ya hay sesión iniciada
  useEffect(() => {
    const token = sessionStorage.getItem('token');
    const usuarioStr = sessionStorage.getItem('usuario');
    if (token && usuarioStr) {
      const usuario = JSON.parse(usuarioStr);
      // Validamos directamente contra el Rol_ID numérico del objeto usuario verificado
      if (usuario.Rol_ID === 1) window.location.href = '/dashboard';
      else window.location.href = '/portal';
    }
  }, []);

  // 2. LOGIN CON GOOGLE (Alumnos) - CORREGIDO PARA SEPARAR APELLIDOS Y DAR FORMATO Nombre Propio
  const handleGoogleSuccess = async (credentialResponse: any) => {
    setErrorMsg('');
    setLoading(true);

    try {
      const token = credentialResponse.credential;
      const payload = JSON.parse(atob(token.split('.')[1]));
      
      // FUNCIÓN AUXILIAR: Convierte textos en mayúsculas a Formato Propio (Ej: NOEL EDUARDO -> Noel Eduardo)
      const formatTitleCase = (str: string) => {
        return str
          .toLowerCase()
          .split(' ')
          .map(word => word.charAt(0).toUpperCase() + word.slice(1))
          .join(' ');
      };

      // Separamos los apellidos que Google manda juntos en family_name
      const apellidosCompletos = (payload.family_name || '').trim().split(' ');
      const primerApellido = apellidosCompletos[0] || '';
      const segundoApellido = apellidosCompletos.slice(1).join(' ') || '';

      // Mapeamos los datos aplicando el formateador de mayúsculas/minúsculas
      const datosEstudiante = {
        correo: payload.email,
        nombre: formatTitleCase(payload.given_name || payload.name || ''),
        apellido_paterno: formatTitleCase(primerApellido),
        apellido_materno: formatTitleCase(segundoApellido)
      };

      const response = await api.post('/login-google', datosEstudiante);

      if (response.data.success) {
        if (response.data.es_nuevo) {
          sessionStorage.setItem('datos_google_temporales', JSON.stringify(response.data.datos_google));
          window.location.href = '/completar-registro'; 
        } else {
        const usuario = response.data.usuario;
        const esAdmin = usuario.Rol_ID == 1;

        sessionStorage.setItem('token', response.data.token);
        sessionStorage.setItem('usuario', JSON.stringify(usuario));
        sessionStorage.setItem('rol', esAdmin ? 'admin' : 'usuario'); // 👈 Cambia aquí: guardamos 'admin' para que el Route Guard no te rebote
        
        if (esAdmin) {
          window.location.href = '/dashboard';
        } else {
          window.location.href = '/portal';
        }
      }
      }
    } catch (error: any) {
      setErrorMsg(error.response?.data?.message || 'Error al autenticar con Google Workspace.');
      setLoading(false);
    }
  };

  return (
    <GoogleOAuthProvider clientId="996518638404-ko9ds937m5lnt72eubph72ri1kc1rq7a.apps.googleusercontent.com">
      <IonPage>
        <IonContent className="unified-login-bg">
          <div className="unified-container">
            <div className="logo-container">
              <img src="/assets/UPVE_Logo.png" alt="Logo UPVE" className="logo" />
            </div>

            <div className="unified-grid">
              <div className="unified-left">
                <h1 className="main-title">Biblioteca Universitaria</h1>
                <p className="main-description">
                  Bienvenido al Sistema de Gestión Bibliotecaria. Un espacio digital diseñado para facilitar el acceso a la información y apoyar el desarrollo académico de nuestra comunidad.
                </p>
              </div>

              <div className="unified-right">
                <div className="form-wrapper">
                  <h2 className="form-title">Iniciar Sesión</h2>
                  <p className="form-subtitle">Ingresa con tus credenciales o cuenta institucional.</p>

                  {errorMsg && <div className="error-alert">{errorMsg}</div>}

                  <div className="google-btn-box" style={{ marginTop: '30px' }}>
                    <GoogleLogin
                      onSuccess={handleGoogleSuccess}
                      onError={() => setErrorMsg('Error de conexión con Google.')}
                      theme="outline"
                      size="large"
                      text="signin_with"
                      width="360"
                    />
                  </div>

                </div>
              </div>
            </div>
          </div>
        </IonContent>
      </IonPage>
    </GoogleOAuthProvider>
  );
};

export default Login;