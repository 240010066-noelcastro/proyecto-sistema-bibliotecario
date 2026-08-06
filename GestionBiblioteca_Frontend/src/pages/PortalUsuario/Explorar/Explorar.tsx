import React, { useEffect, useState, useRef } from 'react';
import { IonContent, IonPage, IonIcon, IonSearchbar } from '@ionic/react';
import { arrowBackOutline, searchOutline, bookOutline, schoolOutline, newspaperOutline, libraryOutline, gridOutline, chevronBackOutline, chevronForwardOutline, tvOutline, buildOutline, wifiOutline, chevronDownOutline, chevronUpOutline, layersOutline } from 'ionicons/icons';
// @ts-ignore
import api from '../../../services/api';
import './Explorar.css';

const categoriasModulos = [
  { id: 'Todos', nombre: 'Todos', icon: gridOutline, valorModulo: '' }, 
  { id: 'Libro', nombre: 'Libros', icon: bookOutline, valorModulo: 'Libro' },
  { id: 'Revista', nombre: 'Revistas', icon: newspaperOutline, valorModulo: 'Revista / Artículo Científico' },
  { id: 'Tesis', nombre: 'Tesis', icon: schoolOutline, valorModulo: 'Tesis' },
  { id: 'Enciclopedia', nombre: 'Enciclopedias', icon: libraryOutline, valorModulo: 'Enciclopedia / Diccionario' },
  { id: 'Audiovisual', nombre: 'Equipo Audiovisual', icon: tvOutline, valorModulo: 'Equipo Audiovisual' }, 
  { id: 'Mobiliario', nombre: 'Equipo Mobiliario', icon: buildOutline, valorModulo: 'Mobiliario Didáctico' }, 
  { id: 'Conectividad', nombre: 'Equipo de Conectividad', icon: wifiOutline, valorModulo: 'Dispositivo de Conectividad' } 
];

const obtenerIconoPorTipo = (tipo: string) => {
  if (tipo === 'Tesis') return schoolOutline;
  if (tipo === 'Revista / Artículo Científico') return newspaperOutline;
  if (tipo === 'Enciclopedia / Diccionario') return libraryOutline;
  if (tipo === 'Equipo Audiovisual') return tvOutline;
  if (tipo === 'Mobiliario Didáctico') return buildOutline;
  if (tipo === 'Dispositivo de Conectividad') return wifiOutline;
  return bookOutline;
};

const Explorar: React.FC = () => {
  const [recursos, setRecursos] = useState<any[]>([]);
  const [temasBD, setTemasBD] = useState<string[]>([]); 
  const [usuario, setUsuario] = useState<any>(null); 
  const [loading, setLoading] = useState<boolean>(true);
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [categoriaActiva, setCategoriaActiva] = useState<string>('Todos');
  const [moduloValor, setModuloValor] = useState<string>('');
  const [mostrarSubcatalogo, setMostrarSubcatalogo] = useState<boolean>(false);
  const [temaSeleccionado, setTemaSeleccionado] = useState<string>('');
  
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [lastPage, setLastPage] = useState<number>(1);
  const [totalRecords, setTotalRecords] = useState<number>(0);

  const abortControllerRef = useRef<AbortController | null>(null);

  // Consulta secundaria (Filtros, búsquedas y paginaciones)
  const fetchCatalogoDigital = async (page: number, search: string, modulo: string) => {
    setLoading(true);
    if (abortControllerRef.current) abortControllerRef.current.abort();
    abortControllerRef.current = new AbortController();

    try {
      const token = sessionStorage.getItem('token');
      const res = await api.get(`/catalogo?page=${page}&search=${search}&modulo=${modulo}&per_page=24`, {
        headers: { Authorization: `Bearer ${token}` },
        signal: abortControllerRef.current.signal
      });

      if (res.data?.success) {
        setRecursos(res.data.data?.data || []);
        setCurrentPage(res.data.data?.current_page || 1);
        setLastPage(res.data.data?.last_page || 1);
        setTotalRecords(res.data.data?.total || 0);
      }
    } catch (err: any) {
      if (err.name !== 'CanceledError' && err.message !== 'canceled') {
        console.error("Error al explorar repositorio:", err);
      }
    } finally {
      if (!abortControllerRef.current?.signal.aborted) {
        setLoading(false);
      }
    }
  };

  useEffect(() => {
    const userData = sessionStorage.getItem('usuario');
    if (userData) {
      setUsuario(JSON.parse(userData));
    }

    // 🚀 OPTIMIZACIÓN: AbortController primario para Laragon
    const initialAbort = new AbortController();
    let isMounted = true;

    const inicializarModulo = async () => {
      try {
        const token = sessionStorage.getItem('token');
        
        // Carga de temas sin duplicar procesos de base de datos
        const resTemas = await api.get('/temas/buscar?all=1', {
          headers: { Authorization: `Bearer ${token}` },
          signal: initialAbort.signal
        });
        if (resTemas.data?.success && isMounted) {
          setTemasBD(resTemas.data.data || []);
        }

        // Carga inicial del catálogo
        const params = new URLSearchParams(window.location.search);
        const queryInit = params.get('search') || '';
        
        const resCat = await api.get(`/catalogo?page=1&search=${queryInit}&modulo=&per_page=24`, {
          headers: { Authorization: `Bearer ${token}` },
          signal: initialAbort.signal
        });

        if (resCat.data?.success && isMounted) {
          if (queryInit) setSearchQuery(queryInit);
          setRecursos(resCat.data.data?.data || []);
          setCurrentPage(resCat.data.data?.current_page || 1);
          setLastPage(resCat.data.data?.last_page || 1);
          setTotalRecords(resCat.data.data?.total || 0);
        }
      } catch (err: any) {
        if (err.name !== 'CanceledError' && err.message !== 'canceled') {
          console.error("Error al arrancar modulo:", err);
        }
      } finally {
        if (isMounted && !initialAbort.signal.aborted) {
          setLoading(false);
        }
      }
    };

    inicializarModulo();

    return () => {
      isMounted = false;
      initialAbort.abort(); 
    };
  }, []);

  const handleBuscarLupa = () => {
    fetchCatalogoDigital(1, searchQuery, moduloValor);
  };

  const handleCambiarCategoria = (catId: string, valor: string) => {
    setCategoriaActiva(catId);
    setModuloValor(valor);
    setTemaSeleccionado('');
    fetchCatalogoDigital(1, searchQuery, valor);
  };

  const handleSeleccionarTema = (tema: string) => {
    setTemaSeleccionado(tema);
    setMostrarSubcatalogo(false);
    fetchCatalogoDigital(1, tema, moduloValor);
  };

  const handleLimpiarFiltroTema = () => {
    setTemaSeleccionado('');
    fetchCatalogoDigital(1, searchQuery, moduloValor);
  };

  const handleCambiarPagina = (pageNum: number) => {
    fetchCatalogoDigital(pageNum, temaSeleccionado || searchQuery, moduloValor);
  };

  const getPageNumbers = () => {
    if (lastPage <= 4) {
      const pages = [];
      for (let i = 1; i <= lastPage; i++) pages.push(i);
      return pages;
    }
    if (currentPage <= 3) return [1, 2, 3, 4];
    if (currentPage >= lastPage - 1) return [1, lastPage - 2, lastPage - 1, lastPage];
    return [1, currentPage - 1, currentPage, currentPage + 1];
  };

  return (
    <IonPage>
      {/* 🏛️ CLASE NORMALIZADA: global-navbar con gridTemplate para congelar el logo */}
      <div className="explorar-navbar" style={{ gridTemplateColumns: '1fr 1fr 1fr' }}>
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
          <span className="nav-top-link active-portal-route">Explorar</span>
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

      <IonContent className="explorar-content-bg" fullscreen>
        <div className="explorar-layout-container">
          
          <div className="explorar-hero-search-box">
            <h2>Explorador del Catálogo Institucional</h2>
            <p>Encuentra libros, artículos de revistas científicas y proyectos de titulación institucionales.</p>
            
            <div className="explorar-searchbar-flex" style={{ position: 'relative' }}>
              <IonSearchbar 
                placeholder="Escribe el título, tema, autor, año..."
                value={searchQuery}
                onIonInput={(e: any) => {
                  const val = e.target.value || '';
                  setSearchQuery(val);
                  if (val.trim() === '') fetchCatalogoDigital(1, '', moduloValor);
                }}
                onKeyDown={(e: any) => e.key === 'Enter' && handleBuscarLupa()}
                onIonClear={() => {
                  setSearchQuery('');
                  fetchCatalogoDigital(1, '', moduloValor);
                }}
              />
              <button 
                type="button" 
                className="invisible-lupa-btn-trigger" 
                onClick={handleBuscarLupa}
                title="Haga clic aquí para buscar"
              />
            </div>

            <div className="subcatalog-toggle-wrapper">
              <button className={`btn-subcatalog-dropdown ${mostrarSubcatalogo ? 'dropdown-open' : ''}`} onClick={() => setMostrarSubcatalogo(!mostrarSubcatalogo)}>
                <IonIcon icon={layersOutline} />
                <span>Temas</span> 
                <IonIcon icon={mostrarSubcatalogo ? chevronUpOutline : chevronDownOutline} style={{ marginLeft: 'auto' }} />
              </button>

              {mostrarSubcatalogo && (
                <div className="subcatalog-scrollable-panel">
                  <div className="panel-header-title">Selecciona un área de estudio:</div>
                  {temasBD.length > 0 ? (
                    temasBD.map((tema, i) => (
                      <div key={i} className="subcatalog-topic-item" onClick={() => handleSeleccionarTema(tema)}>
                        <span className="topic-dot">▪</span> {tema}
                      </div>
                    ))
                  ) : (
                    <div className="subcatalog-topic-item" style={{ color: '#94a3b8', cursor: 'default' }}>
                      No hay subtemas registrados.
                    </div>
                  )}
                </div>
              )}
            </div>

            {temaSeleccionado && (
              <div className="active-theme-pill-indicator">
                Tema: <strong>{temaSeleccionado}</strong>
                <span className="close-theme-pill" onClick={handleLimpiarFiltroTema}>×</span>
              </div>
            )}
          </div>

          <div className="explorar-categories-row">
            {categoriasModulos.map((cat) => (
              <button 
                key={cat.id}
                className={`category-pill-btn ${categoriaActiva === cat.id ? 'active-pill' : ''}`}
                onClick={() => handleCambiarCategoria(cat.id, cat.valorModulo)}
              >
                <IonIcon icon={cat.icon} />
                <span>{cat.nombre}</span>
              </button>
            ))}
          </div>

          <div className="explorar-results-section">
            <div className="explorar-results-header">
              <h3>{categoriaActiva === 'Todos' ? 'Todos los recursos' : `Filtro: ${categoriaActiva}`}</h3>
              <span>{totalRecords} encontrados</span>
            </div>

            {loading ? (
              <div className="explorar-loading-box">
                <div className="explorar-spinner"></div>
                <p>Escaneando el repositorio científico...</p>
              </div>
            ) : recursos.length === 0 ? (
              <div className="explorar-empty-state">
                <IonIcon icon={bookOutline} />
                <h4>No se encontraron coincidencias</h4>
                <p>Intenta cambiar los términos de búsqueda o selecciona otra categoría en el menú superior.</p>
              </div>
            ) : (
              <>
                <div className="explorar-cards-grid">
                  {recursos.map((item: any) => (
                    <div 
                      key={item.Recurso_ID} 
                      className="explorar-resource-card clickable-card-frame"
                      onClick={() => window.location.href = `/portal/recurso/${item.Recurso_ID}`}
                    >
                      <div className="card-cover-container">
                        {item.Imagen_url ? (
                          <img src={item.Imagen_url} alt={item.Titulo} />
                        ) : (
                          <div className="card-cover-fallback">
                            <IonIcon icon={obtenerIconoPorTipo(item.TipoRecurso)} />
                          </div>
                        )}
                        <span className="card-type-tag">{item.TipoRecurso}</span>
                      </div>

                      <div className="card-info-body">
                        <h4 className="card-resource-title" title={item.Titulo}>{item.Titulo}</h4>
                        <p className="card-resource-author">Por: {item.Autor || item.AutorTexto || 'Institucional'}</p>
                        <p className="card-resource-topic">Tema: {item.TemaRecurso || 'General'}</p>
                        <p className="card-resource-year">Año: {item.AnioPublicacion}</p>
                      </div>
                    </div>
                  ))}
                </div>

                <div className="explorar-pagination-footer">
                  <span className="pagination-text-info">Página {currentPage} de {lastPage}</span>
                  <div className="pagination-buttons-group">
                    <button className="btn-p-nav" disabled={currentPage === 1} onClick={() => handleCambiarPagina(currentPage - 1)}>
                      <IonIcon icon={chevronBackOutline} />
                    </button>
                    {getPageNumbers().map(pNum => (
                      <button 
                        key={pNum} 
                        className={`btn-p-number ${currentPage === pNum ? 'p-active' : ''}`}
                        onClick={() => handleCambiarPagina(pNum)}
                      >
                        {pNum}
                      </button>
                    ))}
                    <button className="btn-p-nav" disabled={currentPage === lastPage || lastPage === 0} onClick={() => handleCambiarPagina(currentPage + 1)}>
                      <IonIcon icon={chevronForwardOutline} />
                    </button>
                  </div>
                </div>
              </>
            )}
          </div>

        </div>
      </IonContent>
    </IonPage>
  );
};

export default Explorar;