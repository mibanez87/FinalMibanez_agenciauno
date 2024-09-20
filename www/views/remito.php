<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <title>Formulario de Remito</title>
</head>

<body>
    <div class="container">
        <form action="#" method="POST">
            <!-- Header Section -->
            <div class="header">
                <div class="header-left">
                    <h1>U</h1>
                    <p>AGENCIAUNO<br>AGENCIA UNO DE TECNOLOGÍA Y COMUNICACIÓN S.R.L.</p>
                    <p>Juncal 253 - 4° B<br>(1722) Merlo - Prov. Bs. As.<br>estudiounoagencia@gmail.com<br>Tel./Fax: (0220) 483-6292</p>
                    <p><strong>IVA RESPONSABLE INSCRIPTO</strong></p>
                </div>
                <div class="header-right">
                    <h2>REMITO</h2>
                    <p>No <input type="text" name="remito_numero" placeholder="0002-00002326"></p>
                    <p>Fecha: <input type="date" name="remito_fecha"></p>
                    <p>CUIT N°: 30-71659703-9<br>ING. BRUTOS: 30-71659703-9<br>INICIO DE ACTIVIDADES: 11/09/2019</p>
                    <p><em>Documento no válido como factura</em></p>
                </div>
            </div>

            <!-- Info Table Section -->
            <table class="info-table">
                <tr>
                    <td>Señor (es): <input type="text" name="cliente_nombre"></td>
                    <td>Teléfono: <input type="text" name="cliente_telefono"></td>
                </tr>
                <tr>
                    <td>Domicilio: <input type="text" name="cliente_domicilio"></td>
                    <td>Localidad: <input type="text" name="cliente_localidad"></td>
                </tr>
                <tr>
                    <td>IVA: <input type="text" name="cliente_iva"></td>
                    <td>CUIT: <input type="text" name="cliente_cuit"></td>
                </tr>
                <tr>
                    <td>Entrega en: <input type="text" name="entrega_en"></td>
                    <td>Fecha de vencimiento: <input type="date" name="fecha_vencimiento"></td>
                </tr>
            </table>

            <!-- Details Table Section -->
            <table class="details-table">
                <tr>
                    <td style="width: 20%;">CANTIDAD</td>
                    <td style="width: 80%;">DETALLE</td>
                </tr>
                <tr>
                    <td><input type="number" name="cantidad1"></td>
                    <td><textarea name="detalle1"></textarea></td>
                </tr>
                <tr>
                    <td><input type="number" name="cantidad2"></td>
                    <td><textarea name="detalle2"></textarea></td>
                </tr>
                <!-- Additional rows can be added as needed -->
            </table>

            <!-- Footer Section -->
            <div class="footer">
                <div class="footer-left">
                    <p>U AGENCIA UNO</p>
                    <p>TALLERES GRÁFICOS PELAZZO & CIA S.R.L. - C.U.I.T N° 30-5754559-3</p>
                    <p>HAB. 395574 - TELEFAX: 0220-4820499<br>FECHA DE IMPRESIÓN: 07/2024 - NUMERACIÓN: 0002-0000251 AL 0002-0002750</p>
                </div>
                <div class="footer-right">
                    <p>Firma _________________________</p>
                    <p>CAI Nro: 50309208236427<br>Fecha de Vto: 25/07/2025</p>
                </div>
            </div>
        </form>
    </div>
</body>

</html>