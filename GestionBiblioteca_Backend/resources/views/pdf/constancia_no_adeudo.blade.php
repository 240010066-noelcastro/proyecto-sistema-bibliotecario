<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de No Adeudo</title>
    <style>
        @page { margin: 50px 65px; }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            color: #000000; 
            line-height: 1.5; 
        }
        
        /* ENCABEZADO (LOGO INSTITUCIONAL) */
        .header-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 5px;
        }
        .header-table td { 
            vertical-align: middle; 
            border: none; 
            padding: 0;
        }
        .logo-img { 
            height: 70px; 
            width: auto; 
        }
        
        /* SUB-ENCABEZADO */
        .sub-header {
            font-size: 13px;
            color: #111111;
            font-style: italic;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 35px;
        }
        
        /* TÍTULO CENTRAL */
        .doc-title { 
            text-align: center; 
            font-size: 17px; 
            font-weight: bold; 
            color: #000000;
            margin: 25px 0 35px 0; 
            letter-spacing: 0.5px; 
            text-transform: uppercase; 
        }
        
        /* CONTENIDO DE LA CONSTANCIA */
        .content { 
            font-size: 13.5px; 
            text-align: justify; 
            line-height: 1.6; 
            color: #000000; 
        }
        .content p { 
            margin-bottom: 16px; 
        }
        
        /* FECHA Y LUGAR DE EXPEDICIÓN */
        .fecha-lugar {
            margin-top: 25px;
            font-size: 13.5px;
        }
        
        /* ÁREA DE FIRMA (EMPUJADA MÁS ABAJO CON ESPACIO PARA SELLO) */
        .signature-area { 
            margin-top: 150px; 
            text-align: center; 
        }
        .atentamente { 
            font-weight: bold; 
            font-size: 14px; 
            margin-bottom: 100px; /* Espacio amplio para firma en tinta y sello */
        }
        .signature-line { 
            width: 340px; 
            border-top: 1px solid #000000; 
            margin: 0 auto; 
            padding-top: 8px; 
        }
        .signature-sub { 
            font-size: 13px; 
            color: #000000; 
            line-height: 1.35;
        }

        /* PIE DE PÁGINA (FOLIO DISCRETO) */
        .footer-folio { 
            position: absolute; 
            bottom: -10px; 
            left: 0; 
            right: 0; 
            text-align: center; 
            font-size: 9px; 
            color: #888888; 
        }
    </style>
</head>
<body>

    <!-- ENCABEZADO -->
    <table class="header-table">
        <tr>
            <td style="text-align: left;">
                @if(file_exists(public_path('img/LogoConstancia.png')))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/LogoConstancia.png'))) }}" class="logo-img" alt="Logo UPVE">
                @endif
            </td>
        </tr>
    </table>

    <!-- SUB-ENCABEZADO -->
    <div class="sub-header">
        Centro de Información – Biblioteca
    </div>

    <!-- TÍTULO CENTRAL -->
    <div class="doc-title">
        CONSTANCIA DE NO ADEUDO
    </div>

    <!-- CUERPO UNIVERSAL -->
    <div class="content">
        <p>El que suscribe la presente constancia; <strong>HACE CONSTAR</strong></p>

        <p>
            Que el (la) C. <strong>{{ $usuario->NombreUsuario }} {{ $usuario->ApellidoPaterno }} {{ $usuario->ApellidoMaterno ?? '' }}</strong> con número de matrícula <strong>{{ $usuario->Matricula }}</strong>, usuario (a) de la Universidad Politécnica del Valle del Évora.
        </p>

        <p>
            Se hace constar que usuario (a) arriba antes mencionado no tiene adeudo en la Biblioteca de la UPVE, perteneciente a la Secretaría Académica.
        </p>

        <p>
            Se extiende la presente para los fines que convengan al usuario (a).
        </p>

        <p class="fecha-lugar">
            Guamúchil, Sinaloa, a {{ $fecha }}.
        </p>
    </div>

    <!-- ÁREA DE FIRMA Y SELLO -->
    <div class="signature-area">
        <div class="atentamente">Atentamente</div>

        <div class="signature-line">
            <div class="signature-sub">
                <strong>Encargado del Centro de Información</strong><br>
                <strong>(Biblioteca)</strong>
            </div>
        </div>
    </div>

    <!-- PIE DE PÁGINA CON FOLIO -->
    <div class="footer-folio">
        Folio Digital: <strong>{{ $folioDigital }}</strong> | Documento expedido el {{ $fecha }} por el Sistema Bibliotecario UPVE
    </div>

</body>
</html>