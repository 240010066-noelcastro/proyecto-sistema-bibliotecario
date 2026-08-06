import React, { useEffect, useState } from 'react';
import { IonContent, IonPage, IonIcon } from '@ionic/react';
// 🏛️ NUEVO: Añadimos bulbOutline para activar el foquito de la guía interactiva
import { arrowBackOutline, alertCircleOutline, personOutline, gridOutline, searchOutline, closeOutline, bulbOutline } from 'ionicons/icons';
import { useHistory } from 'react-router-dom';
// @ts-ignore
import api from '../../../services/api';
import "./Historial.css";

const Historial: React.FC = () => {
  const [usuario, setUsuario] = useState<any>(null);
  const [historial, setHistorial] = useState<any[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  
  // Controles del Buscador Avanzado Manual
  const [inputValue, setInputValue] = useState<string>('');   
  const [activeQuery, setActiveQuery] = useState<string>(''); 
  
  // 🏛️ NUEVO: Estado para prender y apagar el panel de ayuda (Foquito)
  const [mostrarAyuda, setMostrarAyuda] = useState<boolean>(false);

  const history = useHistory();

  useEffect(() => {
    const userData = sessionStorage.getItem('usuario');
    if (userData) setUsuario(JSON.parse(userData));

    const abortController = new AbortController();
    let isMounted = true;

    const cargarHistorial = async () => {
      try {
        const token = sessionStorage.getItem('token');
        const res = await api.get('/usuario/prestamos-historial', { 
          headers: { Authorization: `Bearer ${token}` },
          signal: abortController.signal // 🏛️ Cancelación segura y estricta de Axios
        });
        if (res.data?.success && isMounted) {
          setHistorial(res.data.data || []);
        }
      } catch (err: any) {
        if (err.name !== 'CanceledError' && err.message !== 'canceled') {
          console.error("Error al cargar el historial:", err);
        }
      } finally {
        if (isMounted && !abortController.signal.aborted) {
          setLoading(false);
        }
      }
    };
    cargarHistorial();

    return () => {
      isMounted = false;
      abortController.abort();
    };
  }, []);

  // LÓGICA DE CONTROLADORES MANUALES
  const ejecutarFiltro = () => {
    setActiveQuery(inputValue);
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      ejecutarFiltro();
    }
  };

  const handleLimpiarBuscador = () => {
    setInputValue('');
    setActiveQuery('');
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const valor = e.target.value;
    setInputValue(valor);
    
    // 🏛️ REQUISITO: Si quita la última letra o carácter y el campo queda vacío, limpia el filtro automáticamente
    if (valor.trim() === '') {
      setActiveQuery('');
    }
  };

  // Filtrado reactivo en Frontend
  const historialFiltrado = historial.filter((item: any) => {
    // 🏛️ SANEADO: Convertimos a minúsculas y removemos acentos/diacríticos de la búsqueda
    const query = activeQuery.toLowerCase().trim().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    if (!query) return true; 

    // Función auxiliar para limpiar y comparar cualquier texto de las tarjetas de forma segura
    const cumpleFiltro = (texto: string) => 
      texto ? texto.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").includes(query) : false;

    return (
      cumpleFiltro(item.recurso?.Titulo) ||
      cumpleFiltro(item.EntregadoPor) ||
      cumpleFiltro(item.RecibidoPor) ||
      cumpleFiltro(item.FechaPrestamo) ||      
      cumpleFiltro(item.FechaDevolucion)      
    );
  });

  return (
    <IonPage>
      {/* NAVBAR GLOBAL UNIFICADO */}
      <div className="historial-navbar" style={{ gridTemplateColumns: '1fr 1fr 1fr' }}>
        <div className="navbar-left" style={{ justifySelf: 'start', display: 'flex', alignItems: 'center' }}>
          <button className="navbar-back-arrow-btn" onClick={() => window.history.back()} title="Regresar">
            <IonIcon icon={arrowBackOutline} />
          </button>
          <span className="university-logo-text">UPVE</span>
          <span className="university-brand-sub">BIBLIOTECA</span>
        </div>
        <div className="navbar-center-links">
          <span className="nav-top-link" onClick={() => history.push('/portal/inicio')}>Inicio</span>
          <span className="nav-top-link" onClick={() => history.push('/portal/explorar')}>Explorar</span>
          <span className="nav-top-link" onClick={() => history.push('/portal/mibiblioteca')}>Mi Biblioteca</span>
        </div>
        <div className="navbar-right" />
      </div>

      <IonContent className="pedidos-content-bg" fullscreen>
        <div className="pedidos-layout-container">
          <div className="panel-section-title">Mi Historial</div>

          {/* 🏛️ NUEVO: Fila superior del buscador estilizado en Píldora con Foquito alternable al extremo */}
          {!loading && historial.length > 0 && (
            <div className="historial-search-pill-container">
              
              <div className="search-pill-wrapper">
                {/* Lupa a la izquierda integrada dentro de la barra; al aplastarla filtra */}
                <button type="button" className="pill-left-search-btn" onClick={ejecutarFiltro} title="Buscar ahora">
                  <IonIcon icon={searchOutline} />
                </button>
                
                <input 
                  type="text"
                  placeholder="¿Qué libro, revista o recurso buscas hoy?..."
                  value={inputValue}
                  onChange={handleInputChange}
                  onKeyDown={handleKeyDown}
                  className="historial-search-pill-input"
                />

                {/* Tachita en el extremo derecho interno para borrar el texto */}
                {inputValue && (
                  <button type="button" className="pill-right-clear-btn" onClick={handleLimpiarBuscador} title="Limpiar texto">
                    <IonIcon icon={closeOutline} />
                  </button>
                )}
              </div>

              {/* El Foquito de ayuda a un lado de la barra de búsqueda */}
              <button 
                type="button" 
                className={`historial-help-bulb-toggle-btn ${mostrarAyuda ? 'bulb-on' : ''}`}
                onClick={() => setMostrarAyuda(!mostrarAyuda)}
                title="Mostrar u ocultar guías de búsqueda"
              >
                <IonIcon icon={bulbOutline} />
              </button>

            </div>
          )}

          {/* 🏛️ NUEVO: Caja de consejos redactados con la descripción exacta que me aprobaste */}
          {mostrarAyuda && !loading && historial.length > 0 && (
            <div className="historial-info-tooltip-card">
              <div className="tooltip-info-row">
                <strong>🔍 Búsqueda por nombre:</strong>
                <p>Escribe el título completo o palabras clave del libro o material, así como el personal de mostrador que te atendió.</p>
              </div>
              <div className="tooltip-info-row">
                <strong>📅 Búsqueda por fecha:</strong>
                <p>Puedes filtrar tus movimientos por año, mes o día. Nota: Para que el filtro funcione correctamente es necesario introducir los guiones separadores. <em>Ejemplo: 2026-07-02</em></p>
              </div>
            </div>
          )}

          {loading ? (
            <div className="pedidos-loading-box"><div className="pedidos-spinner"></div></div>
          ) : historial.length === 0 ? (
            <div className="pedidos-empty-card">
              <IonIcon icon={alertCircleOutline} style={{ color: '#94a3b8' }} />
              <h4>Sin registros en tu historial</h4>
              <p>No tienes devoluciones registradas en este periodo.</p>
            </div>
          ) : historialFiltrado.length === 0 ? (
            <div className="pedidos-empty-card">
              <IonIcon icon={alertCircleOutline} style={{ color: '#64748b' }} />
              <h4>Sin resultados coincidentes</h4>
              <p>No encontramos registros en la bitácora que coincidan con la palabra "{activeQuery}".</p>
            </div>
          ) : (
            historialFiltrado.map((item, i) => (
              <div 
                key={`${item.recurso?.id}-${item.FechaPrestamo}-${i}`} 
                className="pedido-flex-card past-card"
                onClick={() => {
                  if (item.recurso?.id) {
                    history.push(`/portal/recurso/${item.recurso.id}`);
                  }
                }}
                style={{ cursor: 'pointer' }}
                title="Haga clic en la tarjeta para ver detalles"
              >
                <div className="card-media-aside">
                  {item.recurso?.Imagen ? (
                    <img src={item.recurso.Imagen} alt="Portada" />
                  ) : (
                    <div className="card-media-placeholder"><IonIcon icon={gridOutline} /></div>
                  )}
                </div>

                <div className="card-details-main">
                  <div className="pedido-main-info">
                    <span className={`status-pill-badge ${item.Estado === 'Devuelto con Retraso' ? 'pill-warning' : 'pill-success'}`}>
                      {item.Estado === 'Devuelto con Retraso' ? 'Entregado con Retraso' : 'Entregado a Tiempo'}
                    </span>
                    <h3 className="pedido-resource-title">{item.recurso?.Titulo}</h3>
                  </div>
                  
                  <div className="pedido-meta-grid">
                    <div className="meta-block">
                      <span className="meta-label">Fecha Préstamo</span>
                      <span className="meta-value">{item.FechaPrestamo}</span>
                    </div>
                    <div className="meta-block">
                      <span className="meta-label">Fecha Entrega</span>
                      <span className="meta-value">{item.FechaDevolucion}</span>
                    </div>
                    <div className="meta-block">
                      <span className="meta-label">Tiempo de Uso</span>
                      <span className="meta-value font-weight-bold" style={{ color: '#582c83' }}>{item.DiasPrestamo} días totales</span>
                    </div>
                    <div className="meta-block">
                      <span className="meta-label">Entregó</span>
                      <span className="meta-value icon-flex"><IonIcon icon={personOutline} /> {item.EntregadoPor}</span>
                    </div>
                    <div className="meta-block">
                      <span className="meta-label">Recibió</span>
                      <span className="meta-value icon-flex"><IonIcon icon={personOutline} /> {item.RecibidoPor}</span>
                    </div>
                    {parseFloat(item.MontoMulta) > 0 && (
                      <div className="meta-block">
                        <span className="meta-label">Multa Pagada</span>
                        <span className="meta-value paid-status">${item.MontoMulta} MXN</span>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      </IonContent>
    </IonPage>
  );
};

export default Historial;