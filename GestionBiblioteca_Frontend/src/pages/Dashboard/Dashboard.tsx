import React, { useState, useRef } from 'react';
import { IonContent, IonPage, IonIcon,IonButton, useIonViewWillEnter } from '@ionic/react';
import { 
  libraryOutline, bookOutline, documentTextOutline, videocamOutline, newspaperOutline, 
  swapHorizontalOutline, cashOutline, trendingUpOutline, peopleOutline, shieldCheckmarkOutline, 
  personOutline, bookmarksOutline, searchOutline, bulbOutline, calendarOutline, textOutline, closeCircleOutline, downloadOutline
} from 'ionicons/icons';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { 
  LineChart, Line, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, Legend, ResponsiveContainer,
  PieChart, Pie, Cell 
} from 'recharts';
// @ts-ignore
import api from '../../services/api'; 
import './Dashboard.css';

const Dashboard: React.FC = () => {
  const [isLoading, setIsLoading] = useState(true);
  
  // REFERENCIA PARA CONTROLAR EL SCROLL
  const contentRef = useRef<HTMLIonContentElement | null>(null);

  // ESTADOS DE FECHAS Y PERIODOS RÁPIDOS
  const [periodoPrestamos, setPeriodoPrestamos] = useState('siempre');
  const [fechaInicioPrestamos, setFechaInicioPrestamos] = useState('');
  const [fechaFinPrestamos, setFechaFinPrestamos] = useState('');
  
  const [periodoSanciones, setPeriodoSanciones] = useState('siempre');
  const [fechaInicioSanciones, setFechaInicioSanciones] = useState('');
  const [fechaFinSanciones, setFechaFinSanciones] = useState('');
  const [showHelp, setShowHelp] = useState(false);

  // ➕ NUEVOS ESTADOS INDEPENDIENTES PARA RANGO DE FECHAS EN ESTATUS DE PRÉSTAMOS
  const [periodoEstatus, setPeriodoEstatus] = useState('siempre');
  const [fechaInicioEstatus, setFechaInicioEstatus] = useState('');
  const [fechaFinEstatus, setFechaFinEstatus] = useState('');
  
  const abortControllerRef = useRef<AbortController | null>(null);

  // 🏛️ REFERENCIAS DIGITALES PARA CAPTURAR LOS NODOS VECTORIALES DE LAS GRÁFICAS
  const chartPrestamosRef = useRef<HTMLDivElement>(null);
  const chartSancionesRef = useRef<HTMLDivElement>(null);
  const chartEstatusRef = useRef<HTMLDivElement>(null);
  const chartCatalogoRef = useRef<HTMLDivElement>(null);

  const [stats, setStats] = useState({
    usuarios: 0, personal: 0, autores: 0, editoriales: 0,
    total_catalogo: 0, libros: 0, tesis: 0, audiovisual: 0, revistas: 0, 
    prestamos_periodo: 0, multas_recaudadas: 0
  });
  
  const [charts, setCharts] = useState({
    recursosPorTipo: [], tendenciaPrestamos: [], prestamosPorEstado: [], tendenciaSanciones: []
  });

  const pieColors = ['#582c83', '#fe5000', '#0057b7', '#ffd100', '#d0df00', '#666666'];
  
  const coloresEstados: Record<string, string> = { 
    'Activo': '#3b82f6', 
    'Devuelto': '#10b981',
    'Atrasado': '#ef4444'
  };

  const fetchDashboardData = async (fInicioP = fechaInicioPrestamos, fFinP = fechaFinPrestamos, fInicioS = fechaInicioSanciones, fFinS = fechaFinSanciones) => {
    setIsLoading(true);
    if (abortControllerRef.current) abortControllerRef.current.abort();
    
    const currentAbortController = new AbortController();
    abortControllerRef.current = currentAbortController;

    try {
      const response = await api.get(`/dashboard-stats?prestamos_inicio=${fInicioP}&prestamos_fin=${fFinP}&sanciones_inicio=${fInicioS}&sanciones_fin=${fFinS}`, {
          signal: currentAbortController.signal
      });
      if (response.data.success) {
        setStats(response.data.data.stats);
        setCharts(response.data.data.charts);
      }
    } catch (error: any) {
      if (error.name !== 'CanceledError' && error.message !== 'canceled') {
          console.error("Error al cargar el dashboard:", error);
      }
    } finally {
      if (abortControllerRef.current === currentAbortController) {
        setIsLoading(false);
      }
    }
  };

  // SE REINICIA TODO Y SE FUERZA EL SCROLL ARRIBA AL ENTRAR AL MÓDULO
  useIonViewWillEnter(() => {
    if (contentRef.current) {
        contentRef.current.scrollToTop(0); 
    }
    setPeriodoPrestamos('siempre');
    setFechaInicioPrestamos('');
    setFechaFinPrestamos('');
    setPeriodoSanciones('siempre');
    setFechaInicioSanciones('');
    setFechaFinSanciones('');
    // ➕ Limpieza de estados del gráfico de estatus
    setPeriodoEstatus('siempre');
    setFechaInicioEstatus('');
    setFechaFinEstatus('');
    setShowHelp(false); 
    fetchDashboardData('', '', '', '');
  });

  // HANDLERS PARA PRÉSTAMOS
  const handlePeriodoPrestamos = (e: any) => {
    const val = e.target.value;
    setPeriodoPrestamos(val);
    if (val !== 'personalizado') {
        setFechaInicioPrestamos('');
        setFechaFinPrestamos('');
        // Para el backend, si no es personalizado, enviamos la clave rápida en el campo de "inicio"
        fetchDashboardData(val, '', fechaInicioSanciones || periodoSanciones, fechaFinSanciones);
    }
  };

  const ejecutarBusquedaPrestamos = () => {
    setPeriodoPrestamos('personalizado');
    fetchDashboardData(fechaInicioPrestamos, fechaFinPrestamos, fechaInicioSanciones || periodoSanciones, fechaFinSanciones);
  };

  // HANDLERS PARA SANCIONES
  const handlePeriodoSanciones = (e: any) => {
    const val = e.target.value;
    setPeriodoSanciones(val);
    if (val !== 'personalizado') {
        setFechaInicioSanciones('');
        setFechaFinSanciones('');
        // Enviamos la clave rápida en el campo de "inicio"
        fetchDashboardData(fechaInicioPrestamos || periodoPrestamos, fechaFinPrestamos, val, '');
    }
  };

  const ejecutarBusquedaSanciones = () => {
    setPeriodoSanciones('personalizado');
    fetchDashboardData(fechaInicioPrestamos || periodoPrestamos, fechaFinPrestamos, fechaInicioSanciones, fechaFinSanciones);
  };

  // ➕ MANEJADORES REACTIVOS PARA LA GRÁFICA DE ESTATUS DE PRÉSTAMOS
  const handlePeriodoEstatus = (e: any) => {
    const val = e.target.value;
    setPeriodoEstatus(val);
    if (val !== 'personalizado') {
        setFechaInicioEstatus('');
        setFechaFinEstatus('');
        fetchDashboardData(val, '', fechaInicioSanciones || periodoSanciones, fechaFinSanciones);
    }
  };

  const ejecutarBusquedaEstatus = () => {
    setPeriodoEstatus('personalizado');
    fetchDashboardData(fechaInicioEstatus, fechaFinEstatus, fechaInicioSanciones || periodoSanciones, fechaFinSanciones);
  };

  const ordenDeseado: Record<string, number> = { 'Activo': 1, 'Devuelto': 2, 'Atrasado': 3 };
  
  // 🏛️ ACUMULADOR INTELIGENTE: Clasifica e ignora cambios de nombres agrupando todo en los 3 estados base
  const prestamosOrdenados = (() => {
    const resumen: Record<string, number> = { 'Activo': 0, 'Devuelto': 0, 'Atrasado': 0 };
    
    charts.prestamosPorEstado.forEach((item: any) => {
      const nombreLower = (item.name || '').toLowerCase();
      let claveBase = 'Activo';
      
      // Mapeo por coincidencia de palabras clave comunes
      if (nombreLower.includes('activ') || nombreLower.includes('vigent') || nombreLower.includes('present')) {
        claveBase = 'Activo';
      } else if (nombreLower.includes('devuel') || nombreLower.includes('entreg') || nombreLower.includes('retorn')) {
        claveBase = 'Devuelto';
      } else if (nombreLower.includes('atrasa') || nombreLower.includes('vencid')) {
        claveBase = 'Atrasado';
      } else {
        claveBase = 'Activo'; 
      }
      
      resumen[claveBase] += (item.value || 0);
    });
    
    // Retorna el array unificado mapeado y ordenado para Recharts
    return Object.keys(resumen).map(key => ({
      name: key,
      value: resumen[key]
    })).sort((a, b) => (ordenDeseado[a.name] || 99) - (ordenDeseado[b.name] || 99));
  })();

  const exportarDatosGrafica = (data: any[], tituloReporte: string, columnaClave: string) => {
    if (!data || data.length === 0) {
      alert("No hay registros analíticos disponibles en el periodo seleccionado.");
      return;
    }

    // Mapeo adaptativo para transformar los nodos de Recharts a filas estructuradas de Excel
    const filasExcel = data.map(item => ({
      [columnaClave]: item.name || item.fecha || 'Sin clasificar',
      'Cantidad / Total': item.value || item.cantidad || 0
    }));

    const libroTrabajo = XLSX.utils.book_new();
    const hojaDatos = XLSX.utils.json_to_sheet(filasExcel);
    XLSX.utils.book_append_sheet(libroTrabajo, hojaDatos, "Métricas Analíticas");
    
    const timestamp = new Date().toISOString().split('T')[0];
    XLSX.writeFile(libroTrabajo, `Reporte_${tituloReporte.replace(/\s+/g, '_')}_${timestamp}.xlsx`);
  };

  // 🏛️ RASTERIZADOR ASÍNCRONO Y GENERADOR DE REPORTE CON LIMPIEZA VECTORIAL DE MÁSCARAS
  const exportarPDFGrafica = (
    contenedorRef: React.RefObject<HTMLDivElement | null>, 
    tituloReporte: string,
    data: any[] = [],
    columnaClave: string = "Fecha / Periodo"
  ) => {
    const contenedor = contenedorRef.current;
    if (!contenedor) return;

    const svgOriginal = contenedor.querySelector('svg');
    if (!svgOriginal) {
      alert("No se pudo inicializar la conversión visual de la gráfica seleccionada.");
      return;
    }

    // 1. MEDIDAS REALES EN PANTALLA
    const rect = svgOriginal.getBoundingClientRect();
    const anchoReal = rect.width || 500;
    const altoReal = rect.height || 300;

    // 2. CLONACIÓN PROFUNDA DEL NODO SVG
    const svgClon = svgOriginal.cloneNode(true) as SVGElement;

    // 3. 🛠️ SOLUCIÓN AL CÍRCULO MORADO: ELIMINAMOS TODOS LOS CLIP-PATHS Y MÁSCARAS DE ANIMACIÓN DE RECHARTS
    svgClon.querySelectorAll('*').forEach((el) => {
      el.removeAttribute('clip-path');
      el.removeAttribute('mask');
    });
    svgClon.querySelectorAll('clipPath, mask').forEach((el) => el.remove());

    // 4. INLINEAMOS LOS COLORES Y ESTILOS REALES EN CADA ELEMENTO DEL SVG
    const inlinearEstilos = (src: Element, dest: Element) => {
      const computed = window.getComputedStyle(src);
      const fill = computed.getPropertyValue('fill');
      const stroke = computed.getPropertyValue('stroke');
      const strokeWidth = computed.getPropertyValue('stroke-width');
      const fontSize = computed.getPropertyValue('font-size');
      const fontFamily = computed.getPropertyValue('font-family');
      const fontWeight = computed.getPropertyValue('font-weight');
      const opacity = computed.getPropertyValue('opacity');

      let styleAcc = '';
      if (fill && fill !== 'none') styleAcc += `fill: ${fill};`;
      if (stroke && stroke !== 'none') styleAcc += `stroke: ${stroke};`;
      if (strokeWidth) styleAcc += `stroke-width: ${strokeWidth};`;
      if (fontSize) styleAcc += `font-size: ${fontSize};`;
      if (fontFamily) styleAcc += `font-family: ${fontFamily};`;
      if (fontWeight) styleAcc += `font-weight: ${fontWeight};`;
      if (opacity) styleAcc += `opacity: ${opacity};`;

      if (styleAcc) {
        dest.setAttribute('style', (dest.getAttribute('style') || '') + ';' + styleAcc);
      }

      for (let i = 0; i < src.children.length; i++) {
        if (dest.children[i]) {
          inlinearEstilos(src.children[i], dest.children[i]);
        }
      }
    };
    inlinearEstilos(svgOriginal, svgClon);

    // 5. DIMENSIONAMIENTO FIJO Y VIEWBOX
    svgClon.setAttribute('width', `${anchoReal}`);
    svgClon.setAttribute('height', `${altoReal}`);
    svgClon.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    if (!svgClon.getAttribute('viewBox')) {
      svgClon.setAttribute('viewBox', `0 0 ${anchoReal} ${altoReal}`);
    }

    // 6. CONVERSIÓN A CANVAS E IMAGEN DE ALTA RESOLUCIÓN
    const svgString = new XMLSerializer().serializeToString(svgClon);
    const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
    const blobUrl = URL.createObjectURL(svgBlob);

    const img = new Image();
    img.onload = () => {
      const canvas = document.createElement('canvas');
      const escala = 2; // Alta definición (Retina)
      canvas.width = anchoReal * escala;
      canvas.height = altoReal * escala;

      const ctx = canvas.getContext('2d');
      if (ctx) {
        ctx.scale(escala, escala);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, anchoReal, altoReal);
        ctx.drawImage(img, 0, 0, anchoReal, altoReal);

        const pngBase64 = canvas.toDataURL('image/png');
        const pdf = new jsPDF('l', 'mm', 'a4'); // Hoja A4 Horizontal (297 mm)

        // BANNER INSTITUCIONAL SUPERIOR (Morado UPVE)
        pdf.setFillColor(88, 44, 131); 
        pdf.rect(0, 0, 297, 22, 'F');

        pdf.setTextColor(255, 255, 255);
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(13);
        pdf.text('UNIVERSIDAD POLITÉCNICA DEL VALLE DEL ÉVORA', 14, 14);

        // METADATOS DEL REPORTE
        pdf.setTextColor(17, 24, 39);
        pdf.setFontSize(14);
        pdf.text(`Reporte Estadístico: ${tituloReporte}`, 14, 32);

        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(9);
        pdf.setTextColor(107, 114, 128);
        pdf.text(`Generado el: ${new Date().toLocaleDateString()} a las ${new Date().toLocaleTimeString()}`, 14, 38);

        // 🎯 CÁLCULO DE PROPORCIONES PROPORCIONALES (SIN DEFORMACIÓN)
        const esEstatus = tituloReporte.toLowerCase().includes('estatus');
        const esCatalogo = tituloReporte.toLowerCase().includes('catalogo') || tituloReporte.toLowerCase().includes('catálogo');

        let altoGrafica = 75;
        if (esEstatus) altoGrafica = 68;
        if (esCatalogo) altoGrafica = 72;

        const aspectRatio = anchoReal / altoReal;
        let anchoGrafica = altoGrafica * aspectRatio;

        if (anchoGrafica > 269) {
          anchoGrafica = 269;
          altoGrafica = anchoGrafica / aspectRatio;
        }

        const xPosicion = (esEstatus || esCatalogo) ? (297 - anchoGrafica) / 2 : 14;

        pdf.addImage(pngBase64, 'PNG', xPosicion, 42, anchoGrafica, altoGrafica);

        // 📊 TABLA ANALÍTICA DINÁMICA
        if (data && data.length > 0) {
          const totalAcumulado = data.reduce((acc, curr) => acc + Number(curr.value || curr.cantidad || 0), 0);

          const tableHeaders = [[columnaClave, 'Cantidad / Total']];
          const tableBody: any[] = data.map(item => [
            item.name || item.fecha || 'Sin clasificar',
            item.value || item.cantidad || 0
          ]);

          tableBody.push(['TOTAL ACUMULADO', totalAcumulado]);

          autoTable(pdf, {
            startY: 42 + altoGrafica + 8,
            head: tableHeaders,
            body: tableBody,
            theme: 'grid',
            headStyles: { fillColor: [88, 44, 131], halign: 'center', fontSize: 9, fontStyle: 'bold' },
            bodyStyles: { halign: 'center', fontSize: 8.5 },
            columnStyles: {
              0: { cellWidth: 135 },
              1: { cellWidth: 134, fontStyle: 'bold' }
            },
            didParseCell: function(dataCell) {
              if (dataCell.row.index === tableBody.length - 1) {
                dataCell.cell.styles.fillColor = [243, 232, 255];
                dataCell.cell.styles.textColor = [88, 44, 131];
                dataCell.cell.styles.fontStyle = 'bold';
              }
            }
          });
        }

        // PIE DE PÁGINA
        const totalPaginas = (pdf as any).internal.getNumberOfPages();
        for (let i = 1; i <= totalPaginas; i++) {
          pdf.setPage(i);
          pdf.setFontSize(8);
          pdf.setTextColor(156, 163, 175);
          pdf.text('Ecosistema Digital UPVE - Módulo Administrativo de Control Bibliotecario', 14, 202);
          pdf.text(`Página ${i} de ${totalPaginas}`, 280, 202, { align: 'right' });
        }

        pdf.save(`Reporte_Visual_${tituloReporte.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`);
      }
      URL.revokeObjectURL(blobUrl);
    };
    img.src = blobUrl;
  };

  return (
    <IonPage>
      {/* VINCULAMOS LA REFERENCIA AL CONTENEDOR PRINCIPAL */}
      <IonContent ref={contentRef} className="dashboard-bg" style={{ position: 'relative' }}>
        
        {isLoading && (
            <div className="main-loader-overlay">
                <div className="main-loader-spinner"></div>
                <p>Calculando métricas...</p>
            </div>
        )}

        {/* --- TOOLTIP MODAL INFORMATIVO (FOQUITO RESTAURADO CON EJEMPLOS) --- */}
        {showHelp && (
          <div className="help-tooltip-overlay" onClick={() => setShowHelp(false)}>
            <div className="help-tooltip-content" onClick={e => e.stopPropagation()} style={{ maxWidth: '700px' }}>
              <div className="help-tooltip-header">
                <h3><IonIcon icon={bulbOutline} /> Guía de Filtros y Rangos de Fecha</h3>
                <IonIcon icon={closeCircleOutline} className="close-help-icon" onClick={() => setShowHelp(false)} />
              </div>
              <p>Aprende a utilizar las herramientas de filtrado para analizar las métricas de las gráficas de manera precisa:</p>
              
              <table className="help-tooltip-table">
                <thead>
                  <tr>
                    <th style={{ width: '25%' }}>Herramienta</th>
                    <th style={{ width: '50%' }}>Instrucciones de uso</th>
                    <th style={{ width: '25%' }}>Ejemplo</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><strong>Rango de Fechas <br/>(Desde / Hasta)</strong></td>
                    <td>Haz clic en el primer calendario para elegir la <strong>fecha de inicio</strong> y en el segundo para la <strong>fecha límite</strong>. Una vez seleccionadas, presiona el botón de la lupa morada para graficar los resultados de ese lapso.</td>
                    <td><code className="code-badge">01/06/2026</code> al <br/><code className="code-badge">15/06/2026</code></td>
                  </tr>
                  <tr>
                    <td><strong>Filtro de Tiempo <br/>Rápido</strong></td>
                    <td>Haz clic en el menú desplegable de la derecha para elegir un lapso predeterminado. La gráfica cargará la información automáticamente al seleccionarlo, sin necesidad de usar la lupa.</td>
                    <td><code className="code-badge">Últimos 7 días</code><br/>o bien<br/><code className="code-badge">Hoy</code></td>
                  </tr>
                </tbody>
              </table>
              
              <p style={{ fontSize: '12px', color: '#666', marginTop: '15px', fontStyle: 'italic' }}>
                💡 Tip: Los controles de Préstamos y Sanciones funcionan de forma independiente. Si utilizas los calendarios, el menú rápido cambiará automáticamente a "Personalizado".
              </p>
            </div>
          </div>
        )}

        <div className="dashboard-layout">
          
          <div className="main-top-header">
            <div>
              <h1><IonIcon icon={trendingUpOutline} className="header-icon" /> Dashboard Overview</h1>
              <p>Métricas, usuarios y analíticas de la biblioteca en tiempo real.</p>
            </div>
          </div>

          <h2 className="section-subtitle">Comunidad y Red</h2>
          <div className="kpi-grid">
            <div className="kpi-card"><div className="kpi-icon-wrapper users-icon"><IonIcon icon={peopleOutline} /></div><div className="kpi-info"><h3>{stats.usuarios}</h3><p>Usuarios</p></div></div>
            <div className="kpi-card"><div className="kpi-icon-wrapper staff-icon"><IonIcon icon={shieldCheckmarkOutline} /></div><div className="kpi-info"><h3>{stats.personal}</h3><p>Personal</p></div></div>
            <div className="kpi-card"><div className="kpi-icon-wrapper authors-icon"><IonIcon icon={personOutline} /></div><div className="kpi-info"><h3>{stats.autores}</h3><p>Autores</p></div></div>
            <div className="kpi-card"><div className="kpi-icon-wrapper publishers-icon"><IonIcon icon={bookmarksOutline} /></div><div className="kpi-info"><h3>{stats.editoriales}</h3><p>Editoriales</p></div></div>
          </div>

          <h2 className="section-subtitle">Recursos y Movimientos</h2>
          <div className="kpi-grid">
            <div className="kpi-card highlight-card"><div className="kpi-icon-wrapper total-icon"><IonIcon icon={libraryOutline} /></div><div className="kpi-info"><h3>{stats.total_catalogo}</h3><p>Total en Catálogo</p></div></div>
            <div className="kpi-card"><div className="kpi-icon-wrapper books-icon"><IonIcon icon={bookOutline} /></div><div className="kpi-info"><h3>{stats.libros}</h3><p>Libros</p></div></div>
            <div className="kpi-card"><div className="kpi-icon-wrapper thesis-icon"><IonIcon icon={documentTextOutline} /></div><div className="kpi-info"><h3>{stats.tesis}</h3><p>Tesis</p></div></div>
            <div className="kpi-card"><div className="kpi-icon-wrapper audiovisual-icon"><IonIcon icon={videocamOutline} /></div><div className="kpi-info"><h3>{stats.audiovisual}</h3><p>Equipo Audio/Visual</p></div></div>
            <div className="kpi-card"><div className="kpi-icon-wrapper magazine-icon"><IonIcon icon={newspaperOutline} /></div><div className="kpi-info"><h3>{stats.revistas}</h3><p>Revistas / Artículos</p></div></div>
            
            <div className="kpi-card highlight-card-blue"><div className="kpi-icon-wrapper loans-icon"><IonIcon icon={swapHorizontalOutline} /></div><div className="kpi-info"><h3>{stats.prestamos_periodo}</h3><p>Préstamos (Total)</p></div></div>
            <div className="kpi-card highlight-card-green"><div className="kpi-icon-wrapper cash-icon"><IonIcon icon={cashOutline} /></div><div className="kpi-info"><h3>${stats.multas_recaudadas}</h3><p>Cobradas (Total)</p></div></div>
          </div>

          <div className="charts-grid">
            
            {/* 1. TENDENCIA DE PRÉSTAMOS */}
            <div className="chart-card chart-span-full">
              <div className="analytics-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '10px' }}>
                <div>
                  <h3 className="chart-title chart-title-trend">Tendencia de Préstamos</h3>
                  <p className="chart-subtitle-trend">Actividad según fecha de salida</p>
                </div>
                
                <div style={{ display: 'flex', gap: '8px', alignItems: 'center', flexWrap: 'wrap' }}>
                  {/* 1. CÁPSULA UNIFICADA DE FECHAS (AHORA A LA IZQUIERDA) */}
                  <div style={{ display: 'flex', alignItems: 'center', background: 'white', border: '1px solid #d1d5db', borderRadius: '8px', padding: '0 5px', height: '40px', width: 'max-content' }}>
                    <span style={{ paddingLeft: '5px', color: '#6b7280', fontSize: '12px', fontWeight: 600 }}>Desde:</span>
                    <input 
                      type="date" 
                      style={{ border: 'none', outline: 'none', background: 'transparent', height: '100%', padding: '0 5px', color: '#374151', fontSize: '12px', width: '105px' }} 
                      value={fechaInicioPrestamos}
                      onChange={e => setFechaInicioPrestamos(e.target.value)}
                    />
                    <div style={{ height: '20px', width: '1px', backgroundColor: '#e5e7eb', margin: '0 2px' }}></div>
                    <span style={{ paddingLeft: '5px', color: '#6b7280', fontSize: '12px', fontWeight: 600 }}>Hasta:</span>
                    <input 
                      type="date" 
                      style={{ border: 'none', outline: 'none', background: 'transparent', height: '100%', padding: '0 5px', color: '#374151', fontSize: '12px', width: '105px' }} 
                      value={fechaFinPrestamos}
                      onChange={e => setFechaFinPrestamos(e.target.value)}
                    />
                  </div>

                  {/* 2. LUPA DE BÚSQUEDA */}
                  <button onClick={ejecutarBusquedaPrestamos} style={{ background: '#582c83', color: 'white', border: 'none', borderRadius: '8px', height: '40px', width: '40px', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}>
                    <IonIcon icon={searchOutline} style={{ fontSize: '18px' }} />
                  </button>

                  {/* 3. SELECTOR RÁPIDO MOVIDO A LA DERECHA Y CON COLOR CORREGIDO */}
                  <select 
                    style={{ height: '40px', width: '160px', padding: '0 10px', borderRadius: '8px', border: '1px solid #d1d5db', fontSize: '13px', color: '#374151', outline: 'none', cursor: 'pointer', background: 'white' }}
                    value={periodoPrestamos} 
                    onChange={handlePeriodoPrestamos}
                  >
                    <option value="siempre">Todo el historial</option>
                    <option value="hoy">Hoy</option>
                    <option value="7">Últimos 7 días</option>
                    <option value="30">Últimos 30 días</option>
                    <option value="personalizado" disabled>Rango personalizado</option>
                  </select>

                  {/* 4. FOQUITO AL FINAL */}
                  <button className="btn-bulb-help-outside" onClick={() => setShowHelp(true)} title="Ver guía de formatos">
                    <IonIcon icon={bulbOutline} />
                  </button>

                  {/* 5. BOTÓN EXPORTAR EXCEL */}
                  <IonButton size="small" fill="outline" color="success" style={{ height: '40px', '--border-radius': '8px', fontWeight: '600', margin: 0 }} onClick={() => exportarDatosGrafica(charts.tendenciaPrestamos || [], "Tendencia de Prestamos", "Periodo / Fecha")}>
                    <IonIcon slot="start" icon={downloadOutline} />
                    Excel
                  </IonButton>

                  {/* 6. BOTÓN EXPORTAR PDF (CON IMAGEN) */}
                  <IonButton size="small" fill="outline" color="danger" style={{ height: '40px', '--border-radius': '8px', fontWeight: '600', margin: 0 }} onClick={() => exportarPDFGrafica(chartPrestamosRef, "Tendencia de Prestamos Analiticos", charts.tendenciaPrestamos || [], "Fecha / Periodo")}>
                  <IonIcon slot="start" icon={documentTextOutline} />
                  PDF
                </IonButton>
                </div>
              </div>

              {/* VINCULAMOS LA REFERENCIA AQUÍ 👇 */}
              <div ref={chartPrestamosRef} className="chart-wrapper-large">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={charts.tendenciaPrestamos} margin={{ top: 30, right: 30, left: -20, bottom: 0 }}>
                  <CartesianGrid vertical={false} />
                  <XAxis dataKey="fecha" axisLine={false} tickLine={false} dy={10} interval="preserveStartEnd" minTickGap={15} />
                  <YAxis axisLine={false} tickLine={false} allowDecimals={false} domain={[0, 'dataMax + 2']} />
                    <RechartsTooltip />
                    <Line type="monotone" dataKey="cantidad" stroke="#582c83" strokeWidth={3} dot={{ r: 4, strokeWidth: 2, fill: 'white' }} activeDot={{ r: 6 }} name="Préstamos" label={{ position: 'top', fill: '#582c83', fontSize: 11, fontWeight: 'bold' }} />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </div>

            {/* 2. TENDENCIA DE SANCIONES */}
            <div className="chart-card chart-span-full">
              <div className="analytics-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '10px' }}>
                <div>
                  <h3 className="chart-title chart-title-trend">Tendencia de Sanciones</h3>
                  <p className="chart-subtitle-trend">Sanciones generadas en el periodo seleccionado</p>
                </div>
                
                <div style={{ display: 'flex', gap: '8px', alignItems: 'center', flexWrap: 'wrap' }}>
                  {/* 1. CÁPSULA UNIFICADA DE FECHAS (AHORA A LA IZQUIERDA) */}
                  <div style={{ display: 'flex', alignItems: 'center', background: 'white', border: '1px solid #d1d5db', borderRadius: '8px', padding: '0 5px', height: '40px', width: 'max-content' }}>
                    <span style={{ paddingLeft: '5px', color: '#6b7280', fontSize: '12px', fontWeight: 600 }}>Desde:</span>
                    <input 
                      type="date" 
                      style={{ border: 'none', outline: 'none', background: 'transparent', height: '100%', padding: '0 5px', color: '#374151', fontSize: '12px', width: '105px' }} 
                      value={fechaInicioSanciones}
                      onChange={e => setFechaInicioSanciones(e.target.value)}
                    />
                    <div style={{ height: '20px', width: '1px', backgroundColor: '#e5e7eb', margin: '0 2px' }}></div>
                    <span style={{ paddingLeft: '5px', color: '#6b7280', fontSize: '12px', fontWeight: 600 }}>Hasta:</span>
                    <input 
                      type="date" 
                      style={{ border: 'none', outline: 'none', background: 'transparent', height: '100%', padding: '0 5px', color: '#374151', fontSize: '12px', width: '105px' }} 
                      value={fechaFinSanciones}
                      onChange={e => setFechaFinSanciones(e.target.value)}
                    />
                  </div>

                  {/* 2. LUPA DE BÚSQUEDA */}
                  <button onClick={ejecutarBusquedaSanciones} style={{ background: '#582c83', color: 'white', border: 'none', borderRadius: '8px', height: '40px', width: '40px', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}>
                    <IonIcon icon={searchOutline} style={{ fontSize: '18px' }} />
                  </button>

                  {/* 3. SELECTOR RÁPIDO MOVIDO A LA DERECHA Y CON COLOR CORREGIDO */}
                  <select 
                    style={{ height: '40px', width: '160px', padding: '0 10px', borderRadius: '8px', border: '1px solid #d1d5db', fontSize: '13px', color: '#374151', outline: 'none', cursor: 'pointer', background: 'white' }}
                    value={periodoSanciones} 
                    onChange={handlePeriodoSanciones}
                  >
                    <option value="siempre">Todo el historial</option>
                    <option value="hoy">Hoy</option>
                    <option value="7">Últimos 7 días</option>
                    <option value="30">Últimos 30 días</option>
                    <option value="personalizado" disabled>Rango personalizado</option>
                  </select>

                  {/* 4. FOQUITO AL FINAL */}
                  <button className="btn-bulb-help-outside" onClick={() => setShowHelp(true)} title="Ver guía de formatos">
                    <IonIcon icon={bulbOutline} />
                  </button>

                  {/* 5. BOTÓN EXPORTAR EXCEL */}
                  <IonButton size="small" fill="outline" color="success" style={{ height: '40px', '--border-radius': '8px', fontWeight: '600', margin: 0 }} onClick={() => exportarDatosGrafica(charts.tendenciaSanciones || [], "Tendencia de Sanciones", "Periodo / Fecha")}>
                    <IonIcon slot="start" icon={downloadOutline} />
                    Excel
                  </IonButton>

                  {/* 6. BOTÓN EXPORTAR PDF (CON IMAGEN) */}
                  <IonButton size="small" fill="outline" color="danger" style={{ height: '40px', '--border-radius': '8px', fontWeight: '600', margin: 0 }} onClick={() => exportarPDFGrafica(chartSancionesRef, "Tendencia Mensual de Sanciones", charts.tendenciaSanciones || [], "Fecha / Periodo")}>
                  <IonIcon slot="start" icon={documentTextOutline} />
                  PDF
                </IonButton>
                </div>
              </div>

              {/* VINCULAMOS LA REFERENCIA AQUÍ 👇 */}
              <div ref={chartSancionesRef} className="chart-wrapper-large">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={charts.tendenciaSanciones} margin={{ top: 30, right: 30, left: -20, bottom: 0 }}>
                  <CartesianGrid vertical={false} />
                  <XAxis dataKey="fecha" axisLine={false} tickLine={false} dy={10} interval="preserveStartEnd" minTickGap={15} />
                  <YAxis axisLine={false} tickLine={false} allowDecimals={false} domain={[0, 'dataMax + 2']} />
                    <RechartsTooltip />
                    <Line type="monotone" dataKey="cantidad" stroke="#ef4444" strokeWidth={3} dot={{ r: 4, strokeWidth: 2, fill: 'white' }} activeDot={{ r: 6 }} name="Sanciones" label={{ position: 'top', fill: '#ef4444', fontSize: 11, fontWeight: 'bold' }} />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </div>

            {/* 3. ESTATUS DE PRÉSTAMOS */}
            <div className="chart-card">
              
              {/* 🔝 FILA SUPERIOR: TÍTULO A LA IZQUIERDA Y BOTONES A LA DERECHA ALINEADOS EN UNA SOLA LÍNEA */}
              <div className="analytics-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%', marginBottom: '10px' }}>
                <div>
                  <h3 className="chart-title" style={{ margin: 0 }}>Estatus de los Préstamos</h3>
                  <p className="chart-subtitle-trend" style={{ margin: '2px 0 0 0', fontSize: '12px', color: '#666' }}>Distribución operativa de solicitudes</p>
                </div>
                
                <div style={{ display: 'flex', gap: '6px', alignItems: 'center' }}>
                  {/* 5. BOTÓN EXPORTAR EXCEL COMPACTO */}
                  <IonButton size="small" fill="outline" color="success" style={{ height: '36px', '--border-radius': '8px', fontWeight: '600', fontSize: '12px', margin: 0 }} onClick={() => exportarDatosGrafica(prestamosOrdenados || [], "Estatus de Prestamos", "Estatus Asignado")}>
                    <IonIcon slot="start" icon={downloadOutline} />
                    Excel
                  </IonButton>
                  
                  {/* 6. BOTÓN EXPORTAR PDF COMPACTO */}
                  <IonButton size="small" fill="outline" color="danger" style={{ height: '36px', '--border-radius': '8px', fontWeight: '600', fontSize: '12px', margin: 0 }} onClick={() => exportarPDFGrafica(chartEstatusRef, "Distribucion de Estatus de Prestamos", prestamosOrdenados || [], "Estatus")}>
                  <IonIcon slot="start" icon={documentTextOutline} />
                  PDF
                </IonButton>
                </div>
              </div>

              {/* 🏽 FILA INFERIOR: FILTROS UNIFICADOS JUSTIFICADOS ABAJO A LA DERECHA */}
              <div style={{ display: 'flex', gap: '6px', alignItems: 'center', flexWrap: 'wrap', justifyContent: 'flex-end', marginBottom: '15px', width: '100%' }}>
                
                {/* 1. CALENDARIOS REDUCIDOS CON SINTAXIS CORREGIDA */}
                <div style={{ display: 'flex', alignItems: 'center', background: 'white', border: '1px solid #d1d5db', borderRadius: '8px', padding: '0 4px', height: '36px', width: 'max-content' }}>
                  <span style={{ paddingLeft: '3px', color: '#6b7280', fontSize: '11px', fontWeight: '600' }}>Desde:</span>
                  <input 
                    type="date" 
                    style={{ border: 'none', outline: 'none', background: 'transparent', height: '100%', padding: '0 2px', color: '#374151', fontSize: '11px', width: '92px' }} 
                    value={fechaInicioEstatus}
                    onChange={e => setFechaInicioEstatus(e.target.value)}
                  />
                  <div style={{ height: '16px', width: '1px', backgroundColor: '#e5e7eb', margin: '0 2px' }}></div>
                  <span style={{ paddingLeft: '3px', color: '#6b7280', fontSize: '11px', fontWeight: '600' }}>Hasta:</span>
                  <input 
                    type="date" 
                    style={{ border: 'none', outline: 'none', background: 'transparent', height: '100%', padding: '0 2px', color: '#374151', fontSize: '11px', width: '92px' }} 
                    value={fechaFinEstatus}
                    onChange={e => setFechaFinEstatus(e.target.value)}
                  />
                </div>

                {/* 2. LUPA COMPACTA */}
                <button onClick={ejecutarBusquedaEstatus} style={{ background: '#582c83', color: 'white', border: 'none', borderRadius: '8px', height: '36px', width: '36px', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', margin: 0 }}>
                  <IonIcon icon={searchOutline} style={{ fontSize: '16px' }} />
                </button>

                {/* 3. SELECTOR RÁPIDO COMPACTO */}
                <select 
                  style={{ height: '36px', width: '135px', padding: '0 6px', borderRadius: '8px', border: '1px solid #d1d5db', fontSize: '12px', color: '#374151', outline: 'none', cursor: 'pointer', background: 'white' }}
                  value={periodoEstatus} 
                  onChange={handlePeriodoEstatus}
                >
                  <option value="siempre">Historial</option>
                  <option value="hoy">Hoy</option>
                  <option value="7">7 días</option>
                  <option value="30">30 días</option>
                  <option value="personalizado" disabled>Personalizado</option>
                </select>

                {/* 4. FOQUITO COMPACTO */}
                <button className="btn-bulb-help-outside" style={{ height: '36px', width: '30px', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: 0 }} onClick={() => setShowHelp(true)} title="Ver guía de formatos">
                  <IonIcon icon={bulbOutline} style={{ fontSize: '22px' }} />
                </button>
              </div>
              
              {/* VINCULAMOS LA REFERENCIA AQUÍ 👇 */}
              <div ref={chartEstatusRef} className="chart-wrapper-standard">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={prestamosOrdenados} margin={{ top: 20, right: 30, left: -20, bottom: 5 }}>
                    <CartesianGrid vertical={false} />
                    <XAxis dataKey="name" axisLine={false} tickLine={false} />
                    <YAxis axisLine={false} tickLine={false} allowDecimals={false} />
                    <RechartsTooltip />
                    <Bar dataKey="value" radius={[4, 4, 0, 0]} barSize={50} name="Cantidad">
                      {prestamosOrdenados.map((entry: any, index: number) => (
                        <Cell key={`cell-${index}`} fill={coloresEstados[entry.name] || '#3b82f6'} />
                      ))}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>

            {/* 4. DISTRIBUCIÓN DEL CATÁLOGO */}
            <div className="chart-card">
              
              {/* CABECERA CON TÍTULO, SUBTÍTULO Y BOTONES DE EXPORTACIÓN */}
              <div className="analytics-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%', marginBottom: '15px' }}>
                <div>
                  <h3 className="chart-title" style={{ margin: 0 }}>Distribución del Catálogo</h3>
                  <p className="chart-subtitle-trend" style={{ margin: '2px 0 0 0', fontSize: '12px', color: '#666' }}>Recursos registrados por categoría</p>
                </div>
                
                <div style={{ display: 'flex', gap: '6px', alignItems: 'center' }}>
                  {/* BOTÓN EXPORTAR EXCEL */}
                  <IonButton size="small" fill="outline" color="success" style={{ height: '36px', '--border-radius': '8px', fontWeight: '600', fontSize: '12px', margin: 0 }} onClick={() => exportarDatosGrafica(charts.recursosPorTipo || [], "Distribucion del Catalogo", "Tipo de Recurso")}>
                    <IonIcon slot="start" icon={downloadOutline} />
                    Excel
                  </IonButton>

                </div>
              </div>

              {/* CONTENEDOR CON LA REFERENCIA VINCULADA */}
              <div ref={chartCatalogoRef} className="chart-wrapper-standard">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie 
                    data={charts.recursosPorTipo} 
                    cx="50%" 
                    cy="50%" 
                    innerRadius={70} 
                    outerRadius={100} 
                    paddingAngle={5} 
                    dataKey="value"
                    isAnimationActive={false}
                  >
                    {charts.recursosPorTipo.map((entry: any, index: number) => (
                      <Cell key={`cell-${index}`} fill={pieColors[index % pieColors.length]} />
                    ))}
                  </Pie>
                    <RechartsTooltip />
                    <Legend verticalAlign="bottom" height={36} iconType="circle" />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </div>

          </div>

        </div>
      </IonContent>
    </IonPage>
  );
};

export default Dashboard;