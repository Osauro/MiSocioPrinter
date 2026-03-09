<?php
require_once __DIR__ . '/config/config.php';

// Base URL dinámica (funciona tanto en subdir como en virtual host)
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$appBase   = $scriptDir === '/' ? '' : $scriptDir;

// Valores actuales del .env
$printerName   = env('PRINTER_NAME',   '');
$paperWidth    = env('PAPER_WIDTH',    '37');
$companyName   = env('COMPANY_NAME',   '');
$companyLogo   = env('COMPANY_LOGO',   '');
$footerMessage = env('FOOTER_MESSAGE', '');
$contactInfo   = env('CONTACT_INFO',   '');
$showLogo      = envBool('SHOW_LOGO',   true);
$showQR        = envBool('SHOW_QR',     true);
$autoCut       = envBool('AUTO_CUT',    true);
$soundAlert    = envBool('SOUND_ALERT', true);
$logoImage     = env('LOGO_IMAGE',     '');
$dbHost        = env('DB_HOST',        '127.0.0.1');
$dbPort        = env('DB_PORT',        '3306');
$dbName        = env('DB_DATABASE',    'misocio');
$dbUser        = env('DB_USERNAME',    'root');

$vendorOk = file_exists(__DIR__ . '/vendor/autoload.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema POS - Configuracion de Impresora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body data-sound_alert="<?= $soundAlert ? 'true' : 'false' ?>">

    <!-- Navbar -->
    <nav class="navbar navbar-dark">
        <div class="container-fluid px-4">
            <span class="navbar-brand mb-0 h1">
                <i class="fas fa-print me-2"></i>
                Sistema POS - Configuracion de Impresora
            </span>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">

                <!-- Aviso si falta composer -->
                <?php if (!$vendorOk): ?>
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
                    <i class="fas fa-exclamation-triangle mt-1 flex-shrink-0"></i>
                    <div>
                        <strong>Dependencias no instaladas.</strong>
                        Ejecuta el siguiente comando en la carpeta del proyecto:<br>
                        <code>composer install</code>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contenedor de alertas -->
                <div id="alertContainer"></div>

                <!-- Tarjeta principal -->
                <div class="card shadow">

                    <!-- Cabecera -->
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Configuracion de Impresora
                        </h5>
                    </div>

                    <div class="card-body p-4">

                        <!-- Tabs de navegacion -->
                        <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabImpresora" type="button" role="tab">
                                    <i class="fas fa-print me-1"></i> Impresora
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabBaseDatos" type="button" role="tab">
                                    <i class="fas fa-database me-1"></i> Base de Datos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabLogo" type="button" role="tab">
                                    <i class="fas fa-image me-1"></i> Logo
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">

                            <!-- ═══════════ TAB: IMPRESORA ═══════════ -->
                            <div class="tab-pane fade show active" id="tabImpresora" role="tabpanel">

                                <p class="text-muted small mb-2">Configuracion Actual</p>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="printerName">Nombre de la Impresora</label>
                                        <select class="form-select" id="printerName" name="PRINTER_NAME">
                                            <option value="<?= htmlspecialchars($printerName) ?>" selected>
                                                <?= htmlspecialchars($printerName !== '' ? $printerName . ' \u2713' : 'Cargando...') ?>
                                            </option>
                                        </select>
                                        <div class="form-text text-muted" id="printerCount">Detectando impresoras...</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="paperWidth">Ancho del Papel (caracteres)</label>
                                        <select class="form-select" id="paperWidth" name="PAPER_WIDTH">
                                            <option value="32" <?= $paperWidth === '32' ? 'selected' : '' ?>>32 (58mm)</option>
                                            <option value="37" <?= $paperWidth === '37' ? 'selected' : '' ?>>37 (80mm)</option>
                                            <option value="42" <?= $paperWidth === '42' ? 'selected' : '' ?>>42 (80mm amplio)</option>
                                            <option value="48" <?= $paperWidth === '48' ? 'selected' : '' ?>>48 (80mm maximo)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Estado de conexion -->
                                <p class="section-label text-primary">
                                    <i class="fas fa-wifi"></i> Estado de Conexion
                                </p>
                                <div id="connectionStatus" class="mb-3">
                                    <span class="status-dot status-unknown"></span>
                                    <span id="connectionText">Verificando...</span>
                                </div>

                                <hr>

                                <!-- Informacion de la Empresa -->
                                <p class="section-label text-primary">
                                    <i class="fas fa-building"></i> Informacion de la Empresa
                                </p>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="companyName">Nombre de la Empresa</label>
                                        <input type="text" class="form-control" id="companyName" name="COMPANY_NAME"
                                               value="<?= htmlspecialchars($companyName) ?>"
                                               placeholder="Mi Empresa S.A.">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="companyLogo">Marca/Logo (texto)</label>
                                        <input type="text" class="form-control" id="companyLogo" name="COMPANY_LOGO"
                                               value="<?= htmlspecialchars($companyLogo) ?>"
                                               placeholder="¤ LOGO ¤">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="footerMessage">Mensaje de Pie</label>
                                        <input type="text" class="form-control" id="footerMessage" name="FOOTER_MESSAGE"
                                               value="<?= htmlspecialchars($footerMessage) ?>"
                                               placeholder="GRACIAS POR SU COMPRA">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="contactInfo">Informacion de Contacto</label>
                                        <input type="text" class="form-control" id="contactInfo" name="CONTACT_INFO"
                                               value="<?= htmlspecialchars($contactInfo) ?>"
                                               placeholder="TEL: 000-0000">
                                    </div>
                                </div>

                                <hr>

                                <!-- Configuracion de Recibos -->
                                <p class="section-label text-primary">
                                    <i class="fas fa-receipt"></i> Configuracion de Recibos
                                </p>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="showLogo"
                                                   <?= $showLogo ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="showLogo">Mostrar Logo</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="autoCut"
                                                   <?= $autoCut ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="autoCut">Corte Automatico</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="showQR"
                                                   <?= $showQR ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="showQR">Mostrar Codigo QR</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="soundAlert"
                                                   <?= $soundAlert ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="soundAlert">Alerta Sonora</label>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Pruebas -->
                                <p class="section-label">
                                    <i class="fas fa-flask text-secondary"></i>
                                    <span class="text-secondary">Pruebas</span>
                                </p>

                                <!-- Fila 1: prueba general + detectar -->
                                <div class="d-flex gap-2 tests-section mb-3">
                                    <button type="button" class="btn btn-outline-secondary" id="btnTestPrint"
                                            <?= !$vendorOk ? 'disabled title="Ejecuta composer install primero"' : '' ?>>
                                        <i class="fas fa-print me-1"></i> Imprimir Prueba
                                    </button>
                                    <button type="button" class="btn btn-outline-info" id="btnDetectPrinters">
                                        <i class="fas fa-search me-1"></i> Detectar Impresoras
                                    </button>
                                </div>

                                <!-- Fila 2: prueba por ID (venta o prestamo real) -->
                                <div class="card border-0 bg-light p-3 rounded">
                                    <p class="small fw-semibold text-secondary mb-2">
                                        <i class="fas fa-vial me-1"></i> Probar impresion con ID real
                                    </p>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-sm-4">
                                            <label class="form-label" for="testTipo">Tipo</label>
                                            <select class="form-select form-select-sm" id="testTipo">
                                                <option value="venta">Venta</option>
                                                <option value="prestamo">Prestamo</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label" for="testId">ID</label>
                                            <input type="number" class="form-control form-control-sm" id="testId"
                                                   min="1" placeholder="Ej: 1" value="1">
                                        </div>
                                        <div class="col-sm-4">
                                            <button type="button" class="btn btn-sm btn-primary w-100" id="btnTestById"
                                                    <?= !$vendorOk ? 'disabled' : '' ?>>
                                                <i class="fas fa-print me-1"></i> Imprimir
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2 small text-muted">
                                        URL generada: <code id="testUrlPreview">
                                            <?= htmlspecialchars(($_SERVER['HTTP_HOST'] ?? 'localhost') . $appBase) ?>/venta/1
                                        </code>
                                    </div>
                                </div>

                            </div><!-- /tabImpresora -->

                            <!-- ═══════════ TAB: BASE DE DATOS ═══════════ -->
                            <div class="tab-pane fade" id="tabBaseDatos" role="tabpanel">
                                <form id="formDB" autocomplete="off" onsubmit="return false;">
                                <p class="text-muted small mb-3">
                                    Configura la conexion a la base de datos MySQL de MiSocio.
                                </p>

                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label" for="dbHost">Host</label>
                                        <input type="text" class="form-control" id="dbHost" name="DB_HOST"
                                               value="<?= htmlspecialchars($dbHost) ?>"
                                               placeholder="127.0.0.1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="dbPort">Puerto</label>
                                        <input type="number" class="form-control" id="dbPort" name="DB_PORT"
                                               value="<?= htmlspecialchars($dbPort) ?>"
                                               min="1" max="65535" placeholder="3306">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="dbDatabase">Nombre de la Base de Datos</label>
                                        <input type="text" class="form-control" id="dbDatabase" name="DB_DATABASE"
                                               value="<?= htmlspecialchars($dbName) ?>"
                                               placeholder="misocio">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="dbUsername">Usuario</label>
                                        <input type="text" class="form-control" id="dbUsername" name="DB_USERNAME"
                                               value="<?= htmlspecialchars($dbUser) ?>"
                                               placeholder="root"
                                               autocomplete="username">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="dbPassword">Contrasena</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="dbPassword" name="DB_PASSWORD"
                                                   placeholder="••••••••"
                                                   autocomplete="current-password">
                                            <button class="btn btn-outline-secondary" type="button" id="btnTogglePass"
                                                    title="Mostrar/ocultar contrasena">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Deja en blanco para no modificar la contrasena actual.</div>
                                    </div>
                                    <div class="col-12 d-flex align-items-center gap-3">
                                        <button type="button" class="btn btn-outline-success" id="btnTestDB"
                                                <?= !$vendorOk ? 'disabled' : '' ?>>
                                            <i class="fas fa-plug me-1"></i> Probar Conexion
                                        </button>
                                        <span id="dbStatus"></span>
                                    </div>
                                </div>
                                </form>

                            </div><!-- /tabBaseDatos -->

                            <!-- ═══════════ TAB: LOGO ═══════════ -->
                            <div class="tab-pane fade" id="tabLogo" role="tabpanel">

                                <p class="text-muted small mb-3">
                                    Sube el logotipo de tu empresa para imprimirlo en los tickets.<br>
                                    Formatos soportados: <strong>PNG, GIF, BMP</strong> &mdash; Maximo 2 MB.<br>
                                    <i class="fas fa-lightbulb text-warning"></i>
                                    Recomendado: imagen en blanco y negro, maximo 300 px de ancho.
                                </p>

                                <?php if ($logoImage !== ''): ?>
                                <div class="mb-4 text-center">
                                    <p class="small text-muted mb-2">Logo actual:</p>
                                    <img src="uploads/<?= htmlspecialchars($logoImage) ?>"
                                         alt="Logo actual"
                                         class="img-thumbnail"
                                         style="max-height: 100px; max-width: 280px;">
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoveLogo">
                                            <i class="fas fa-trash me-1"></i> Eliminar Logo
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div id="logoPreview" class="mb-3 text-center py-3 border rounded bg-light"
                                     style="display:none">
                                    <img src="" id="logoPreviewImg" alt="Vista previa"
                                         style="max-height:100px; max-width:280px;">
                                    <p class="small text-muted mt-1 mb-0">Vista previa</p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="logoFile">Seleccionar imagen</label>
                                    <input type="file" class="form-control" id="logoFile" accept=".png,.gif,.bmp">
                                </div>

                                <button type="button" class="btn btn-outline-primary" id="btnUploadLogo">
                                    <i class="fas fa-upload me-1"></i> Subir Logo
                                </button>

                            </div><!-- /tabLogo -->

                        </div><!-- /tab-content -->
                    </div><!-- /card-body -->

                    <!-- Pie con botones -->
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" id="btnReset">
                            <i class="fas fa-undo me-1"></i> Restablecer
                        </button>
                        <button type="button" class="btn btn-primary" id="btnSave">
                            <i class="fas fa-save me-1"></i> Guardar Configuracion
                        </button>
                    </div>

                </div><!-- /card -->
            </div><!-- /col -->
        </div><!-- /row -->
    </div><!-- /container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>window.APP_BASE = <?= json_encode($appBase) ?>;</script>
    <script src="assets/js/app.js"></script>
</body>
</html>
