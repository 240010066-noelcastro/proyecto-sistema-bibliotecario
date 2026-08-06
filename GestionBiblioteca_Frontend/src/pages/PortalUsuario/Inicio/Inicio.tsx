import React, { useEffect, useState } from 'react';
import { IonContent, IonPage, IonIcon, IonInfiniteScroll, IonInfiniteScrollContent } from '@ionic/react';
import { bookOutline, alertCircleOutline, searchOutline, timeOutline, warningOutline, libraryOutline, heartOutline, closeOutline, eyeOutline, newspaperOutline, albumsOutline } from 'ionicons/icons';
// @ts-ignore
import api from '../../../services/api';
import './Inicio.css'; 

const Inicio: React.FC = () => {
  const [usuario, setUsuario] = useState<any>(null);
  const [prestamosCount, setPrestamosCount] = useState<number>(0);
  const [atrasosCount, setAtrasosCount] = useState<number>(0);
  const [multasTotal, setMultasTotal] = useState<string>('0.00');
  const [librosNovedades, setLibrosNovedades] = useState<any[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  
  // Controles de Búsqueda Local e Interfaz
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [mostrarAvisoLegal, setMostrarAvisoLegal] = useState<boolean>(false);
  const [resultadosLocales, setResultadosLocales] = useState<any[]>([]);
  const [isSearching, setIsSearching] = useState<boolean>(false);
  const [temasVisibles, setTemasVisibles] = useState<number>(5);

  useEffect(() => {
    const userData = sessionStorage.getItem('usuario');
    if (userData) {
      setUsuario(JSON.parse(userData));
    }

    const abortController = new AbortController();
    let isMounted = true;

    const cargarDatosDashboard = async () => {
      try {
        const token = sessionStorage.getItem('token');
        const response = await api.get('/usuario/dashboard-stats', {
          headers: { Authorization: `Bearer ${token}` },
          signal: abortController.signal
        });
        
        if (response.data.success && isMounted) {
          setPrestamosCount(response.data.prestamos_activos);
          setAtrasosCount(response.data.atrasos);
          setMultasTotal(response.data.multas_pendientes);
          setLibrosNovedades(response.data.novedades || []);
        }
      } catch (error: any) {
        if (error.name !== 'CanceledError' && error.message !== 'canceled') {
          console.error("Error en dashboard:", error);
        }
      } finally {
        if (isMounted && !abortController.signal.aborted) {
          setLoading(false);
        }
      }
    };

    cargarDatosDashboard();

    return () => {
      isMounted = false;
      abortController.abort();
    };
  }, []);

  // 🏛️ NUEVO EFFECT: Hace que la advertencia legal desaparezca sola después de 4 segundos
  useEffect(() => {
    if (mostrarAvisoLegal) {
      const timer = setTimeout(() => {
        setMostrarAvisoLegal(false);
      }, 4000); 
      return () => clearTimeout(timer);
    }
  }, [mostrarAvisoLegal]);

  const handleBuscarLocal = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    const query = searchQuery.trim().toLowerCase();
    
    if (!query) {
      setIsSearching(false);
      setResultadosLocales([]);
      return;
    }

    setIsSearching(true);
    setMostrarAvisoLegal(false); // Ocultar aviso de inmediato al presionar buscar
    
    // 🏛️ REPARACIÓN: Normaliza acentos, separa por palabras sueltas e ignora plurales quitando la 's' final
    const tokenizarYNormalizar = (texto: string) => {
      return texto
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "") // Limpia tildes
        .split(/\s+/)                  // Separa palabras por espacios
        .filter(palabra => palabra.length > 0)
        .map(palabra => palabra.replace(/s$/, '')); // Convierte plurales a singulares de forma simple
    };

    const palabrasQuery = tokenizarYNormalizar(query);

    const filtrados = librosNovedades.filter(item => {
      if (!item.Titulo) return false;
      
      const palabrasTitulo = tokenizarYNormalizar(item.Titulo);
      
      return palabrasQuery.every(palabraQ => 
        palabrasTitulo.some(palabraT => palabraT.includes(palabraQ))
      );
    });
    
    setResultadosLocales(filtrados);
  };

  const handleLimpiarBusqueda = () => {
    setSearchQuery('');
    setIsSearching(false);
    setResultadosLocales([]);
  };

  const renderIconoCategoria = (tipoId: number) => {
    if (tipoId === 2) return '🔬';
    if (tipoId === 4) return '📊';
    return '📚';
  };

  // SECCIÓN DINÁMICA: Agrupación por temas venidos de la BD
  const temasAgrupados = librosNovedades.reduce((acc: any, libro: any) => {
    const tema = libro.TemaRecurso && libro.TemaRecurso.trim() !== '' ? libro.TemaRecurso : 'Repositorio Multidisciplinario';
    if (!acc[tema]) acc[tema] = [];
    acc[tema].push(libro);
    return acc;
  }, {});

  // Filtros globales para Revistas y Enciclopedias
  const todasLasRevistas = librosNovedades.filter(l => l.TipoRecurso_ID === 2);
  const todasLasEnciclopedias = librosNovedades.filter(l => l.TipoRecurso_ID === 4);

  return (
    <IonPage>
      {/* 1. NAVBAR SUPERIOR WEB COMPLETA */}
      <div className="inicio-navbar">
        <div className="navbar-left">
          <span className="university-logo-text">UPVE</span>
          <span className="university-brand-sub">BIBLIOTECA</span>
        </div>

        <div className="navbar-center-links">
          <span className="nav-top-link active" onClick={() => window.location.href = '/portal/inicio'}>Inicio</span>
          <span className="nav-top-link" onClick={() => window.location.href = '/portal/explorar'}>Explorar</span>
          <span className="nav-top-link" onClick={() => window.location.href = '/portal/mibiblioteca'}>Mi Biblioteca</span>
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

      <IonContent className="portal-bg" fullscreen>
        <div className="inicio-master-scroll-wrapper">

          {/* 2. HERO BANNER PRINCIPAL */}
          <div className="hero-section">
            <div className="hero-content-wrapper">
              <span className="hero-badge">PORTAL DIGITAL</span>
              <h1 className="hero-main-title">
                BIENVENIDO AL PORTAL BIBLIOTECARIO<br />
                <span className="text-highlight-morado">INSTITUCIONAL</span>
              </h1>
              
              <form onSubmit={handleBuscarLocal} className="hero-search-form" style={{ position: 'relative' }}>
                <div className="hero-search-input-wrapper">
                  <IonIcon icon={searchOutline} className="hero-search-inside-icon" />
                  <input 
                    type="text" 
                    placeholder="¿Qué libro, revista o recurso buscas hoy?..." 
                    value={searchQuery}
                    onFocus={() => setMostrarAvisoLegal(true)}
                    onChange={(e) => {
                      setSearchQuery(e.target.value);
                      if (e.target.value.trim() === '') {
                        setIsSearching(false);
                        setResultadosLocales([]);
                      }
                    }}
                    className="hero-search-field"
                  />
                  {searchQuery && (
                    <IonIcon icon={closeOutline} className="hero-clear-inside-icon" onClick={handleLimpiarBusqueda} />
                  )}
                  <button type="submit" className="invisible-lupa-trigger-inicio" title="Buscar recurso" />
                </div>

                {mostrarAvisoLegal && (
                  <div className="librototal-legal-callout">
                    <button type="button" className="close-legal-callout-btn" onClick={() => setMostrarAvisoLegal(false)}>×</button>
                    <p>
                      <strong>Nota de Responsabilidad:</strong> Todos los contenidos indexados en este repositorio son de uso estrictamente educativo y de consulta institucional. La UPVE no almacena ni publica materiales protegidos por derechos de autor sin la debida autorización.
                    </p>
                  </div>
                )}
              </form>

              {/* 🏛️ CORRECCIÓN UX: Ocultamos el saludo de bienvenida si el alumno ya está buscando */}
              {!isSearching && (
                <p className="hero-subtitle">
                  ¡Bienvenido, {usuario ? usuario.NombreUsuario.split(' ')[0] : 'Alumno'}! 👋
                </p>
              )}
            </div>
          </div>

          {/* 3. CUERPO DE CONTENIDOS */}
          <div className="inicio-main-content">
            
            {/* Escondemos los accesos rápidos y resúmenes durante la búsqueda */}
            {!isSearching && (
              <>
                {/* TARJETA DE ESTADO OVERLAP */}
                <div className="status-summary-card overlap-card">
                  <div className="summary-block">
                    <div className="summary-icon-box box-morado-oficial">
                      <IonIcon icon={bookOutline} />
                    </div>
                    <div className="summary-data">
                      <h3>{loading ? '...' : prestamosCount}</h3>
                      <p>Préstamos Activos</p>
                    </div>
                  </div>
                  <div className="summary-divider"></div>
                  <div className="summary-block">
                    <div className="summary-icon-box box-naranja-oficial">
                      <IonIcon icon={alertCircleOutline} />
                    </div>
                    <div className="summary-data">
                      <h3 className={parseFloat(multasTotal) > 0 ? 'text-danger-oficial' : 'text-normal-black'}>
                        ${loading ? '0.00' : multasTotal}
                      </h3>
                      <p>Multas Pendientes</p>
                    </div>
                  </div>
                </div>

                {/* BANNER DE NOTIFICACIÓN DE ATRASOS */}
                {!loading && atrasosCount > 0 && (
                  <div className="atraso-alert-banner">
                    <div className="atraso-alert-icon">
                      <IonIcon icon={warningOutline} />
                    </div>
                    <div className="atraso-alert-text">
                      <h4>Atención: Préstamos Vencidos o Pendientes</h4>
                      <p>Tienes {atrasosCount} {atrasosCount === 1 ? 'libro o recurso con retraso' : 'libros o recursos con retrasos'}. Por favor acude al mostrador de control.</p>
                    </div>
                  </div>
                )}

                {/* ACCESOS DIRECTOS */}
                <h3 className="section-title-modern">Mis Accesos Directos</h3>
                <div className="quick-actions-grid">
                  {/* 🏛️ Redirige directamente a la pantalla limpia de Pedidos Activos */}
                  <div className="action-btn" onClick={() => window.location.href = '/portal/pedidos'}>
                    <div className="action-icon-circle bg-morado-oficial-btn">
                      <IonIcon icon={libraryOutline} className="color-white" />
                    </div>
                    <span>Pedidos Activos</span>
                  </div>
                  
                  {/* 🏛️ Redirige directamente a la nueva pantalla independiente de Historial */}
                  <div className="action-btn" onClick={() => window.location.href = '/portal/historial'}>
                    <div className="action-icon-circle bg-azul-oficial">
                      <IonIcon icon={timeOutline} className="color-white" />
                    </div>
                    <span>Mi Historial</span>
                  </div>
                  
                  {/* 🏛️ Redirige a tus Favoritos Guardados de forma limpia */}
                  <div className="action-btn" onClick={() => window.location.href = '/portal/mibiblioteca'}>
                    <div className="action-icon-circle bg-verde-oficial">
                      <IonIcon icon={heartOutline} className="color-white" />
                    </div>
                    <span>Mis Favoritos</span>
                  </div>
                </div>
              </>
            )}

            {/* RENDERIZACIÓN DE RESULTADOS DE BÚSQUEDA */}
            {isSearching ? (
              <div className="inline-search-results-box" style={{ marginTop: '25px' }}>
                <div className="section-header">
                  <h3 className="section-title-modern">Resultados de tu búsqueda ({resultadosLocales.length})</h3>
                  <span className="see-all-link" onClick={handleLimpiarBusqueda}>Cerrar búsqueda ×</span>
                </div>

                {resultadosLocales.length > 0 ? (
                  <div className="inicio-cards-results-grid">
                    {resultadosLocales.map((recurso: any, index: number) => (
                      <div className="book-card-mini" key={`res-${recurso.id || index}`} onClick={() => window.location.href = `/portal/recurso/${recurso.id}`} style={{ cursor: 'pointer' }}>
                        <div className="book-cover">
                          {recurso.Portada || recurso.Imagen ? (
                            <img src={recurso.Portada || recurso.Imagen} alt={recurso.Titulo} className="book-cover-img-real" />
                          ) : (
                            <div className={`book-cover-placeholder-gradient mock-cover-${(index % 3) + 1}`}>{renderIconoCategoria(recurso.TipoRecurso_ID)}</div>
                          )}
                        </div>
                        <h4 className="book-title-mini" title={recurso.Titulo}>{recurso.Titulo}</h4>
                        <p className="book-category-mini">Disponible</p>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="no-results-local-text">No se encontraron recursos coincidentes con los términos ingresados.</p>
                )}
              </div>
            ) : (
              /* CONTENEDOR DE CARTELERAS LIMPIAS */
              <div className="dynamic-shelves-container">
                
                {/* 1️⃣ CARTELERA FIJA TOP: ÚLTIMOS AGREGADOS RECIENTES */}
                <div className="shelf-row-wrapper">
                  <h4 className="shelf-topic-title">Últimos Agregados</h4>
                  <div className="books-horizontal-scroll">
                    {librosNovedades.length > 0 ? (
                      librosNovedades.slice(0, 10).map((recurso: any, index: number) => (
                        <div className="book-card-mini" key={`top-${recurso.id || index}`} onClick={() => window.location.href = `/portal/recurso/${recurso.id}`}>
                          <div className="book-cover">
                            {recurso.Portada || recurso.Imagen ? (
                              <img src={recurso.Portada || recurso.Imagen} alt={recurso.Titulo} className="book-cover-img-real" />
                            ) : (
                              <div className={`book-cover-placeholder-gradient mock-cover-${(index % 3) + 1}`}>{renderIconoCategoria(recurso.TipoRecurso_ID)}</div>
                            )}
                          </div>
                          <h4 className="book-title-mini" title={recurso.Titulo}>{recurso.Titulo}</h4>
                          <p className="book-category-mini">Novedad</p>
                        </div>
                      ))
                    ) : (
                      <p className="fallback-text-shelves">{loading ? 'Sincronizando catálogo...' : 'No hay recursos agregados recientemente.'}</p>
                    )}
                  </div>
                </div>

                {/* 2️⃣ CARTELERA GLOBAL DE REVISTAS */}
                {todasLasRevistas.length > 0 && (
                  <div className="shelf-row-wrapper" style={{ marginTop: '15px', borderTop: '1px dashed #cbd5e1', paddingTop: '20px' }}>
                    <h4 className="shelf-topic-title">Revistas</h4>
                    <div className="books-horizontal-scroll">
                      {todasLasRevistas.map((recurso: any, index: number) => (
                        <div className="book-card-mini" key={`rev-global-${recurso.id || index}`} onClick={() => window.location.href = `/portal/recurso/${recurso.id}`}>
                          <div className="book-cover">
                            {recurso.Portada || recurso.Imagen ? (
                              <img src={recurso.Portada || recurso.Imagen} alt={recurso.Titulo} className="book-cover-img-real" />
                            ) : (
                              <div className="book-cover-placeholder-gradient mock-cover-1">🔬</div>
                            )}
                          </div>
                          <h4 className="book-title-mini" title={recurso.Titulo}>{recurso.Titulo}</h4>
                          <p className="book-category-mini">Revista</p>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* 3️⃣ CARTELERA GLOBAL DE ENCICLOPEDIAS Y DICCIONARIOS */}
                {todasLasEnciclopedias.length > 0 && (
                  <div className="shelf-row-wrapper" style={{ marginTop: '15px', borderTop: '1px dashed #cbd5e1', paddingTop: '20px' }}>
                    <h4 className="shelf-topic-title">Enciclopedias / Diccionarios</h4>
                    <div className="books-horizontal-scroll">
                      {todasLasEnciclopedias.map((recurso: any, index: number) => (
                        <div className="book-card-mini" key={`enci-global-${recurso.id || index}`} onClick={() => window.location.href = `/portal/recurso/${recurso.id}`}>
                          <div className="book-cover">
                            {recurso.Portada || recurso.Imagen ? (
                              <img src={recurso.Portada || recurso.Imagen} alt={recurso.Titulo} className="book-cover-img-real" />
                            ) : (
                              <div className="book-cover-placeholder-gradient mock-cover-3">9📖</div>
                            )}
                          </div>
                          <h4 className="book-title-mini" title={recurso.Titulo}>{recurso.Titulo}</h4>
                          <p className="book-category-mini">Colección</p>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* 4️⃣ ESTANTERÍAS TEMÁTICAS ASIGNADAS AUTOMÁTICAS */}
                {Object.keys(temasAgrupados).sort((a, b) => a.localeCompare(b)).slice(0, temasVisibles).map((nombreTema) => (
                  <div className="shelf-row-wrapper" key={nombreTema} style={{ marginTop: '15px', borderTop: '1px dashed #cbd5e1', paddingTop: '20px' }}>
                    <h4 className="shelf-topic-title">{nombreTema}</h4>
                    <div className="books-horizontal-scroll">
                      {temasAgrupados[nombreTema].map((recurso: any, index: number) => (
                        <div className="book-card-mini" key={`dinamico-${recurso.id || index}`} onClick={() => window.location.href = `/portal/recurso/${recurso.id}`}>
                          <div className="book-cover">
                            {recurso.Portada || recurso.Imagen ? (
                              <img src={recurso.Portada || recurso.Imagen} alt={recurso.Titulo} className="book-cover-img-real" />
                            ) : (
                              <div className={`book-cover-placeholder-gradient mock-cover-${((index + 1) % 3) + 1}`}>{renderIconoCategoria(recurso.TipoRecurso_ID)}</div>
                            )}
                          </div>
                          <h4 className="book-title-mini" title={recurso.Titulo}>{recurso.Titulo}</h4>
                          <p className="book-category-mini">Disponible</p>
                        </div>
                      ))}
                    </div>
                  </div>
                ))}

                {/* 5️⃣ VIGILANTE DEL SCROLL INFINITO */}
                <IonInfiniteScroll
                  disabled={temasVisibles >= Object.keys(temasAgrupados).length}
                  onIonInfinite={(e: CustomEvent<void>) => {
                    setTimeout(() => {
                      setTemasVisibles((prev) => prev + 5); 
                      (e.target as HTMLIonInfiniteScrollElement).complete(); 
                    }, 800); 
                  }}
                >
                  <IonInfiniteScrollContent
                    loadingSpinner="bubbles"
                    loadingText="Cargando más áreas de estudio..."
                  />
                </IonInfiniteScroll>

              </div>
            )}

          </div>
        </div>
      </IonContent>
    </IonPage>
  );
};

export default Inicio;

