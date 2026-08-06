import React, { useEffect, useState } from 'react';
import { IonContent, IonPage, IonIcon } from '@ionic/react';
import { bookOutline, schoolOutline, newspaperOutline, libraryOutline, heart, searchOutline, arrowBackOutline } from 'ionicons/icons';
// @ts-ignore
import api from '../../../services/api'; 
import './MiBiblioteca.css';

const MiBiblioteca: React.FC = () => {
  const [usuario, setUsuario] = useState<any>(null);
  const [favoritos, setFavoritos] = useState<any[]>([]);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    const userData = sessionStorage.getItem('usuario');
    if (userData) {
      setUsuario(JSON.parse(userData));
    }

    const abortController = new AbortController();
    let isMounted = true;

    const cargarFavoritos = async () => {
      try {
        const token = sessionStorage.getItem('token');
        const res = await api.get('usuario/favoritos', {
          headers: { Authorization: `Bearer ${token}` },
          signal: abortController.signal 
        });
        
        if (res.data?.success && isMounted) {
          setFavoritos(res.data.data);
        }
      } catch (err: any) {
        if (err.name !== 'CanceledError' && err.message !== 'canceled') {
          console.error("Error al cargar biblioteca personal:", err);
        }
      } finally {
        if (isMounted && !abortController.signal.aborted) {
          setLoading(false);
        }
      }
    };

    cargarFavoritos();

    return () => {
      isMounted = false;
      abortController.abort(); 
    };
  }, []);

  const obtenerIconoPorTipo = (tipo: string) => {
    if (tipo === 'Tesis') return schoolOutline;
    if (tipo === 'Revista / Artículo Científico') return newspaperOutline;
    if (tipo === 'Enciclopedia / Diccionario') return libraryOutline;
    return bookOutline;
  };

  return (
    <IonPage>
      {/* NAVBAR GLOBAL UNIFICADO CON AJUSTE DE COLUMNAS SIMÉTRICAS */}
      <div className="biblioteca-navbar" style={{ gridTemplateColumns: '1fr 1fr 1fr' }}>
        <div className="navbar-left" style={{ justifySelf: 'start', display: 'flex', alignItems: 'center' }}>
          {/* 🏛️ CORRECCIÓN: Regresar a la interfaz anterior utilizando el historial del navegador */}
          <button className="navbar-back-arrow-btn" onClick={() => window.history.back()} title="Regresar">
            <IonIcon icon={arrowBackOutline} />
          </button>
          <span className="university-logo-text">UPVE</span>
          <span className="university-brand-sub">BIBLIOTECA</span>
        </div>

        <div className="navbar-center-links">
          <span className="nav-top-link" onClick={() => window.location.href = '/portal/inicio'}>Inicio</span>
          <span className="nav-top-link" onClick={() => window.location.href = '/portal/explorar'}>Explorar</span>
          <span className="nav-top-link active" onClick={() => window.location.href = '/portal/mibiblioteca'}>Mi Biblioteca</span>
        </div>

        <div className="navbar-right">
          <div className="navbar-avatar-btn" onClick={() => window.location.href = '/portal/perfil'}>
            {usuario?.FotoPerfil ? (
              <img src={usuario.FotoPerfil} alt="Avatar" className="navbar-avatar-img" />
            ) : (
              <span className="navbar-avatar-letter">
                {usuario ? usuario.NombreUsuario.charAt(0).toUpperCase() : 'U'}
              </span>
            )}
          </div>
        </div>
      </div>

      {/* 🏛️ CORRECCIÓN: Fondo uniforme de la aplicación */}
      <IonContent className="portal-bg" fullscreen>
        <div className="biblioteca-layout-wrapper">
          <div className="biblioteca-header-section">
            <h1 className="biblioteca-main-title">Mi Biblioteca Personal</h1>
            <p className="biblioteca-sub-title">Tu colección privada de fravoritos guardadas del repositorio oficial de la universidad.</p>
          </div>

          {loading ? (
            <div className="biblioteca-loader-box">
              <div className="cervantes-spinner"></div>
              <p>Consultando tu estante privado...</p>
            </div>
          ) : favoritos.length === 0 ? (
            <div className="biblioteca-empty-state">
              <div className="empty-heart-icon-box">
                <IonIcon icon={heart} className="empty-heart-icon" />
              </div>
              <h3>Tu estante está vacío</h3>
              <p>Aún no has agregado nada a tus favoritos. Explora el catálogo general y presiona el corazón en los libros o tesis de tu interés para verlos reflejados aquí.</p>
              <button className="btn-biblioteca-explorar" onClick={() => window.location.href = '/portal/explorar'}>
                <IonIcon icon={searchOutline} style={{ marginRight: '8px', verticalAlign: 'middle' }} />
                Explorar Catálogo
              </button>
            </div>
          ) : (
            <div className="biblioteca-grid-container">
              {favoritos.map((item: any) => (
                <div 
                  key={item.id} 
                  className="biblioteca-book-card"
                  onClick={() => window.location.href = `/portal/recurso/${item.id}`}
                >
                  <div className="book-card-image-box">
                    {item.Imagen_url ? (
                      <img src={item.Imagen_url} alt={item.Titulo} className="book-card-img" />
                    ) : (
                      <div className="book-card-fallback">
                        <IonIcon icon={obtenerIconoPorTipo(item.TipoRecurso)} />
                        <span>{item.TipoRecurso}</span>
                      </div>
                    )}
                    <div className="book-card-heart-badge">
                      <IonIcon icon={heart} />
                    </div>
                  </div>
                  
                  <div className="book-card-info-box">
                    <span className="book-card-tag">{item.TemaRecurso || 'General'}</span>
                    <h3 className="book-card-title-text" title={item.Titulo}>{item.Titulo}</h3>
                    <p className="book-card-author-text">{item.Autor || 'Autor Institucional'}</p>
                    <div className="book-card-footer">
                      <span className="book-card-type-text">{item.TipoRecurso}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </IonContent>
    </IonPage>
  );
};

export default MiBiblioteca;