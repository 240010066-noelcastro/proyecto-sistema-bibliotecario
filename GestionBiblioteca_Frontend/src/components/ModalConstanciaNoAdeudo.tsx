import React, { useState, useEffect } from 'react';
// Importamos tu servicio API configurado con Token Sanctum
// @ts-ignore
import api from '../services/api';

interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  usuario: {
    Usuario_ID: number;
    NombreUsuario: string;
    ApellidoPaterno: string;
    ApellidoMaterno?: string;
    Matricula: string;
  } | null;
  personalList: Array<{ Personal_ID?: number; Usuario_ID?: number; NombrePersonal?: string; NombreUsuario?: string; ApellidoPaterno: string }>;
}

export const ModalConstanciaNoAdeudo: React.FC<ModalProps> = ({ isOpen, onClose, usuario, personalList }) => {
  const [isVerificando, setIsVerificando] = useState(false);
  const [isGenerando, setIsGenerando] = useState(false);
  const [limpio, setLimpio] = useState<boolean | null>(null);
  const [detalles, setDetalles] = useState({ prestamos: 0, sanciones: 0 });
  const [personalId, setPersonalId] = useState<number | string>('');
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  useEffect(() => {
    if (isOpen && usuario) {
      verificarEstadoUsuario();
      setPersonalId('');
      setErrorMsg(null);
    }
  }, [isOpen, usuario]);

  const verificarEstadoUsuario = async () => {
    if (!usuario) return;
    setIsVerificando(true);
    setErrorMsg(null);
    try {
      // Petición autenticada mediante tu api.ts
      const res = await api.get(`/constancias/verificar/${usuario.Usuario_ID}`);
      setLimpio(res.data.limpio);
      setDetalles({
        prestamos: res.data.prestamos_activos,
        sanciones: res.data.sanciones_pendientes,
      });
    } catch (err: any) {
      console.error('Error al verificar adeudos:', err);
      setErrorMsg(err.response?.data?.message || 'Error al conectar con el servidor.');
    } finally {
      setIsVerificando(false);
    }
  };

  const handleGenerarPdf = async () => {
    if (!usuario || !personalId) return;

    setIsGenerando(true);
    try {
      // 1. Petición POST enviando el Token Sanctum en los Headers
      const res = await api.post(
        '/constancias/generar',
        {
          Usuario_ID: usuario.Usuario_ID,
          Personal_ID: personalId,
        },
        { responseType: 'blob' } // Pide la respuesta como archivo binario (PDF)
      );

      // 2. Transforma los datos binarios en una URL de PDF temporal del navegador
      const file = new Blob([res.data], { type: 'application/pdf' });
      const fileURL = URL.createObjectURL(file);

      // 3. Abre el archivo generado en una nueva pestaña
      window.open(fileURL, '_blank');
      onClose();
    } catch (err: any) {
      console.error('Error al generar PDF:', err);
      alert('Ocurrió un error al generar el PDF.');
    } finally {
      setIsGenerando(false);
    }
  };

  if (!isOpen || !usuario) return null;

  return (
    <div style={{ position: 'fixed', inset: 0, backgroundColor: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, backdropFilter: 'blur(3px)' }}>
      <div style={{ background: '#fff', padding: '24px', borderRadius: '12px', width: '450px', maxWidth: '90%', boxShadow: '0 20px 25px -5px rgba(0,0,0,0.1)' }}>
        <h3 style={{ margin: '0 0 10px 0', color: '#582c83', fontSize: '18px', fontWeight: 'bold' }}>📜 Constancia de No Adeudo</h3>
        
        <p style={{ fontSize: '14px', color: '#374151', margin: '8px 0 15px 0' }}>
          Alumno: <strong>{usuario.NombreUsuario} {usuario.ApellidoPaterno} {usuario.ApellidoMaterno || ''}</strong> ({usuario.Matricula})
        </p>

        {isVerificando ? (
          <p style={{ textAlign: 'center', margin: '20px 0', color: '#6b7280', fontSize: '13px' }}>🔍 Consultando historial en la base de datos...</p>
        ) : (
          <>
            {errorMsg && (
              <div style={{ backgroundColor: '#fef2f2', border: '1px solid #fca5a5', color: '#991b1b', padding: '10px 12px', borderRadius: '8px', marginBottom: '15px', fontSize: '13px' }}>
                ⚠️ {errorMsg}
              </div>
            )}

            {limpio === false && (
              <div style={{ backgroundColor: '#fef2f2', border: '1px solid #fca5a5', color: '#991b1b', padding: '12px', borderRadius: '8px', margin: '15px 0', fontSize: '13px' }}>
                <strong>❌ No se puede emitir la constancia.</strong>
                <ul style={{ margin: '5px 0 0 18px', padding: 0 }}>
                  {detalles.prestamos > 0 && <li>Préstamos activos/atrasados: {detalles.prestamos}</li>}
                  {detalles.sanciones > 0 && <li>Sanciones/multas pendientes: {detalles.sanciones}</li>}
                </ul>
              </div>
            )}

            {limpio === true && (
              <div style={{ backgroundColor: '#f0fdf4', border: '1px solid #86efac', color: '#166534', padding: '12px', borderRadius: '8px', margin: '15px 0', fontSize: '13px' }}>
                ✅ <strong>Usuario al corriente:</strong> El alumno no cuenta con préstamos ni sanciones pendientes.
              </div>
            )}

            {limpio === true && (
              <div style={{ marginTop: '15px' }}>
                <label style={{ display: 'block', fontSize: '11px', fontWeight: 'bold', marginBottom: '6px', color: '#582c83' }}>
                  SELECCIONA QUIÉN FIRMARÁ LA CONSTANCIA *
                </label>
                <select 
                  style={{ width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid #d1d5db', fontSize: '14px', backgroundColor: '#fff', color: '#111827', cursor: 'pointer' }}
                  value={personalId}
                  onChange={(e) => setPersonalId(e.target.value)}
                >
                  <option value="">-- Seleccionar Encargado --</option>
                  {personalList.map((p) => {
                    const id = p.Personal_ID || p.Usuario_ID;
                    const nombre = p.NombrePersonal || p.NombreUsuario;
                    return (
                      <option key={id} value={id}>
                        {nombre} {p.ApellidoPaterno}
                      </option>
                    );
                  })}
                </select>
              </div>
            )}
          </>
        )}

        {/* Botones de acción con estilos contrastados */}
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px', marginTop: '20px' }}>
          <button 
            onClick={onClose} 
            style={{ 
              padding: '9px 18px', 
              borderRadius: '8px', 
              border: '1px solid #d1d5db', 
              background: '#f3f4f6', 
              color: '#374151', 
              fontWeight: '600', 
              fontSize: '13px',
              cursor: 'pointer' 
            }}
          >
            Cancelar
          </button>

          {limpio === true && (
            <button 
              onClick={handleGenerarPdf}
              disabled={!personalId || isGenerando}
              style={{ 
                padding: '9px 18px', 
                borderRadius: '8px', 
                border: 'none', 
                background: (personalId && !isGenerando) ? '#582c83' : '#9ca3af', 
                color: '#fff', 
                fontWeight: 'bold', 
                fontSize: '13px',
                cursor: (personalId && !isGenerando) ? 'pointer' : 'not-allowed' 
              }}
            >
              {isGenerando ? '📄 Generando...' : '📄 Generar PDF'}
            </button>
          )}
        </div>
      </div>
    </div>
  );
};