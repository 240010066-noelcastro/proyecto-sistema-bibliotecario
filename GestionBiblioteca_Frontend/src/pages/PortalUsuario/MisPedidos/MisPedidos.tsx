import React, { useEffect, useState } from 'react';
import { IonContent, IonPage, IonIcon } from '@ionic/react';
import { arrowBackOutline, checkmarkCircleOutline, personOutline, cashOutline, gridOutline } from 'ionicons/icons';
import { useHistory } from 'react-router-dom';
// @ts-ignore
import api from '../../../services/api';
import './MisPedidos.css';

const MisPedidos: React.FC = () => {
  const [usuario, setUsuario] = useState<any>(null);
  const [activos, setActivos] = useState<any[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const history = useHistory();

  useEffect(() => {
    const userData = sessionStorage.getItem('usuario');
    if (userData) setUsuario(JSON.parse(userData));

    // 🏛️ REPARADO: Creamos el controlador para abortar la petición fantasma duplicada
    const abortController = new AbortController();
    let isMounted = true;

    const cargarPedidosActivos = async () => {
      try {
        const token = sessionStorage.getItem('token');
        const res = await api.get('/usuario/pedidos-activos', {
          headers: { Authorization: `Bearer ${token}` },
          signal: abortController.signal // 🏛️ Vinculamos el token de cancelación
        });
        if (res.data?.success && isMounted) {
          setActivos(res.data.data || []);
        }
      } catch (err: any) {
        // Ignoramos el log en consola si la petición simplemente fue abortada por React
        if (err.name !== 'CanceledError' && err.message !== 'canceled') {
          console.error("Error al cargar pedidos activos:", err);
        }
      } finally {
        if (isMounted && !abortController.signal.aborted) {
          setLoading(false);
        }
      }
    };
    cargarPedidosActivos();

    // 🏛️ Limpieza: Si el componente se desmonta o se duplica, cancela inmediatamente la petición previa
    return () => {
      isMounted = false;
      abortController.abort();
    };
  }, []);

  return (
    <IonPage>
      {/* NAVBAR GLOBAL UNIFICADO */}
      <div className="pedidos-navbar" style={{ gridTemplateColumns: '1fr 1fr 1fr' }}>
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
          <div className="panel-section-title">Mis pedidos</div>

          {loading ? (
            <div className="pedidos-loading-box"><div className="pedidos-spinner"></div></div>
          ) : activos.length === 0 ? (
            <div className="pedidos-empty-card">
              <IonIcon icon={checkmarkCircleOutline} style={{ color: '#2dd36f' }} />
              <h4>No tienes pedidos pendientes</h4>
              <p>Tu cuenta está libre de adeudos físicos.</p>
            </div>
          ) : (
            activos.map((item, i) => (
              <div 
                key={i} 
                className={`pedido-flex-card ${item.Estado === 'Vencido' ? 'border-alert-danger' : ''}`}
                onClick={() => {
                  if (item.recurso?.id) {
                    history.push(`/portal/recurso/${item.recurso.id}`);
                  }
                }}
                style={{ cursor: 'pointer' }}
                title="Haga clic en la tarjeta para ver detalles"
              >
                {/* Contenedor Visual de Portada */}
                <div className="card-media-aside">
                  {item.recurso?.Imagen ? (
                    <img src={item.recurso.Imagen} alt="Portada" />
                  ) : (
                    <div className="card-media-placeholder"><IonIcon icon={gridOutline} /></div>
                  )}
                </div>
                
                <div className="card-details-main">
                  <div className="pedido-main-info">
                    <span className={`status-pill-badge ${item.Estado === 'Vencido' ? 'pill-danger' : 'pill-process'}`}>
                      {item.Estado === 'Vencido' ? 'Con Retraso' : 'En Posesión / Activo'}
                    </span>
                    <h3 className="pedido-resource-title">{item.recurso?.Titulo}</h3>
                    <span className="pedido-right-type-label">Tipo: {item.recurso?.TipoRecurso || 'Material'}</span>
                  </div>

                  <div className="pedido-meta-grid">
                    <div className="meta-block">
                      <span className="meta-label">Fecha Salida</span>
                      <span className="meta-value">{item.FechaPrestamo}</span>
                    </div>
                    <div className="meta-block">
                      <span className="meta-label">Plazo Máximo Entrega</span>
                      <span className="meta-value font-weight-bold" style={{ color: item.Estado === 'Vencido' ? '#e00320' : '#111827' }}>{item.FechaLimite}</span>
                    </div>
                    <div className="meta-block">
                      <span className="meta-label">Quién te lo entregó</span>
                      <span className="meta-value icon-flex"><IonIcon icon={personOutline} /> {item.EntregadoPor}</span>
                    </div>
                    {parseFloat(item.MontoMulta) > 0 && (
                      <div className="meta-block">
                        <span className="meta-label">Multa por Retraso</span>
                        <span className="meta-value cost-alert"><IonIcon icon={cashOutline} /> ${item.MontoMulta} MXN</span>
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

export default MisPedidos;