<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecursoCatalogoController extends Controller
{
    // ====================================================================
    // 1. OBTENER EL CATÁLOGO (Lee de la tabla maestra y hace JOIN con hijas)
    // ====================================================================
    public function index(Request $request)
    {
        try {
            $modulo = $request->input('modulo');
            $search = $request->input('search');

            // 🏛️ CORRECCIÓN: Conectamos recursos_catalogo con temas_catalogo relacionalmente
            $query = DB::table('recursos_catalogo')
                ->leftJoin('autores', 'recursos_catalogo.Autor_ID', '=', 'autores.Autor_ID')
                ->leftJoin('editoriales', 'recursos_catalogo.Editorial_ID', '=', 'editoriales.Editorial_ID')
                ->leftJoin('temas_catalogo', 'recursos_catalogo.Tema_ID', '=', 'temas_catalogo.Tema_ID');

            // 🏛️ CORRECCIÓN: Inyectamos el alias virtual TemaRecurso directo desde la tabla de temas
            $selectFields = [
                'recursos_catalogo.*',
                'temas_catalogo.NombreTema as TemaRecurso',
                DB::raw("TRIM(CONCAT(IFNULL(autores.NombreAutor,''), ' ', IFNULL(autores.ApellidosAutor,''))) as Autor"),
                'editoriales.NombreEditorial as Editorial'
            ];

            // Condicionamos los Joins y campos estrictamente según el módulo solicitado
            if ($modulo === 'Libro') {
                $query->join('libros', 'recursos_catalogo.Recurso_ID', '=', 'libros.Recurso_ID');
                $selectFields[] = 'libros.EdicionVolumen';
                $selectFields[] = 'libros.ClasificacionISBN';
            } 
            elseif ($modulo === 'Revista / Artículo Científico') {
                $query->join('revistas', 'recursos_catalogo.Recurso_ID', '=', 'revistas.Recurso_ID');
                $selectFields[] = 'revistas.EdicionVolumen';
                $selectFields[] = 'revistas.ClasificacionISSN';
            } 
            elseif ($modulo === 'Tesis') {
                $query->join('tesis', 'recursos_catalogo.Recurso_ID', '=', 'tesis.Recurso_ID');
                $selectFields[] = 'tesis.Asesor';
                $selectFields[] = 'tesis.GradoCarrera';
                $selectFields[] = 'tesis.AutorTexto';
            } 
            elseif ($modulo === 'Enciclopedia / Diccionario') {
                $query->join('enciclopedias', 'recursos_catalogo.Recurso_ID', '=', 'enciclopedias.Recurso_ID');
                $selectFields[] = 'enciclopedias.EdicionVolumen';
                $selectFields[] = 'enciclopedias.ClasificacionISBN';
            } 
            elseif ($modulo === 'Equipo Audiovisual') {
                $query->join('audiovisuales', 'recursos_catalogo.Recurso_ID', '=', 'audiovisuales.Recurso_ID');
                $selectFields[] = 'audiovisuales.Marca';
                $selectFields[] = 'audiovisuales.NumSerie';
            } 
            elseif ($modulo === 'Mobiliario Didáctico') {
                $query->join('mobiliario_didactico', 'recursos_catalogo.Recurso_ID', '=', 'mobiliario_didactico.Recurso_ID');
                $selectFields[] = 'mobiliario_didactico.Marca';
                $selectFields[] = 'mobiliario_didactico.Material';
            } 
            elseif ($modulo === 'Dispositivo de Conectividad') {
                $query->join('dispositivos_conectividad', 'recursos_catalogo.Recurso_ID', '=', 'dispositivos_conectividad.Recurso_ID');
                $selectFields[] = 'dispositivos_conectividad.Marca';
                $selectFields[] = 'dispositivos_conectividad.NumSerie';
            } 
            else {
                // Respaldo genérico por si no viene el parámetro del módulo
                $query->leftJoin('libros', 'recursos_catalogo.Recurso_ID', '=', 'libros.Recurso_ID')
                      ->leftJoin('tesis', 'recursos_catalogo.Recurso_ID', '=', 'tesis.Recurso_ID')
                      ->leftJoin('revistas', 'recursos_catalogo.Recurso_ID', '=', 'revistas.Recurso_ID');
            }

            // Aplicamos la selección final de las columnas limpias
            $query->select($selectFields);

            // Filtro por Módulo
            if ($modulo) {
                $query->where('recursos_catalogo.TipoRecurso', $modulo);
            }

            // Búsqueda en tiempo real condicionada al módulo activo para evitar colisiones
            if ($search) {
                $query->where(function($q) use ($search, $modulo) {
                    // 1. Búsqueda en campos globales (siempre existen)
                    $q->where('recursos_catalogo.Titulo', 'LIKE', "%{$search}%")
                      ->orWhere('temas_catalogo.NombreTema', 'LIKE', "%{$search}%") // 🏛️ CORREGIDO
                      ->orWhere('recursos_catalogo.AnioPublicacion', 'LIKE', "%{$search}%")
                      ->orWhereRaw("TRIM(CONCAT(IFNULL(autores.NombreAutor,''), ' ', IFNULL(autores.ApellidosAutor,''))) LIKE ?", ["%{$search}%"])
                      ->orWhere('editoriales.NombreEditorial', 'LIKE', "%{$search}%");

                    // 2. Búsquedas específicas según el módulo real conectado
                    if ($modulo === 'Libro') {
                        $q->orWhere('libros.ClasificacionISBN', 'LIKE', "%{$search}%")
                          ->orWhere('libros.EdicionVolumen', 'LIKE', "%{$search}%");
                    }
                    elseif ($modulo === 'Revista / Artículo Científico') {
                        $q->orWhere('revistas.ClasificacionISSN', 'LIKE', "%{$search}%")
                          ->orWhere('revistas.EdicionVolumen', 'LIKE', "%{$search}%");
                    }
                    elseif ($modulo === 'Tesis') {
                        $q->orWhere('tesis.Asesor', 'LIKE', "%{$search}%")
                          ->orWhere('tesis.AutorTexto', 'LIKE', "%{$search}%")
                          ->orWhere('tesis.GradoCarrera', 'LIKE', "%{$search}%");
                    }
                    elseif ($modulo === 'Enciclopedia / Diccionario') {
                        $q->orWhere('enciclopedias.ClasificacionISBN', 'LIKE', "%{$search}%")
                          ->orWhere('enciclopedias.EdicionVolumen', 'LIKE', "%{$search}%");
                    }
                    elseif ($modulo === 'Equipo Audiovisual') {
                        $q->orWhere('audiovisuales.Marca', 'LIKE', "%{$search}%")
                          ->orWhere('audiovisuales.NumSerie', 'LIKE', "%{$search}%");
                    }
                    elseif ($modulo === 'Mobiliario Didáctico') {
                        $q->orWhere('mobiliario_didactico.Marca', 'LIKE', "%{$search}%")
                          ->orWhere('mobiliario_didactico.Material', 'LIKE', "%{$search}%");
                    }
                    elseif ($modulo === 'Dispositivo de Conectividad') {
                        $q->orWhere('dispositivos_conectividad.Marca', 'LIKE', "%{$search}%")
                          ->orWhere('dispositivos_conectividad.NumSerie', 'LIKE', "%{$search}%");
                    }
                });
            }

            // 🏛️ SOLUCIÓN MAESTRA: Captura el parámetro enviado por React, si no existe deja 6 por defecto (Panel Admin)
            $perPage = $request->input('per_page', 6);

            // Ejecutamos la consulta usando la paginación dinámica
            $datos = $request->has('all') ? $query->get() : $query->paginate($perPage);
            $items = $request->has('all') ? $datos : $datos->getCollection();

            // CORRECCIÓN 2: MAGIA DE IMÁGENES (Convertimos textos en URLs y Base64 para el PDF)
            foreach ($items as $item) {
                
                // 1. Crear URL pública para que la tabla en React pueda pintar la foto
                if (!empty($item->Imagen_path)) {
                    if (str_starts_with($item->Imagen_path, 'http')) {
                        // 🏛️ NUEVO PARCHE HD: Escalamos la calidad de la portada también en el listado/explorador general
                        $imgHD = str_replace('zoom=1', 'zoom=2', $item->Imagen_path);
                        $item->Imagen_url = $imgHD; 
                    } else {
                        $item->Imagen_url = url('storage/' . $item->Imagen_path); // Es local
                    }
                } else {
                    $item->Imagen_url = null;
                }

                // NUEVO: Crear URL pública para el PDF (si existe)
                if (!empty($item->Archivo_PDF)) {
                    $item->Pdf_url = url('storage/' . $item->Archivo_PDF);
                } else {
                    $item->Pdf_url = null;
                }
                
                // 2. Crear código Base64 SÓLO si React pide generar el PDF con imágenes
                if ($request->input('with_images') === 'true' && !empty($item->Imagen_path)) {
                    try {
                        if (str_starts_with($item->Imagen_path, 'http')) {
                            // Descargar imagen de internet
                            $imgData = file_get_contents($item->Imagen_path);
                            if ($imgData) {
                                $item->Imagen_base64 = 'data:image/jpeg;base64,' . base64_encode($imgData);
                            }
                        } else {
                            // Leer imagen local del servidor
                            $fullPath = storage_path('app/public/' . $item->Imagen_path);
                            if (file_exists($fullPath)) {
                                $mime = mime_content_type($fullPath);
                                $item->Imagen_base64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                            }
                        }
                    } catch (\Exception $e) {
                        // Si algo falla, lo dejamos null para que no rompa el PDF
                        $item->Imagen_base64 = null; 
                    }
                }
            }

            // Re-inyectamos los items procesados a la paginación
            if (!$request->has('all')) {
                $datos->setCollection($items);
            }

            return response()->json(['success' => true, 'data' => $datos]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ====================================================================
    // 2. ELIMINAR RECURSO (Borrado explícito sin dejar registros huérfanos)
    // ====================================================================
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $recurso = DB::table('recursos_catalogo')->where('Recurso_ID', $id)->first();
            
            if (!$recurso) {
                return response()->json(['success' => false, 'message' => 'El recurso no existe o ya fue eliminado.'], 404);
            }

            // 1. Si tiene imagen local, la borramos del disco duro
            if (!empty($recurso->Imagen_path) && !str_starts_with($recurso->Imagen_path, 'http')) {
                Storage::disk('public')->delete($recurso->Imagen_path);
            }

            // NUEVO: Si tiene PDF de Tesis, lo borramos del disco duro
            if (!empty($recurso->Archivo_PDF)) {
                Storage::disk('public')->delete($recurso->Archivo_PDF);
            }

            // 2. Borramos primero al hijo específico para evitar registros huérfanos
            switch ($recurso->TipoRecurso) {
                case 'Libro':
                    DB::table('libros')->where('Recurso_ID', $id)->delete();
                    break;
                case 'Revista / Artículo Científico':
                    DB::table('revistas')->where('Recurso_ID', $id)->delete();
                    break;
                case 'Tesis':
                    DB::table('tesis')->where('Recurso_ID', $id)->delete();
                    break;
                case 'Enciclopedia / Diccionario':
                    DB::table('enciclopedias')->where('Recurso_ID', $id)->delete();
                    break;
                case 'Equipo Audiovisual':
                    DB::table('audiovisuales')->where('Recurso_ID', $id)->delete();
                    break;
                case 'Mobiliario Didáctico':
                    DB::table('mobiliario_didactico')->where('Recurso_ID', $id)->delete();
                    break;
                case 'Dispositivo de Conectividad':
                    DB::table('dispositivos_conectividad')->where('Recurso_ID', $id)->delete();
                    break;
            }

            // 3. Borramos el inventario (copias físicas) asociadas
            DB::table('inventario_unidades')->where('Recurso_ID', $id)->delete();
            
            // 4. Finalmente borramos el registro maestro
            DB::table('recursos_catalogo')->where('Recurso_ID', $id)->delete();
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Recurso eliminado completamente.'], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ====================================================================
    // 3. OBTENER DETALLE ESPECÍFICO OPTIMIZADO (CON COBERTURA DE INVENTARIO REAL)
    // ====================================================================
    public function getRecursoDetalle(Request $request, $id)
    {
        try {
            $recurso = DB::table('recursos_catalogo')->where('Recurso_ID', $id)->first();
            
            if (!$recurso) {
                return response()->json(['success' => false, 'message' => 'El recurso solicitado no existe en el catálogo.'], 404);
            }

            // Usamos leftJoin global para agilizar la respuesta y evitar retrasos en cascada
            $query = DB::table('recursos_catalogo')
                ->leftJoin('autores', 'recursos_catalogo.Autor_ID', '=', 'autores.Autor_ID')
                ->leftJoin('editoriales', 'recursos_catalogo.Editorial_ID', '=', 'editoriales.Editorial_ID')
                ->leftJoin('temas_catalogo', 'recursos_catalogo.Tema_ID', '=', 'temas_catalogo.Tema_ID') // 🏛️ CORREGIDO
                ->where('recursos_catalogo.Recurso_ID', $id);

            $selectFields = [
                'recursos_catalogo.*',
                'temas_catalogo.NombreTema as TemaRecurso', // 🏛️ CORREGIDO
                DB::raw("TRIM(CONCAT(IFNULL(autores.NombreAutor,''), ' ', IFNULL(autores.ApellidosAutor,''))) as Autor"),
                'editoriales.NombreEditorial as Editorial'
            ];

            // Vinculación por herencia con leftJoin para evitar bloqueos si faltan datos relacionales
            if ($recurso->TipoRecurso === 'Libro') {
                $query->leftJoin('libros', 'recursos_catalogo.Recurso_ID', '=', 'libros.Recurso_ID');
                $selectFields[] = 'libros.EdicionVolumen';
                $selectFields[] = 'libros.ClasificacionISBN';
            } 
            elseif ($recurso->TipoRecurso === 'Revista / Artículo Científico') {
                $query->leftJoin('revistas', 'recursos_catalogo.Recurso_ID', '=', 'revistas.Recurso_ID');
                $selectFields[] = 'revistas.EdicionVolumen';
                $selectFields[] = 'revistas.ClasificacionISSN';
            } 
            elseif ($recurso->TipoRecurso === 'Tesis') {
                // 🏛️ BLINDAJE EXTRA: Cambiado a leftJoin para homogeneizar con el resto de módulos y mitigar caídas
                $query->leftJoin('tesis', 'recursos_catalogo.Recurso_ID', '=', 'tesis.Recurso_ID');
                $selectFields[] = 'tesis.Asesor';
                $selectFields[] = 'tesis.GradoCarrera';
                $selectFields[] = 'tesis.AutorTexto';
            } 
            elseif ($recurso->TipoRecurso === 'Enciclopedia / Diccionario') {
                $query->leftJoin('enciclopedias', 'recursos_catalogo.Recurso_ID', '=', 'enciclopedias.Recurso_ID');
                $selectFields[] = 'enciclopedias.EdicionVolumen';
                $selectFields[] = 'enciclopedias.ClasificacionISBN';
            }
            elseif ($recurso->TipoRecurso === 'Equipo Audiovisual') {
                $query->leftJoin('audiovisuales', 'recursos_catalogo.Recurso_ID', '=', 'audiovisuales.Recurso_ID');
                $selectFields[] = 'audiovisuales.Marca';
                $selectFields[] = 'audiovisuales.NumSerie';
            } 
            elseif ($recurso->TipoRecurso === 'Mobiliario Didáctico') {
                $query->leftJoin('mobiliario_didactico', 'recursos_catalogo.Recurso_ID', '=', 'mobiliario_didactico.Recurso_ID');
                $selectFields[] = 'mobiliario_didactico.Marca';
                $selectFields[] = 'mobiliario_didactico.Material';
            } 
            elseif ($recurso->TipoRecurso === 'Dispositivo de Conectividad') {
                $query->leftJoin('dispositivos_conectividad', 'recursos_catalogo.Recurso_ID', '=', 'dispositivos_conectividad.Recurso_ID');
                $selectFields[] = 'dispositivos_conectividad.Marca';
                $selectFields[] = 'dispositivos_conectividad.NumSerie';
            }

            $item = $query->select($selectFields)->first();

            if (!$item) {
                return response()->json(['success' => false, 'message' => 'No se pudo estructurar la ficha técnica.'], 404);
            }

            // 📊 CONTEO SEGURO DE INVENTARIO: Guiado por la columna de herencia lógica unificada
            $unidadesDisponibles = 0;
            try {
                $unidadesDisponibles = DB::table('inventario_unidades')
                    ->where('Recurso_ID', $id)
                    ->where('EstadoDisponibilidad_Logico', 'Disponible')
                    ->count();
            } catch (\Throwable $thInv) {
                // Fallback de contingencia por si la tabla está vacía o no tiene registros aún
                $unidadesDisponibles = 0;
            }

            // Inyectamos la variable limpia al objeto de respuesta
            $item->unidades_disponibles = $unidadesDisponibles;

            // Escalado HD de portadas de Google Books
            if (!empty($item->Imagen_path)) {
                $imgHD = str_replace('zoom=1', 'zoom=2', $item->Imagen_path);
                $item->Imagen_url = str_starts_with($item->Imagen_path, 'http') ? $imgHD : url('storage/' . $item->Imagen_path);
            } else {
                $item->Imagen_url = null;
            }

            if (!empty($item->Archivo_PDF)) {
                $item->Pdf_url = url('storage/' . $item->Archivo_PDF);
            } else {
                $item->Pdf_url = null;
            }

            // 🏛️ NUEVO FRAGMENTO AÑADIDO AQUÍ:
            $usuarioId = $request->user()->Usuario_ID;
            $item->is_favorito = DB::table('favoritos')
                ->where('Usuario_ID', $usuarioId)
                ->where('Recurso_ID', $id)
                ->exists();

            // Tu línea original queda exactamente debajo:
            return response()->json(['success' => true, 'data' => $item], 200);

        } catch (\Throwable $e) {
            // Usamos Throwable para atrapar errores de cualquier tipo y mandarlos limpios a la consola
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ====================================================================
    // 4. OBTENER ESTANTE DE FAVORITOS PERSONAL DEL ALUMNO (MI BIBLIOTECA)
    // ====================================================================
    public function getMisFavoritos(Request $request)
    {
        try {
            $usuarioId = $request->user()->Usuario_ID;

            // Consultamos los recursos que están en la tabla favoritos vinculados a este alumno
            $favoritos = DB::table('favoritos')
                ->join('recursos_catalogo', 'favoritos.Recurso_ID', '=', 'recursos_catalogo.Recurso_ID')
                ->leftJoin('autores', 'recursos_catalogo.Autor_ID', '=', 'autores.Autor_ID')
                ->leftJoin('temas_catalogo', 'recursos_catalogo.Tema_ID', '=', 'temas_catalogo.Tema_ID')
                ->select(
                    'recursos_catalogo.Recurso_ID as id',
                    'recursos_catalogo.Titulo',
                    'recursos_catalogo.TipoRecurso',
                    'recursos_catalogo.Imagen_path',
                    'temas_catalogo.NombreTema as TemaRecurso',
                    DB::raw("TRIM(CONCAT(IFNULL(autores.NombreAutor,''), ' ', IFNULL(autores.ApellidosAutor,''))) as Autor")
                )
                ->where('favoritos.Usuario_ID', $usuarioId)
                ->orderBy('favoritos.created_at', 'DESC') // Los más recientes primero
                ->get();

            // Escalado HD de portadas para mantener coherencia estética
            foreach ($favoritos as $item) {
                if (!empty($item->Imagen_path)) {
                    $imgHD = str_replace('zoom=1', 'zoom=2', $item->Imagen_path);
                    $item->Imagen_url = str_starts_with($item->Imagen_path, 'http') ? $imgHD : url('storage/' . $item->Imagen_path);
                } else {
                    $item->Imagen_url = null;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $favoritos
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}