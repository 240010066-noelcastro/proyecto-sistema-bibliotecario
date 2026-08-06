import React, { useEffect, useState } from 'react';
import { IonContent, IonPage, IonIcon } from '@ionic/react';
import { useParams } from 'react-router-dom';
import { arrowBackOutline, bookOutline, schoolOutline, newspaperOutline, libraryOutline, warningOutline, documentTextOutline, checkmarkCircleOutline, bookmarkOutline, informationCircleOutline, clipboardOutline, heart, heartOutline, tvOutline, easelOutline, wifiOutline } from 'ionicons/icons';
// @ts-ignore
import api from '../../../services/api';
import './DetalleRecurso.css';

const DetalleRecurso: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [recurso, setRecurso] = useState<any>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [isFavorito, setIsFavorito] = useState<boolean>(false); 
  const [toast, setToast] = useState({ show: false, message: '' });
  
  const [textosLegalesGlobales, setTextosLegalesGlobales] = useState({
    libro: '', revista: '', enciclopedia: '', tesis: ''
  });

  const [avisoModal, setAvisoModal] = useState({ show: false, mensaje: '', url: '', isPdf: false });

  useEffect(() => {
    // 🏛️ CAPA DE ULTRA VELOCIDAD: AbortController cancela peticiones duplicadas antes de saturar Laragon
    const abortController = new AbortController();
    let isMounted = true;

    const cargarDatosFicha = async () => {
      try {
        const token = sessionStorage.getItem('token');
        
        // 🏛️ OPTIMIZACIÓN DE CACHÉ: Verificamos si las configuraciones ya existen en el navegador
        const cacheConfig = sessionStorage.getItem('upve_config_catalogo');
        let configData = cacheConfig ? JSON.parse(cacheConfig) : null;

        const promises: Promise<any>[] = [
          api.get(`/usuario/recurso/${id}`, { 
            headers: { Authorization: `Bearer ${token}` },
            signal: abortController.signal
          })
        ];

        // Si no están en caché, las descargamos por única vez
        if (!configData) {
          promises.push(
            api.get('/configuraciones/Catalogo', { 
              headers: { Authorization: `Bearer ${token}` },
              signal: abortController.signal
            })
          );
        }

        const [resRecurso, resConfig] = await Promise.all(promises);

        if (!configData && resConfig?.data?.success) {
          configData = resConfig.data.data || {};
          sessionStorage.setItem('upve_config_catalogo', JSON.stringify(configData));
        }

        if (configData) {
          setTextosLegalesGlobales({
            libro: configData.mensaje_legal_libro || '',
            revista: configData.mensaje_legal_revista || '',
            enciclopedia: configData.mensaje_legal_enciclopedia || '',
            tesis: configData.mensaje_legal_tesis || ''
          });
        }

        if (resRecurso.data?.success && isMounted) {
          setRecurso(resRecurso.data.data);
          if (resRecurso.data.data.is_favorito) {
            setIsFavorito(true);
          }
        }
      } catch (err: any) {
        if (err.name !== 'CanceledError' && err.message !== 'canceled') {
          setToast({ show: true, message: 'Error al conectar con el repositorio de la biblioteca.' });
        }
      } finally {
        if (isMounted && !abortController.signal.aborted) {
          setLoading(false);
        }
      }
    };

    cargarDatosFicha();

    return () => {
      isMounted = false;
      abortController.abort(); // Cancela llamadas colgadas al salir de la pantalla
    };
  }, [id]);

  const handleToggleFavorito = async () => {
    try {
      const token = sessionStorage.getItem('token');
      const nuevoEstado = !isFavorito;
      setIsFavorito(nuevoEstado); // UI Optimista: prende/apaga al instante sin esperar al servidor

      await api.post(`/usuario/recurso/${id}/favorito`, {
        favorito: nuevoEstado
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });
    } catch (err) {
      console.error("Error al guardar favorito:", err);
      setIsFavorito(isFavorito); // Reversa el estado si hay un error real de red
    }
  };

  const handleOpenLink = (e: any, url: string, mensajeLegal: string, isPdf: boolean = false) => {
    e.preventDefault(); 
    e.stopPropagation(); 

    let mensajeFinal = mensajeLegal ? String(mensajeLegal).trim() : '';

    if (mensajeFinal === '') {
      if (recurso.TipoRecurso === 'Libro') mensajeFinal = textosLegalesGlobales.libro;
      else if (recurso.TipoRecurso === 'Revista / Artículo Científico') mensajeFinal = textosLegalesGlobales.revista;
      else if (recurso.TipoRecurso === 'Enciclopedia / Diccionario') mensajeFinal = textosLegalesGlobales.enciclopedia;
      else if (recurso.TipoRecurso === 'Tesis') mensajeFinal = textosLegalesGlobales.tesis;
    }

    if (mensajeFinal !== '') {
      setAvisoModal({ show: true, mensaje: mensajeFinal, url: url || '', isPdf: isPdf });
    } else {
      if (url) {
        window.open(url, '_blank', 'noopener,noreferrer');
      } else {
        setToast({ show: true, message: 'Este recurso no cuenta con un enlace digital guardado.' });
        setTimeout(() => setToast({ show: false, message: '' }), 3000);
      }
    }
  };

  const obtenerIconoPorTipo = (tipo: string) => {
    if (tipo === 'Tesis') return schoolOutline;
    if (tipo === 'Revista / Artículo Científico') return newspaperOutline;
    if (tipo === 'Enciclopedia / Diccionario') return libraryOutline;
    if (tipo === 'Equipo Audiovisual') return tvOutline;
    if (tipo === 'Mobiliario Didáctico') return easelOutline;
    if (tipo === 'Dispositivo de Conectividad') return wifiOutline;
    return bookOutline;
  };

  const obtenerMensajeAgotado = (tipo: string) => {
    if (tipo === 'Revista / Artículo Científico') return 'Revista no disponible';
    if (tipo === 'Enciclopedia / Diccionario') return 'Enciclopedia no disponible';
    if (tipo === 'Tesis') return 'Tesis no disponible';
    if (tipo === 'Equipo Audiovisual') return 'Equipo no disponible';
    if (tipo === 'Mobiliario Didáctico') return 'Mobiliario no disponible';
    if (tipo === 'Dispositivo de Conectividad') return 'Dispositivo no disponible';
    return 'Sin ejemplares disponibles';
  };

  if (loading) {
    return (
      <IonPage>
        <div className="cervantes-loader-container">
          <div className="cervantes-spinner"></div>
          <p>Cargando ficha bibliográfica oficial...</p>
        </div>
      </IonPage>
    );
  }

  if (!recurso) {
    return (
      <IonPage>
        <div className="cervantes-loader-container">
          <p style={{ color: '#ef4444', fontWeight: 'bold' }}>❌ El recurso solicitado no se encuentra disponible.</p>
          <button className="btn-cervantes-back-error" onClick={() => window.history.back()}>
            Volver a la página anterior
          </button>
        </div>
      </IonPage>
    );
  }

  const isPrintMaterial = ['Libro', 'Revista / Artículo Científico', 'Tesis', 'Enciclopedia / Diccionario'].includes(recurso.TipoRecurso);
  const tieneEnlaceDigital = recurso.URL_Externa || recurso.Pdf_url;
  const tieneStockFisico = recurso.unidades_disponibles > 0;

  return (
    <IonPage>
      <div className="detalle-navbar">
        <div className="navbar-left">
          <button className="navbar-back-arrow-btn" onClick={() => window.history.back()} title="Regresar al catálogo">
            <IonIcon icon={arrowBackOutline} />
          </button>
          <span className="university-logo-text">UPVE</span>
          <span className="university-brand-sub">BIBLIOTECA</span>
        </div>

        <div className="navbar-center-links">
          <span className="nav-top-link" onClick={() => window.location.href = '/portal/inicio'}>Inicio</span>
          <span className="nav-top-link" onClick={() => window.location.href = '/portal/explorar'}>Explorar</span>
          <span className="nav-top-link" onClick={() => window.location.href = '/portal/mibiblioteca'}>Mi Biblioteca</span>
        </div>

        <div className="navbar-right"></div>
      </div>

      <IonContent className="cervantes-content-bg" fullscreen>
        
        {toast.show && <div className="cervantes-toast-error"><span>{toast.message}</span></div>}

        {avisoModal.show && (
          <div className="cervantes-modal-overlay">
            <div className="cervantes-modal-content">
              <h3>
                <IonIcon icon={avisoModal.isPdf ? checkmarkCircleOutline : warningOutline} style={{ color: avisoModal.isPdf ? '#10b981' : '#f59e0b', verticalAlign: 'middle', marginRight: '8px' }}/>
                Aviso de Consulta Digital
              </h3>
              <p>{avisoModal.mensaje}</p>
              <div className="cervantes-modal-actions">
                <button className="btn-cervantes-modal-no" onClick={() => setAvisoModal({ ...avisoModal, show: false })}>Cancelar</button>
                <button className="btn-cervantes-modal-yes" onClick={() => { 
                  window.open(avisoModal.url, '_blank'); 
                  setAvisoModal({ ...avisoModal, show: false }); 
                }}>
                  Entendido, Continuar
                </button>
              </div>
            </div>
          </div>
        )}

        <div className="cervantes-layout-wrapper">
          <div className="cervantes-main-card">
            
            {/* COLUMNA IZQUIERDA: PORTADA Y ACCESO INTEGRADO */}
            <div className="cervantes-col-left">
              <div className="cervantes-image-wrapper">
                {recurso.Imagen_url ? (
                  <img src={recurso.Imagen_url} alt={recurso.Titulo} className="cervantes-book-img" />
                ) : (
                  <div className="cervantes-fallback-frame">
                    <IonIcon icon={obtenerIconoPorTipo(recurso.TipoRecurso)} />
                    <span>{recurso.TipoRecurso}</span>
                  </div>
                )}
              </div>

              <div className="cervantes-under-cover-box">
                {recurso.TipoRecurso === 'Tesis' && recurso.Pdf_url ? (
                  <button className="btn-cervantes-digital dynamic-bg-verde" style={{ marginBottom: '12px' }} onClick={(e) => handleOpenLink(e, recurso.Pdf_url, recurso.Mensaje_Legal, true)}>
                    📄 Ver Documento PDF
                  </button>
                ) : recurso.URL_Externa ? (
                  <button className="btn-cervantes-digital dynamic-bg-azul" style={{ marginBottom: '12px' }} onClick={(e) => handleOpenLink(e, recurso.URL_Externa, recurso.Mensaje_Legal, false)}>
                    🌐 Consultar en Sitio Web
                  </button>
                ) : null}

                {tieneStockFisico && tieneEnlaceDigital && (
                  <div className="real-inventory-badge badge-both" style={{ marginBottom: '12px' }}>
                    <span className="badge-dot dot-green"></span>
                    <span>Disponible en Físico y Digital</span>
                  </div>
                )}

                {!tieneStockFisico && tieneEnlaceDigital && (
                  <div className="real-inventory-badge badge-digital-only" style={{ marginBottom: '12px' }}>
                    <span className="badge-dot dot-blue"></span>
                    <span>Disponible Solo en Digital</span>
                  </div>
                )}

                {tieneStockFisico && !tieneEnlaceDigital && (
                  <div className="real-inventory-badge badge-physical-only" style={{ marginBottom: '12px' }}>
                    <span className="badge-dot dot-purple"></span>
                    <span>Disponible Solo en Biblioteca Física</span>
                  </div>
                )}

                {!tieneStockFisico && !tieneEnlaceDigital && (
                  <div className="real-inventory-badge badge-empty" style={{ marginBottom: '12px' }}>
                    <span className="badge-dot dot-red"></span>
                    <span>{obtenerMensajeAgotado(recurso.TipoRecurso)}</span>
                  </div>
                )}
              </div>
            </div>

            {/* COLUMNA DERECHA: ESPECIFICACIONES CON CORAZÓN ALINEADO */}
            <div className="cervantes-col-right">
              
              <h1 className="cervantes-main-title-text">{recurso.Titulo}</h1>
              <div className="cervantes-title-divider"></div>

              <div className="cervantes-section-group-card">
                {/* 🏛️ CORRECCIÓN UBICACIÓN: Corazón integrado en el extremo derecho de la barra gris */}
                <h3 className="group-card-title">
                  <div className="group-card-title-left-side">
                    <IonIcon icon={bookmarkOutline} /> 
                    <span>Especificaciones Generales</span>
                  </div>
                  
                  <button 
                    type="button"
                    className={`btn-favorito-heart-ficha ${isFavorito ? 'heart-active-red' : ''}`}
                    onClick={handleToggleFavorito}
                    title={isFavorito ? "Quitar de favoritos" : "Añadir a favoritos"}
                  >
                    <IonIcon icon={isFavorito ? heart : heartOutline} />
                  </button>
                </h3>
                
                <div className="group-card-content">
                  <p className="cervantes-meta-row"><span className="cervantes-label">Tipo de recurso:</span><span className="cervantes-value text-purple-type">{recurso.TipoRecurso}</span></p>
                  
                  {['Equipo Audiovisual', 'Mobiliario Didáctico', 'Dispositivo de Conectividad'].includes(recurso.TipoRecurso) ? (
                    <>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Año de registro:</span><span className="cervantes-value">{recurso.AnioPublicacion}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Marca / Fabricante:</span><span className="cervantes-value text-link-style">{recurso.Marca || 'No especificada'}</span></p>
                      {recurso.TipoRecurso === 'Mobiliario Didáctico' ? (
                        <p className="cervantes-meta-row"><span className="cervantes-label">Material:</span><span className="cervantes-value">{recurso.Material || 'No especificado'}</span></p>
                      ) : (
                        <p className="cervantes-meta-row"><span className="cervantes-label">Número de Serie:</span><span className="cervantes-value">{recurso.NumSerie || 'No especificado'}</span></p>
                      )}
                    </>
                  ) : recurso.TipoRecurso === 'Tesis' ? (
                    <>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Área / Tema:</span><span className="cervantes-value">{recurso.TemaRecurso || 'General'}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Año de publicación:</span><span className="cervantes-value">{recurso.AnioPublicacion}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Autor (Alumno):</span><span className="cervantes-value text-link-style">{recurso.AutorTexto || 'No especificado'}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Maestro Asesor:</span><span className="cervantes-value">{recurso.Asesor || 'No especificado'}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Grado / Carrera:</span><span className="cervantes-value">{recurso.GradoCarrera || 'No especificado'}</span></p>
                    </>
                  ) : (
                    <>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Área / Tema:</span><span className="cervantes-value">{recurso.TemaRecurso || 'General'}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Año de publicación:</span><span className="cervantes-value">{recurso.AnioPublicacion}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Autor / Compilador:</span><span className="cervantes-value text-link-style">{recurso.Autor || 'Autor Colectivo / Institucional'}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Casa Editorial:</span><span className="cervantes-value">{recurso.Editorial || 'Edición Independiente'}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Edición / Volumen:</span><span className="cervantes-value">{recurso.EdicionVolumen || '-'}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Registro (ISBN / ISSN):</span><span className="cervantes-value">{recurso.ClasificacionISBN || recurso.ClasificacionISSN || 'Sin registro'}</span></p>
                    </>
                  )}

                  {isPrintMaterial && (
                    <>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Formato / Tapa:</span><span className="cervantes-value">{recurso.Formato || 'No especificado'}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Páginas:</span><span className="cervantes-value">{recurso.Cantidad_Paginas ? `${recurso.Cantidad_Paginas} págs.` : 'No especificado'}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Idioma:</span><span className="cervantes-value">{recurso.Idioma || 'No especificado'}</span></p>
                      <p className="cervantes-meta-row"><span className="cervantes-label">Género / Tipo:</span><span className="cervantes-value">{recurso.Genero || 'No especificado'}</span></p>
                    </>
                  )}

                  {/* 🏛️ Ocultamos la sinopsis para activos fijos que no la requieren */}
                  {!['Equipo Audiovisual', 'Mobiliario Didáctico', 'Dispositivo de Conectividad'].includes(recurso.TipoRecurso) && (
                    <div className="single-card-inner-text-block">
                      <h4 className="inner-block-subtitle"><IonIcon icon={documentTextOutline} /> Resumen / Sinopsis:</h4>
                      <p className="cervantes-paragraph-justified text-main-synopsis">
                        {recurso.Resumen && recurso.Resumen.trim() !== '' 
                          ? recurso.Resumen 
                          : 'Este recurso no cuenta con una sinopsis o resumen argumental registrado en el catálogo digital.'}
                      </p>
                    </div>
                  )}

                  <div className="single-card-inner-text-block border-top-dashed">
                    <h4 className="inner-block-subtitle"><IonIcon icon={clipboardOutline} /> Observaciones:</h4>
                    <p className="cervantes-paragraph-justified text-main-observations">
                      {recurso.Observaciones && recurso.Observaciones.trim() !== '' 
                        ? recurso.Observaciones 
                        : 'Sin observaciones adicionales o especificaciones de inventario registradas.'}
                    </p>
                  </div>

                </div>
              </div>

              <div className="cervantes-footer-info-badge">
                <IonIcon icon={informationCircleOutline} />
                <span>Para préstamos acude al mostrador de control portando tu credencial de alumno vigente. Respeta los reglamentos y plazos de devolución de la UPVE.</span>
              </div>

            </div>

          </div>
        </div>
      </IonContent>
    </IonPage>
  );
};

export default DetalleRecurso;