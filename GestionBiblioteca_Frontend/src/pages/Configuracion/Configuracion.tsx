import React, { useState, useRef } from 'react';
import { IonContent, IonPage, IonIcon, useIonViewWillEnter, IonButton } from '@ionic/react';
import { settingsOutline, checkmarkCircleOutline, warningOutline, documentTextOutline, saveOutline, createOutline, closeOutline, closeCircleOutline, trashOutline } from 'ionicons/icons';
// @ts-ignore
import api from '../../services/api'; 
import './Configuracion.css'; 

const Configuracion: React.FC = () => {
  const [isInitialLoading, setIsInitialLoading] = useState(true);
  const [isProcessing, setIsProcessing] = useState(false);
  const abortControllerRef = useRef<AbortController | null>(null);

  const [toast, setToast] = useState({ show: false, message: '', type: 'success' });

  // ESTADOS DE LOS TEXTOS SEPARADOS
  const [mensajeLibro, setMensajeLibro] = useState('');
  const [mensajeRevista, setMensajeRevista] = useState('');
  const [mensajeEnciclopedia, setMensajeEnciclopedia] = useState('');
  const [mensajeTesis, setMensajeTesis] = useState('');

  // ESTADOS DE EDICIÓN INDIVIDUALES
  const [editLibro, setEditLibro] = useState(false);
  const [editRevista, setEditRevista] = useState(false);
  const [editEnciclopedia, setEditEnciclopedia] = useState(false);
  const [editTesis, setEditTesis] = useState(false);

  // INTERFAZ Y MODELO DE ÍTEMS INTERACTIVOS CON SOPORTE DE OCULTACIÓN GLOBAL
  interface ConfigItem { label: string; action?: string; isNative: boolean; hidden?: boolean; }
  const [tiposAutor, setTiposAutor] = useState<ConfigItem[]>([]);
  const [estadosFisicos, setEstadosFisicos] = useState<ConfigItem[]>([]);
  const [disponibilidades, setDisponibilidades] = useState<ConfigItem[]>([]);
  const [estadosPrestamo, setEstadosPrestamo] = useState<ConfigItem[]>([]);
  const [estadosSancion, setEstadosSancion] = useState<ConfigItem[]>([]);
  const [tiposSancion, setTiposSancion] = useState<ConfigItem[]>([]);

  // FLAGS DE MODIFICACIÓN INDEPENDIENTES (DIRTY FLAGS)
  const [isCatalogoDirty, setIsCatalogoDirty] = useState(false);
  const [isAutoresDirty, setIsAutoresDirty] = useState(false);
  const [isInventarioDirty, setIsInventarioDirty] = useState(false);
  const [isPrestamosDirty, setIsPrestamosDirty] = useState(false);
  const [isSancionesDirty, setIsSancionesDirty] = useState(false);

  // BANDERAS DE EDICIÓN INDEPENDIENTES POR CADA RECUADRO SECCIÓN DE AJUSTE
  const [editTiposAutor, setEditTiposAutor] = useState(false);
  const [editEstadosFisicos, setEditEstadosFisicos] = useState(false);
  const [editDisponibilidades, setEditDisponibilidades] = useState(false);
  const [editEstadosPrestamo, setEditEstadosPrestamo] = useState(false);
  const [editEstadosSancion, setEditEstadosSancion] = useState(false);
  const [editTiposSancion, setEditTiposSancion] = useState(false);

  // 🏛️ UNIVERSAL: Control de edición y selectores individuales por cada Acción Lógica Base
  const [editEstadosDefecto, setEditEstadosDefecto] = useState(false);
  // Raíces del Inventario
  const [defectoDispDisponible, setDefectoDispDisponible] = useState('Disponible');
  const [defectoDispPrestado, setDefectoDispPrestado] = useState('Prestado');
  const [defectoDispExtraviado, setDefectoDispExtraviado] = useState('Extraviado');
  const [defectoDispBaja, setDefectoDispBaja] = useState('Baja');
  // Raíces de Préstamos
  const [defectoPrestamoActivo, setDefectoPrestamoActivo] = useState('Activo');
  const [defectoPrestamoDevuelto, setDefectoPrestamoDevuelto] = useState('Devuelto');
  const [defectoPrestamoAtrasado, setDefectoPrestamoAtrasado] = useState('Atrasado');
  const [defectoPrestamoSancion, setDefectoPrestamoSancion] = useState('Finalizado (Sanción)');
  
  // 🏛️ NUEVO: Raíces de Sanciones Completas
  const [defectoSancionPendiente, setDefectoSancionPendiente] = useState('Pendiente');
  const [defectoSancionPagado, setDefectoSancionPagado] = useState('Pagado');
  const [defectoSancionCondonado, setDefectoSancionCondonado] = useState('Condonado');

  // 🏛️ INTERFAZ PARA DEFINIR PROPIEDADES OPCIONALES
  interface ConfirmDialogType {
    show: boolean;
    title: string;
    message: string;
    confirmText?: string;   // 💡 El signo "?" significa opcional
    confirmColor?: string;  // 💡 Así no marcará error en las otras secciones
    onConfirm: () => void;
  }

  // ALERTA DE SEGURIDAD ADAPTATIVA (SOPORTA BORRADOS Y ACTUALIZACIONES)
  const [confirmDialog, setConfirmDialog] = useState<ConfirmDialogType>({ 
    show: false, 
    title: '', 
    message: '', 
    confirmText: 'Confirmar', 
    confirmColor: '#ef4444', 
    onConfirm: () => {} 
  });

  // ESTADOS DE CAPTURA TEMPORAL PARA NUEVOS ELEMENTOS
  const [nuevoTexto, setNuevoTexto] = useState<{ [key: string]: string }>({});
  const [nuevaAccion, setNuevaAccion] = useState<{ [key: string]: string }>({});

  const showToast = (message: string, type: 'success' | 'danger' = 'success') => {
    setToast({ show: true, message, type });
    setTimeout(() => setToast({ show: false, message: '', type: 'success' }), 4000);
  };

  useIonViewWillEnter(() => {
    const loadConfiguraciones = async () => {
      setIsInitialLoading(true);
      
      setEditLibro(false);
      setEditRevista(false);
      setEditEnciclopedia(false);
      setEditTesis(false);
      setEditEstadosDefecto(false);

      if (abortControllerRef.current) abortControllerRef.current.abort();
      abortControllerRef.current = new AbortController();

      try {
        // ENFOQUE OPTIMIZADO: CARGA EN PARALELO MEDIANTE PROMISE.ALL
        const [resCatalogo, resAutores, resInventario, resPrestamos, resSanciones]: any = await Promise.all([
          api.get('/configuraciones/Catalogo', { signal: abortControllerRef.current.signal }),
          api.get('/configuraciones/Autores', { signal: abortControllerRef.current.signal }).catch(() => ({ data: {} })),
          api.get('/configuraciones/Inventario', { signal: abortControllerRef.current.signal }).catch(() => ({ data: {} })),
          api.get('/configuraciones/Prestamos', { signal: abortControllerRef.current.signal }).catch(() => ({ data: {} })),
          api.get('/configuraciones/Sanciones', { signal: abortControllerRef.current.signal }).catch(() => ({ data: {} }))
        ]);
        
        const dataCat = resCatalogo.data?.data || {};
        setMensajeLibro(dataCat.mensaje_legal_libro || 'La Universidad Politécnica del Valle del Évora (UPVE) se deslinda de los derechos de autor de este libro...');
        setMensajeRevista(dataCat.mensaje_legal_revista || 'La UPVE no posee los derechos de propiedad intelectual de este artículo o revista...');
        setMensajeEnciclopedia(dataCat.mensaje_legal_enciclopedia || 'La UPVE se deslinda de la autoría y derechos de esta enciclopedia...');
        setMensajeTesis(dataCat.mensaje_legal_tesis || 'El repositorio institucional de la UPVE autoriza el alojamiento local...');

        /* 🏛️ SOLUCIÓN INTEGRADA: Lee el arreglo dataCat que tienes en la línea 120 sin importar el orden de las funciones de abajo */
        const _get = (k: string, d: string) => Array.isArray(dataCat) ? (dataCat.find((r: any) => r.Clave === k)?.Valor || d) : (dataCat[k] || d);

        setDefectoDispDisponible(_get('defecto_disp_Disponible', 'Disponible'));
        setDefectoDispPrestado(_get('defecto_disp_Prestado', 'Prestado'));
        setDefectoDispExtraviado(_get('defecto_disp_Extraviado', 'Extraviado'));
        setDefectoDispBaja(_get('defecto_disp_Baja', 'Baja'));
        
        setDefectoPrestamoActivo(dataCat['defecto_prestamo_Activo'] || 'Activo');
        setDefectoPrestamoDevuelto(dataCat['defecto_prestamo_Devuelto'] || 'Devuelto');
        setDefectoPrestamoAtrasado(dataCat['defecto_prestamo_Atrasado'] || 'Atrasado');
        setDefectoPrestamoSancion(dataCat['defecto_prestamo_Finalizado (Sanción)'] || 'Finalizado (Sanción)');
        
        // 🏛️ NUEVO: Sincronizar estados iniciales de todas las acciones de sanciones
        setDefectoSancionPendiente(dataCat['defecto_sancion_Pendiente'] || 'Pendiente');
        setDefectoSancionPagado(dataCat['defecto_sancion_Pagado'] || 'Pagado');
        setDefectoSancionCondonado(dataCat['defecto_sancion_Condonado'] || 'Condonado');

        // EXTRACTOR ADAPTATIVO: VALIDA SI EL BACKEND RESPONDE CON LLAVE-VALOR O COLECCIÓN INDEXADA DE FILAS
        const getRawValue = (resObj: any, clave: string) => {
          const content = resObj.data?.data;
          if (!content) return null;
          if (Array.isArray(content)) {
            const row = content.find((r: any) => r.Clave === clave);
            return row ? row.Valor : null;
          }
          return content[clave] || null;
        };

        // REQUERIMIENTO 3: EL NORMALIZADOR ADAPTATIVO CONVIERTE TEXTO PLANO ANTIGUO A OBJETOS OPERATIVOS EVITANDO CAMPOS VACÍOS
        const parseConfig = (resObj: any, clave: string, fallback: ConfigItem[]) => {
          const raw = getRawValue(resObj, clave);
          if (!raw) return fallback;
          const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;
          
          if (Array.isArray(parsed)) {
            return parsed.map((item: any) => {
              if (typeof item === 'string') {
                const nativosGlobales = [
                  'Personal', 'Corporativo / Institucional', 'Nuevo', 'Bueno', 'Regular', 'Malo / Dañado',
                  'Disponible', 'Prestado', 'Extraviado', 'Baja', 'Disponible en Estante', 'Dado de Baja',
                  'Activo', 'Devuelto', 'Atrasado', 'Finalizado (Sanción)', 'Pendiente', 'Pagado', 'Condonado',
                  'Material Dañado', 'Material Extraviado'
                ];
                return { label: item, action: item, isNative: nativosGlobales.includes(item), hidden: false };
              }
              return {
                label: item.label || '',
                action: item.action || item.label || '',
                isNative: !!item.isNative,
                hidden: !!item.hidden
              };
            });
          }
          return fallback;
        };

        setTiposAutor(parseConfig(resAutores, 'tipos_autor', [
          { label: 'Personal', isNative: true }, { label: 'Corporativo / Institucional', isNative: true }
        ]));
        setEstadosFisicos(parseConfig(resInventario, 'estados_fisicos', [
          { label: 'Nuevo', isNative: true }, { label: 'Bueno', isNative: true }, { label: 'Regular', isNative: true }, { label: 'Malo / Dañado', isNative: true }
        ]));
        setDisponibilidades(parseConfig(resInventario, 'disponibilidades', [
          { label: 'Disponible en Estante', action: 'Disponible', isNative: true }, { label: 'Prestado', action: 'Prestado', isNative: true },
          { label: 'En Mantenimiento', action: 'Mantenimiento', isNative: true }, { label: 'Extraviado', action: 'Extraviado', isNative: true }, { label: 'Dado de Baja', action: 'Baja', isNative: true }
        ]));
        setEstadosPrestamo(parseConfig(resPrestamos, 'estados_prestamo', [
          { label: 'Activo', action: 'Activo', isNative: true }, { label: 'Devuelto', action: 'Devuelto', isNative: true },
          { label: 'Atrasado', action: 'Atrasado', isNative: true }, { label: 'Finalizado (Sanción)', action: 'Finalizado (Sanción)', isNative: true }
        ]));
        setEstadosSancion(parseConfig(resSanciones, 'estados_sancion', [
          { label: 'Pendiente', action: 'Pendiente', isNative: true }, { label: 'Pagado', action: 'Pagado', isNative: true }, { label: 'Condonado (Perdonado)', action: 'Condonado', isNative: true }
        ]));
        setTiposSancion(parseConfig(resSanciones, 'tipos_sancion', [
          { label: 'Material Dañado', isNative: true }, { label: 'Material Extraviado', isNative: true }
        ]));

        setIsCatalogoDirty(false); setIsAutoresDirty(false); setIsInventarioDirty(false); setIsPrestamosDirty(false); setIsSancionesDirty(false);
      } catch (err: any) {
        if (err.name !== 'CanceledError' && err.message !== 'canceled') {
          console.error("Error al cargar configuraciones:", err);
          showToast("Error al cargar las configuraciones", "danger");
        }
      } finally {
        setIsInitialLoading(false);
      }
    };
    
    loadConfiguraciones();
  });

  // 🏛️ RESTAURADO: Función individual para procesar el guardado inmediato de los avisos legales
  const saveAvisoLegal = async (clave: string, valor: string, setEdit: React.Dispatch<React.SetStateAction<boolean>>) => {
    if (!valor.trim()) {
      return showToast("El mensaje legal no puede quedar en blanco.", "danger");
    }
    setIsProcessing(true);
    try {
      await api.post('/configuraciones', { Modulo: 'Catalogo', Clave: clave, Valor: valor });
      showToast("¡Aviso legal guardado exitosamente!", "success");
      setEdit(false);
    } catch (error) {
      console.error(error);
      showToast("Error al guardar el aviso legal.", "danger");
    } finally {
      setIsProcessing(false);
    }
  };

  // 🏛️ NUEVO: Manejador controlado con alerta de confirmación previa para los estados por defecto
  const handleSaveDefecto = (key: string, newValue: string, setter: (v: string) => void) => {
    setConfirmDialog({
      show: true,
      title: 'Modificar Flujo Automatizado',
      message: `¿Estás seguro de cambiar el estado por defecto actual a "${newValue}"? Esto afectará el estatus que asignará el servidor de manera automática en las siguientes operaciones.`,
      confirmText: 'Sí, cambiar', // 🎨 Texto personalizado no agresivo
      confirmColor: '#582c83',    // 🎨 Color morado UPVE en vez de rojo de borrado
      onConfirm: async () => {
        setConfirmDialog({ show: false, title: '', message: '', confirmText: 'Confirmar', confirmColor: '#ef4444', onConfirm: () => {} });
        setIsProcessing(true);
        try {
          await api.post('/configuraciones', { Modulo: 'Catalogo', Clave: key, Valor: newValue });
          showToast("¡Estado automático actualizado exitosamente!", "success");
          setter(newValue); // Actualiza la vista solo si el backend confirmó el guardado
        } catch (error) {
          console.error(error);
          showToast("Error al guardar la configuración automática.", "danger");
        } finally {
          setIsProcessing(false);
        }
      }
    });
  };

  const saveConfig = async () => {
    setIsProcessing(true);
    try {
      // CORRECCIÓN BASE DE DATOS: Enviamos los objetos limpios directamente para que Laravel y MySQL los guarden de forma nativa en la columna JSON
      if (isAutoresDirty) {
        await api.post('/configuraciones', { Modulo: 'Autores', Clave: 'tipos_autor', Valor: tiposAutor });
        setIsAutoresDirty(false);
      }

      if (isInventarioDirty) {
        await api.post('/configuraciones', { Modulo: 'Inventario', Clave: 'estados_fisicos', Valor: estadosFisicos });
        await api.post('/configuraciones', { Modulo: 'Inventario', Clave: 'disponibilidades', Valor: disponibilidades });
        setIsInventarioDirty(false);
      }

      if (isPrestamosDirty) {
        await api.post('/configuraciones', { Modulo: 'Prestamos', Clave: 'estados_prestamo', Valor: estadosPrestamo });
        setIsPrestamosDirty(false);
      }

      if (isSancionesDirty) {
        await api.post('/configuraciones', { Modulo: 'Sanciones', Clave: 'estados_sancion', Valor: estadosSancion });
        await api.post('/configuraciones', { Modulo: 'Sanciones', Clave: 'tipos_sancion', Valor: tiposSancion });
        setIsSancionesDirty(false);
      }

      showToast("¡Configuraciones guardadas exitosamente!", "success");
      setEditLibro(false); setEditRevista(false); setEditEnciclopedia(false); setEditTesis(false);
      
      // Bloquea de forma individual cada sección al concluir el guardado de datos
      setEditTiposAutor(false);
      setEditEstadosFisicos(false);
      setEditDisponibilidades(false);
      setEditEstadosPrestamo(false);
      setEditEstadosSancion(false);
      setEditTiposSancion(false);
    } catch (error) {
      console.error(error);
      showToast("Error al guardar los cambios en el servidor.", "danger");
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <IonPage>
      {(isInitialLoading || isProcessing) && (
          <div className="main-loader-overlay">
              <div className="main-loader-spinner"></div>
              <p>{isInitialLoading ? 'Cargando configuraciones...' : 'Guardando cambios...'}</p>
          </div>
      )}

      <IonContent className="config-bg" style={{ position: 'relative' }}>
        
        <div className={`toast-notification ${toast.show ? 'show' : ''} ${toast.type}`}>
            <IonIcon icon={toast.type === 'success' ? checkmarkCircleOutline : warningOutline} />
            <span>{toast.message}</span>
        </div>

        <div className="config-layout">
          
          <div className="main-top-header">
            <div>
              <h1>
                <IonIcon icon={settingsOutline} className="header-icon" /> Ajustes y Configuración
              </h1>
              <p>Administración de textos legales y avisos del sistema.</p>
            </div>
          </div>

          <div className="config-form-card">
            {/* 🏛️ INDICADOR DE MÓDULO */}
            <span style={{ display: 'inline-block', background: '#f3e8ff', color: '#582c83', padding: '4px 10px', borderRadius: '6px', fontSize: '11px', fontWeight: 'bold', marginBottom: '8px', textTransform: 'uppercase' }}>
              Módulo: Catálogo
            </span>

            <div className="config-card-header">
                <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <IonIcon icon={documentTextOutline} className="section-icon" />
                  <h3>Textos Legales del Catálogo</h3>
                </div>
            </div>
            
            <p className="config-description">
              Modifica los mensajes de advertencia y derechos de autor que se muestran al registrar recursos bibliográficos o tesis institucionales.
            </p>

            {/* SECCIÓN LIBROS */}
            <div className="form-row" style={{ marginTop: '20px' }}>
              <div className="form-group flex-1">
                <div className="field-header">
                  <label>AVISO PARA LIBROS *</label>
                  <IonButton fill="clear" className="btn-edit-toggle-small" onClick={() => setEditLibro(!editLibro)} title={editLibro ? "Cancelar edición" : "Editar texto"}>
                    <IonIcon icon={editLibro ? closeOutline : createOutline} style={{ color: editLibro ? '#ef4444' : '#7c3aed' }} />
                  </IonButton>
                </div>
                <textarea 
                  className="custom-textarea" 
                  rows={2}
                  value={mensajeLibro} 
                  onChange={e => setMensajeLibro(e.target.value)} 
                  disabled={!editLibro}
                />
                {editLibro && (
                  <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '10px' }}>
                    <button className="btn-guardar-inline" style={{ height: '36px', padding: '0 20px', background: '#582c83', fontSize: '12.5px' }} onClick={() => saveAvisoLegal('mensaje_legal_libro', mensajeLibro, setEditLibro)} disabled={isProcessing}>
                      <IonIcon icon={saveOutline} style={{ marginRight: '6px', verticalAlign: 'middle' }} /> Guardar Aviso
                    </button>
                  </div>
                )}
              </div>
            </div>

            {/* SECCIÓN REVISTAS */}
            <div className="form-row" style={{ marginTop: '20px' }}>
              <div className="form-group flex-1">
                <div className="field-header">
                  <label>AVISO PARA REVISTAS Y ARTÍCULOS *</label>
                  <IonButton fill="clear" className="btn-edit-toggle-small" onClick={() => setEditRevista(!editRevista)} title={editRevista ? "Cancelar edición" : "Editar texto"}>
                    <IonIcon icon={editRevista ? closeOutline : createOutline} style={{ color: editRevista ? '#ef4444' : '#7c3aed' }} />
                  </IonButton>
                </div>
                <textarea 
                  className="custom-textarea" 
                  rows={2}
                  value={mensajeRevista} 
                  onChange={e => setMensajeRevista(e.target.value)} 
                  disabled={!editRevista}
                />
                {editRevista && (
                  <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '10px' }}>
                    <button className="btn-guardar-inline" style={{ height: '36px', padding: '0 20px', background: '#582c83', fontSize: '12.5px' }} onClick={() => saveAvisoLegal('mensaje_legal_revista', mensajeRevista, setEditRevista)} disabled={isProcessing}>
                      <IonIcon icon={saveOutline} style={{ marginRight: '6px', verticalAlign: 'middle' }} /> Guardar Aviso
                    </button>
                  </div>
                )}
              </div>
            </div>

            {/* SECCIÓN ENCICLOPEDIAS */}
            <div className="form-row" style={{ marginTop: '20px' }}>
              <div className="form-group flex-1">
                <div className="field-header">
                  <label>AVISO PARA ENCICLOPEDIAS Y DICCIONARIOS *</label>
                  <IonButton fill="clear" className="btn-edit-toggle-small" onClick={() => setEditEnciclopedia(!editEnciclopedia)} title={editEnciclopedia ? "Cancelar edición" : "Editar texto"}>
                    <IonIcon icon={editEnciclopedia ? closeOutline : createOutline} style={{ color: editEnciclopedia ? '#ef4444' : '#7c3aed' }} />
                  </IonButton>
                </div>
                <textarea 
                  className="custom-textarea" 
                  rows={2}
                  value={mensajeEnciclopedia} 
                  onChange={e => setMensajeEnciclopedia(e.target.value)} 
                  disabled={!editEnciclopedia}
                />
                {editEnciclopedia && (
                  <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '10px' }}>
                    <button className="btn-guardar-inline" style={{ height: '36px', padding: '0 20px', background: '#582c83', fontSize: '12.5px' }} onClick={() => saveAvisoLegal('mensaje_legal_enciclopedia', mensajeEnciclopedia, setEditEnciclopedia)} disabled={isProcessing}>
                      <IonIcon icon={saveOutline} style={{ marginRight: '6px', verticalAlign: 'middle' }} /> Guardar Aviso
                    </button>
                  </div>
                )}
              </div>
            </div>

            {/* SECCIÓN TESIS */}
            <div className="form-row" style={{ marginTop: '20px' }}>
              <div className="form-group flex-1">
                <div className="field-header">
                  <label>AUTORIZACIÓN INSTITUCIONAL PARA TESIS (ARCHIVOS LOCALES) *</label>
                  <IonButton fill="clear" className="btn-edit-toggle-small" onClick={() => setEditTesis(!editTesis)} title={editTesis ? "Cancelar edición" : "Editar texto"}>
                    <IonIcon icon={editTesis ? closeOutline : createOutline} style={{ color: editTesis ? '#ef4444' : '#7c3aed' }} />
                  </IonButton>
                </div>
                <textarea 
                  className="custom-textarea" 
                  rows={2}
                  value={mensajeTesis} 
                  onChange={e => setMensajeTesis(e.target.value)} // 🏛️ CORREGIDO
                  disabled={!editTesis}
                />
                {editTesis && (
                  <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '10px' }}>
                    <button className="btn-guardar-inline" style={{ height: '36px', padding: '0 20px', background: '#582c83', fontSize: '12.5px' }} onClick={() => saveAvisoLegal('mensaje_legal_tesis', mensajeTesis, setEditTesis)} disabled={isProcessing}>
                      <IonIcon icon={saveOutline} style={{ marginRight: '6px', verticalAlign: 'middle' }} /> Guardar Aviso
                    </button>
                  </div>
                )}
              </div>
            </div>

            {/* 🏛️ ARQUITECTURA ELÁSTICA UNIVERSAL: Selectores dinámicos por cada Acción Lógica Base */}
            <div className="config-card-header" style={{ marginTop: '35px', borderTop: '1px solid #e5e7eb', paddingTop: '25px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <IonIcon icon={settingsOutline} className="section-icon" />
                    <h3>Estados Automatizados por Defecto</h3>
                  </div>
                  <IonButton 
                    fill="clear" 
                    className="btn-edit-toggle-small" 
                    onClick={() => setEditEstadosDefecto(!editEstadosDefecto)} 
                    title={editEstadosDefecto ? "Bloquear edición de sección" : "Editar estados por defecto"}
                  >
                    <IonIcon icon={editEstadosDefecto ? closeOutline : createOutline} style={{ color: editEstadosDefecto ? '#ef4444' : '#7c3aed', fontSize: '20px' }} />
                  </IonButton>
                </div>
            </div>
            <p className="config-description">
              Asigna qué etiqueta personalizada se aplicará por defecto cada vez que los procesos automatizados del sistema requieran transicionar hacia una acción lógica base.
            </p>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '20px', marginTop: '15px' }}>
              
              {/* BLOQUE INVENTARIO */}
              <div style={{ background: '#f9fafb', padding: '20px', borderRadius: '12px', border: '1px solid #e5e7eb' }}>
                <span style={{ display: 'inline-block', color: '#582c83', fontSize: '11px', fontWeight: 'bold', marginBottom: '12px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                  Módulo: Inventario (Disponibilidades)
                </span>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                  <div>
                    <label style={{ display: 'block', fontSize: '12.5px', fontWeight: '600', color: '#4b5563', marginBottom: '4px' }}>Acción Base: "Disponible"</label>
                    <select className="custom-input" style={{ backgroundColor: editEstadosDefecto ? '#ffffff' : '#f3f4f6' }} value={defectoDispDisponible} disabled={!editEstadosDefecto} onChange={e => handleSaveDefecto('defecto_disp_Disponible', e.target.value, setDefectoDispDisponible)}>
                      {disponibilidades.filter(d => d.action === 'Disponible' && !d.hidden).map((d, i) => <option key={i} value={d.label}>{d.label}</option>)}
                    </select>
                  </div>
                  <div>
                    <label style={{ display: 'block', fontSize: '12.5px', fontWeight: '600', color: '#4b5563', marginBottom: '4px' }}>Acción Base: "Prestado"</label>
                    <select className="custom-input" style={{ backgroundColor: editEstadosDefecto ? '#ffffff' : '#f3f4f6' }} value={defectoDispPrestado} disabled={!editEstadosDefecto} onChange={e => handleSaveDefecto('defecto_disp_Prestado', e.target.value, setDefectoDispPrestado)}>
                      {disponibilidades.filter(d => d.action === 'Prestado' && !d.hidden).map((d, i) => <option key={i} value={d.label}>{d.label}</option>)}
                    </select>
                  </div>
                  <div>
                    <label style={{ display: 'block', fontSize: '12.5px', fontWeight: '600', color: '#4b5563', marginBottom: '4px' }}>Acción Base: "Extraviado"</label>
                    <select className="custom-input" style={{ backgroundColor: editEstadosDefecto ? '#ffffff' : '#f3f4f6' }} value={defectoDispExtraviado} disabled={!editEstadosDefecto} onChange={e => handleSaveDefecto('defecto_disp_Extraviado', e.target.value, setDefectoDispExtraviado)}>
                      {disponibilidades.filter(d => d.action === 'Extraviado' && !d.hidden).map((d, i) => <option key={i} value={d.label}>{d.label}</option>)}
                    </select>
                  </div>
                  <div>
                    <label style={{ display: 'block', fontSize: '12.5px', fontWeight: '600', color: '#4b5563', marginBottom: '4px' }}>Acción Base: "Baja"</label>
                    <select className="custom-input" style={{ backgroundColor: editEstadosDefecto ? '#ffffff' : '#f3f4f6' }} value={defectoDispBaja} disabled={!editEstadosDefecto} onChange={e => handleSaveDefecto('defecto_disp_Baja', e.target.value, setDefectoDispBaja)}>
                      {disponibilidades.filter(d => d.action === 'Baja' && !d.hidden).map((d, i) => <option key={i} value={d.label}>{d.label}</option>)}
                    </select>
                  </div>
                </div>
              </div>

              {/* BLOQUE PRÉSTAMOS */}
              <div style={{ background: '#f9fafb', padding: '20px', borderRadius: '12px', border: '1px solid #e5e7eb' }}>
                <span style={{ display: 'inline-block', color: '#582c83', fontSize: '11px', fontWeight: 'bold', marginBottom: '12px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                  Módulo: Préstamos (Estados del Trámite)
                </span>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                  <div>
                    <label style={{ display: 'block', fontSize: '12.5px', fontWeight: '600', color: '#4b5563', marginBottom: '4px' }}>Acción Base: "Activo"</label>
                    <select className="custom-input" style={{ backgroundColor: editEstadosDefecto ? '#ffffff' : '#f3f4f6' }} value={defectoPrestamoActivo} disabled={!editEstadosDefecto} onChange={e => handleSaveDefecto('defecto_prestamo_Activo', e.target.value, setDefectoPrestamoActivo)}>
                      {estadosPrestamo.filter(p => p.action === 'Activo' && !p.hidden).map((p, i) => <option key={i} value={p.label}>{p.label}</option>)}
                    </select>
                  </div>
                  <div>
                    <label style={{ display: 'block', fontSize: '12.5px', fontWeight: '600', color: '#4b5563', marginBottom: '4px' }}>Acción Base: "Devuelto"</label>
                    <select className="custom-input" style={{ backgroundColor: editEstadosDefecto ? '#ffffff' : '#f3f4f6' }} value={defectoPrestamoDevuelto} disabled={!editEstadosDefecto} onChange={e => handleSaveDefecto('defecto_prestamo_Devuelto', e.target.value, setDefectoPrestamoDevuelto)}>
                      {estadosPrestamo.filter(p => p.action === 'Devuelto' && !p.hidden).map((p, i) => <option key={i} value={p.label}>{p.label}</option>)}
                    </select>
                  </div>
                  <div>
                    <label style={{ display: 'block', fontSize: '12.5px', fontWeight: '600', color: '#4b5563', marginBottom: '4px' }}>Acción Base: "Atrasado"</label>
                    <select className="custom-input" style={{ backgroundColor: editEstadosDefecto ? '#ffffff' : '#f3f4f6' }} value={defectoPrestamoAtrasado} disabled={!editEstadosDefecto} onChange={e => handleSaveDefecto('defecto_prestamo_Atrasado', e.target.value, setDefectoPrestamoAtrasado)}>
                      {estadosPrestamo.filter(p => p.action === 'Atrasado' && !p.hidden).map((p, i) => <option key={i} value={p.label}>{p.label}</option>)}
                    </select>
                  </div>
                  <div>
                    <label style={{ display: 'block', fontSize: '12.5px', fontWeight: '600', color: '#4b5563', marginBottom: '4px' }}>Acción Base: "Finalizado (Sanción)"</label>
                    <select className="custom-input" style={{ backgroundColor: editEstadosDefecto ? '#ffffff' : '#f3f4f6' }} value={defectoPrestamoSancion} disabled={!editEstadosDefecto} onChange={e => handleSaveDefecto('defecto_prestamo_Finalizado (Sanción)', e.target.value, setDefectoPrestamoSancion)}>
                      {estadosPrestamo.filter(p => p.action === 'Finalizado (Sanción)' && !p.hidden).map((p, i) => <option key={i} value={p.label}>{p.label}</option>)}
                    </select>
                  </div>
                </div>
              </div>

              {/* 🏛️ NUEVO: BLOQUE SANCIONES (Simetría completa con todas sus acciones raíz) */}
              <div style={{ background: '#f9fafb', padding: '20px', borderRadius: '12px', border: '1px solid #e5e7eb' }}>
                <span style={{ display: 'inline-block', color: '#582c83', fontSize: '11px', fontWeight: 'bold', marginBottom: '12px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                  Módulo: Sanciones (Condiciones de Cobro)
                </span>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                  {/* 1. RAÍZ PENDIENTE */}
                  <div>
                    <label style={{ display: 'block', fontSize: '12.5px', fontWeight: '600', color: '#4b5563', marginBottom: '4px' }}>Acción Base: "Pendiente"</label>
                    <select className="custom-input" style={{ backgroundColor: editEstadosDefecto ? '#ffffff' : '#f3f4f6' }} value={defectoSancionPendiente} disabled={!editEstadosDefecto} onChange={e => handleSaveDefecto('defecto_sancion_Pendiente', e.target.value, setDefectoSancionPendiente)}>
                      {estadosSancion.filter(s => s.action === 'Pendiente' && !s.hidden).map((s, i) => <option key={i} value={s.label}>{s.label}</option>)}
                    </select>
                  </div>

                  {/* 2. RAÍZ PAGADO */}
                  <div>
                    <label style={{ display: 'block', fontSize: '12.5px', fontWeight: '600', color: '#4b5563', marginBottom: '4px' }}>Acción Base: "Pagado"</label>
                    <select className="custom-input" style={{ backgroundColor: editEstadosDefecto ? '#ffffff' : '#f3f4f6' }} value={defectoSancionPagado} disabled={!editEstadosDefecto} onChange={e => handleSaveDefecto('defecto_sancion_Pagado', e.target.value, setDefectoSancionPagado)}>
                      {estadosSancion.filter(s => s.action === 'Pagado' && !s.hidden).map((s, i) => <option key={i} value={s.label}>{s.label}</option>)}
                    </select>
                  </div>

                  {/* 3. RAÍZ CONDONADO */}
                  <div>
                    <label style={{ display: 'block', fontSize: '12.5px', fontWeight: '600', color: '#4b5563', marginBottom: '4px' }}>Acción Base: "Condonado"</label>
                    <select className="custom-input" style={{ backgroundColor: editEstadosDefecto ? '#ffffff' : '#f3f4f6' }} value={defectoSancionCondonado} disabled={!editEstadosDefecto} onChange={e => handleSaveDefecto('defecto_sancion_Condonado', e.target.value, setDefectoSancionCondonado)}>
                      {estadosSancion.filter(s => s.action === 'Condonado' && !s.hidden).map((s, i) => <option key={i} value={s.label}>{s.label}</option>)}
                    </select>
                  </div>
                </div>
              </div>

            </div>

            {/* INYECTAMOS EL MODAL DE CONFIRMACIÓN ALERTA DEL SISTEMA EN LA PARTE SUPERIOR DEL AJUSTE */}
            {confirmDialog.show && (
                <div className="pdf-modal-overlay">
                    <div className="pdf-modal-content" style={{maxWidth: '400px'}}>
                        {/* 🎨 El título ahora toma el color dinámico según la operación */}
                        <h3 style={{color: confirmDialog.confirmColor || '#ef4444', marginBottom: '10px'}}>{confirmDialog.title}</h3>
                        <p style={{color: '#4b5563', fontSize: '14px', lineHeight: '1.5', marginBottom: '25px'}}>{confirmDialog.message}</p>
                        <div style={{display: 'flex', gap: '10px', justifyContent: 'center'}}>
                            <button className="btn-pdf-text" onClick={() => setConfirmDialog({show: false, title: '', message: '', confirmText: 'Confirmar', confirmColor: '#ef4444', onConfirm: () => {}})}>Cancelar</button>
                            {/* 🎨 Texto y fondo dinámicos para evitar confusiones de borrado */}
                            <button className="btn-pdf-img" style={{backgroundColor: confirmDialog.confirmColor || '#ef4444'}} onClick={confirmDialog.onConfirm}>
                              {confirmDialog.confirmText || 'Sí, eliminar'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* SECCIÓN DE CAMPOS ELÁSTICOS INTERACTIVOS */}
            <div className="config-card-header" style={{ marginTop: '35px', borderTop: '1px solid #e5e7eb', paddingTop: '25px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <IonIcon icon={settingsOutline} className="section-icon" />
                  <h3>Configuraciones Elásticas de Menús</h3>
                </div>
            </div>

            {(() => {
              const renderItemControl = (
                title: string, 
                items: ConfigItem[], 
                setItems: React.Dispatch<React.SetStateAction<ConfigItem[]>>, 
                setDirty: React.Dispatch<React.SetStateAction<boolean>>, 
                configKey: string,
                isModuleEditable: boolean,
                setIsModuleEditable: React.Dispatch<React.SetStateAction<boolean>>,
                nativeActions?: string[]
              ) => {
                return (
                  <div style={{ marginTop: '20px', padding: '20px', background: '#f9fafb', borderRadius: '12px', border: '1px solid #e5e7eb', width: '100%', boxSizing: 'border-box' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '14px' }}>
                      <h4 style={{ margin: 0, color: '#582c83', fontSize: '14px', fontWeight: 'bold' }}>{title}</h4>
                      <IonButton fill="clear" className="btn-edit-toggle-small" style={{ margin: 0, padding: 0 }} onClick={() => setIsModuleEditable(!isModuleEditable)} title={isModuleEditable ? "Cancelar edición de sección" : "Editar sección"}>
                        <IonIcon icon={isModuleEditable ? closeOutline : createOutline} style={{ color: isModuleEditable ? '#ef4444' : '#7c3aed', fontSize: '20px' }} />
                      </IonButton>
                    </div>

                    <div style={{ display: 'flex', flexDirection: 'column', gap: '12px', width: '100%' }}>
                      {items.map((item, idx) => (
                        <div key={idx} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '30px', background: 'white', padding: '12px', borderRadius: '8px', border: '1px solid #e5e7eb', flexWrap: 'nowrap', width: '100%', boxSizing: 'border-box' }}>
                          
                          <input 
                            className="custom-input" 
                            style={{ margin: 0, flex: 1, padding: '10px 14px', color: '#111827', backgroundColor: (item.isNative || !isModuleEditable) ? '#f3f4f6' : '#ffffff', fontSize: '14px', border: '1px solid #d1d5db', borderRadius: '8px', cursor: (item.isNative || !isModuleEditable) ? 'not-allowed' : 'text', opacity: item.hidden ? 0.4 : 1 }} 
                            value={item.label} 
                            disabled={item.isNative || !isModuleEditable}
                            onChange={e => { const updated = [...items]; updated[idx].label = e.target.value; setItems(updated); setDirty(true); }} 
                          />

                          {nativeActions && (item.action || item.label) && (
                            <div style={{ width: '130px', fontSize: '13px', color: '#582c83', fontWeight: '600', padding: '8px 12px', background: '#f3e8ff', borderRadius: '6px', textAlign: 'center', boxSizing: 'border-box', flexShrink: 0, opacity: item.hidden ? 0.4 : 1 }}>
                              {item.action || item.label}
                            </div>
                          )}

                          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', flexShrink: 0, width: '80px', justifyContent: 'flex-end' }}>
                            <IonButton fill="clear" disabled={!isModuleEditable} style={{ margin: 0, padding: 0 }} onClick={() => { const updated = [...items]; updated[idx].hidden = !updated[idx].hidden; setItems(updated); setDirty(true); }}>
                              <IonIcon icon={item.hidden ? closeCircleOutline : checkmarkCircleOutline} style={{ fontSize: '22px', color: !isModuleEditable ? '#e5e7eb' : (item.hidden ? '#9ca3af' : '#22c55e') }} />
                            </IonButton>

                            {!item.isNative && isModuleEditable && (
                              <IonButton fill="clear" color="danger" style={{ margin: 0, padding: 0 }} onClick={() => {
                                setConfirmDialog({
                                  show: true,
                                  title: 'Eliminar Estado Personalizado',
                                  message: `¿Estás seguro de que deseas eliminar permanentemente "${item.label}"?`,
                                  onConfirm: () => {
                                    setItems(items.filter((_, i) => i !== idx));
                                    setDirty(true);
                                    setConfirmDialog({ show: false, title: '', message: '', onConfirm: () => {} });
                                  }
                                });
                              }}><IonIcon icon={trashOutline} style={{ fontSize: '20px' }} /></IonButton>
                            )}
                          </div>
                        </div>
                      ))}

                      {isModuleEditable && (
                        <>
                          <div style={{ display: 'flex', gap: '10px', marginTop: '6px', flexWrap: 'wrap', width: '100%' }}>
                            <input className="custom-input" style={{ margin: 0, flex: '1 1 250px', color: '#111827', backgroundColor: '#ffffff', padding: '10px 14px' }} placeholder="Nuevo estado..." value={nuevoTexto[configKey] || ''} onChange={e => setNuevoTexto({ ...nuevoTexto, [configKey]: e.target.value })} />
                            {nativeActions && (
                              <select className="custom-input" style={{ margin: 0, width: '180px', color: '#111827', backgroundColor: '#ffffff', padding: '0 10px' }} value={nuevaAccion[configKey] || nativeActions[0]} onChange={e => setNuevaAccion({ ...nuevaAccion, [configKey]: e.target.value })}>
                                {nativeActions.map(act => <option key={act} value={act}>{act}</option>)}
                              </select>
                            )}
                            <button className="btn-guardar-inline" style={{ margin: 0, padding: '0 20px', height: '44px' }} onClick={() => { const lbl = nuevoTexto[configKey]?.trim(); if (!lbl) return; const act = nativeActions ? (nuevaAccion[configKey] || nativeActions[0]) : undefined; setItems([...items, { label: lbl, action: act, isNative: false, hidden: false }]); setDirty(true); setNuevoTexto({ ...nuevoTexto, [configKey]: '' }); }}>+ Añadir</button>
                          </div>

                          <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '15px', borderTop: '1px solid #e5e7eb', paddingTop: '15px' }}>
                            <button className="btn-guardar-inline" style={{ height: '40px', padding: '0 20px', background: '#582c83' }} onClick={saveConfig} disabled={isProcessing}>
                              <IonIcon icon={saveOutline} style={{ marginRight: '6px', verticalAlign: 'middle' }} />
                              Guardar Sección
                            </button>
                          </div>
                        </>
                      )}
                    </div>
                  </div>
                );
              };

              return (
                <>
                  <div style={{ marginTop: '30px', marginBottom: '5px', color: '#582c83', fontWeight: 'bold', fontSize: '12px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Módulo: Autores</div>
                  {renderItemControl("Clasificaciones de Autor", tiposAutor, setTiposAutor, setIsAutoresDirty, "tipos_autor", editTiposAutor, setEditTiposAutor)}
                  
                  <div style={{ marginTop: '30px', marginBottom: '5px', color: '#582c83', fontWeight: 'bold', fontSize: '12px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Módulo: Inventario</div>
                  {renderItemControl("Estados Físicos de Unidades", estadosFisicos, setEstadosFisicos, setIsInventarioDirty, "estados_fisicos", editEstadosFisicos, setEditEstadosFisicos)}
                  {renderItemControl("Estados de Disponibilidad", disponibilidades, setDisponibilidades, setIsInventarioDirty, "disponibilidades", editDisponibilidades, setEditDisponibilidades, ["Disponible", "Prestado", "Extraviado", "Baja"])}
                  
                  <div style={{ marginTop: '30px', marginBottom: '5px', color: '#582c83', fontWeight: 'bold', fontSize: '12px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Módulo: Préstamos</div>
                  {renderItemControl("Estados del Trámite", estadosPrestamo, setEstadosPrestamo, setIsPrestamosDirty, "estados_prestamo", editEstadosPrestamo, setEditEstadosPrestamo, ["Activo", "Devuelto", "Atrasado", "Finalizado (Sanción)"])}
                  
                  <div style={{ marginTop: '30px', marginBottom: '5px', color: '#582c83', fontWeight: 'bold', fontSize: '12px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Módulo: Sanciones</div>
                  {renderItemControl("Condiciones de Cobro", estadosSancion, setEstadosSancion, setIsSancionesDirty, "estados_sancion", editEstadosSancion, setEditEstadosSancion, ["Pendiente", "Pagado", "Condonado"])}
                  {renderItemControl("Naturaleza de Infracción", tiposSancion, setTiposSancion, setIsSancionesDirty, "tipos_sancion", editTiposSancion, setEditTiposSancion)}
                </>
              );
            })()}

            {(isAutoresDirty || isInventarioDirty || isPrestamosDirty || isSancionesDirty) && (
              <div className="form-row" style={{ marginTop: '25px', justifyContent: 'flex-end' }}>
                <button className="btn-guardar-inline" onClick={saveConfig} disabled={isProcessing || isInitialLoading}>
                  <IonIcon icon={saveOutline} style={{ marginRight: '8px', fontSize: '18px', verticalAlign: 'middle' }}/> 
                  GUARDAR TODO
                </button>
              </div>
            )}

          </div>

        </div>
      </IonContent>
    </IonPage>
  );
};

export default Configuracion;