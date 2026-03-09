/* ============================================================
   MiSocio Printer - App JS
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    // Cargar impresoras y verificar estado al inicio
    detectPrinters();

    // ── Detectar Impresoras ──────────────────────────────────────────────
    document.getElementById('btnDetectPrinters')
        ?.addEventListener('click', function () {
            setLoading(this, true, 'Detectando...');
            detectPrinters(() => setLoading(this, false, '<i class="fas fa-search me-1"></i> Detectar Impresoras'));
        });

    // ── Imprimir Prueba ──────────────────────────────────────────────────
    document.getElementById('btnTestPrint')
        ?.addEventListener('click', function () {
            const btn = this;
            setLoading(btn, true, 'Imprimiendo...');
            fetch('api/test_print.php')
                .then(r => r.ok ? r.json() : r.text().then(t => { throw new Error(t || 'HTTP ' + r.status); }))
                .then(data => {
                    showAlert(data.success ? 'success' : 'danger', data.message);
                    if (data.success && envBool('SOUND_ALERT')) playBeep();
                })
                .catch(e => showAlert('danger', e.message || 'Error al comunicarse con el servidor'))
                .finally(() => setLoading(btn, false, '<i class="fas fa-print me-1"></i> Imprimir Prueba'));
        });

    // ── Guardar Configuracion ────────────────────────────────────────────
    document.getElementById('btnSave')
        ?.addEventListener('click', function () {
            const btn = this;
            setLoading(btn, true, 'Guardando...');
            const data = collectFormData();

            fetch('api/save_config.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(data),
            })
            .then(r => r.json())
            .then(resp => {
                showAlert(resp.success ? 'success' : 'danger', resp.message);
                if (resp.success) {
                    checkPrinterStatus(data.PRINTER_NAME || '');
                }
            })
            .catch(() => showAlert('danger', 'Error al guardar la configuracion'))
            .finally(() => setLoading(btn, false, '<i class="fas fa-save me-1"></i> Guardar Configuracion'));
        });

    // ── Restablecer ──────────────────────────────────────────────────────
    document.getElementById('btnReset')
        ?.addEventListener('click', function () {
            Swal.fire({
                title: '\u00bfDescartar cambios?',
                text: 'Se recargara la pagina y se perderan los cambios no guardados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'S\u00ed, recargar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
            }).then(result => {
                if (result.isConfirmed) location.reload();
            });
        });

    // ── Probar Conexion DB ───────────────────────────────────────────────
    document.getElementById('btnTestDB')
        ?.addEventListener('click', function () {
            const btn    = this;
            const status = document.getElementById('dbStatus');
            setLoading(btn, true, 'Probando...');
            status.innerHTML = '';

            // Guardar credenciales antes de probar
            const dbData = {
                DB_HOST:     document.getElementById('dbHost')?.value     ?? '',
                DB_PORT:     document.getElementById('dbPort')?.value     ?? '3306',
                DB_DATABASE: document.getElementById('dbDatabase')?.value ?? '',
                DB_USERNAME: document.getElementById('dbUsername')?.value ?? '',
            };
            const passEl = document.getElementById('dbPassword');
            if (passEl?.value) dbData.DB_PASSWORD = passEl.value;

            fetch('api/save_config.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(dbData),
            })
            .then(() => fetch('api/test_db.php'))
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    status.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>' + esc(data.message) + '</span>';
                } else {
                    status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>' + esc(data.message) + '</span>';
                }
            })
            .catch(() => {
                status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Error de conexion</span>';
            })
            .finally(() => setLoading(btn, false, '<i class="fas fa-plug me-1"></i> Probar Conexion'));
        });

    // ── Toggle contrasena ────────────────────────────────────────────────
    document.getElementById('btnTogglePass')
        ?.addEventListener('click', function () {
            const passInput = document.getElementById('dbPassword');
            const icon      = this.querySelector('i');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

    // ── Preview del logo ─────────────────────────────────────────────────
    document.getElementById('logoFile')
        ?.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const preview    = document.getElementById('logoPreview');
            const previewImg = document.getElementById('logoPreviewImg');
            if (preview && previewImg) {
                previewImg.src      = URL.createObjectURL(file);
                preview.style.display = 'block';
            }
        });

    // ── Subir Logo ───────────────────────────────────────────────────────
    document.getElementById('btnUploadLogo')
        ?.addEventListener('click', function () {
            const fileInput = document.getElementById('logoFile');
            if (!fileInput?.files[0]) {
                showAlert('warning', 'Selecciona una imagen primero.');
                return;
            }
            const btn      = this;
            const formData = new FormData();
            formData.append('logo', fileInput.files[0]);
            setLoading(btn, true, 'Subiendo...');

            fetch('api/upload_logo.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    showAlert(data.success ? 'success' : 'danger', data.message);
                    if (data.success) setTimeout(() => location.reload(), 1200);
                })
                .catch(() => showAlert('danger', 'Error al subir el logo'))
                .finally(() => setLoading(btn, false, '<i class="fas fa-upload me-1"></i> Subir Logo'));
        });

    // ── Eliminar Logo ────────────────────────────────────────────────────
    document.getElementById('btnRemoveLogo')
        ?.addEventListener('click', function () {
            Swal.fire({
                title: '¿Eliminar logo?',
                text: 'Se eliminara el logo actual del ticket.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
            }).then(result => {
                if (!result.isConfirmed) return;
                fetch('api/save_config.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ LOGO_IMAGE: '' }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Logo eliminado correctamente.');
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        showAlert('danger', data.message);
                    }
                })
                .catch(() => showAlert('danger', 'Error al eliminar el logo'));
            });
        });

    // ── Probar impresion por ID real ─────────────────────────────────────────
    document.getElementById('btnTestById')
        ?.addEventListener('click', function () {
            const btn  = this;
            const tipo = document.getElementById('testTipo')?.value || 'venta';
            const id   = parseInt(document.getElementById('testId')?.value || '0', 10);

            if (!id || id <= 0) {
                showAlert('warning', 'Ingresa un ID valido (numero entero positivo).');
                return;
            }

            setLoading(btn, true, 'Imprimiendo...');

            fetch('print.php?tipo=' + encodeURIComponent(tipo) + '&id=' + id)
                .then(r => r.ok ? r.json() : r.text().then(t => { throw new Error(t || 'HTTP ' + r.status); }))
                .then(data => {
                    showAlert(data.success ? 'success' : 'danger', data.message);
                    if (data.success && envBool('SOUND_ALERT')) playBeep();
                })
                .catch(e => showAlert('danger', e.message || 'Error al comunicarse con el servidor'))
                .finally(() => setLoading(btn, false, '<i class="fas fa-print me-1"></i> Imprimir'));
        });

    // ── Actualizar preview de URL al cambiar tipo o ID ───────────────────────
    function updateUrlPreview() {
        const tipo    = document.getElementById('testTipo')?.value || 'venta';
        const id      = document.getElementById('testId')?.value   || '1';
        const preview = document.getElementById('testUrlPreview');
        if (preview) {
            const base = (typeof window.APP_BASE !== 'undefined') ? window.APP_BASE : '';
            preview.textContent = window.location.host + base + '/' + tipo + '/' + id;
        }
    }
    document.getElementById('testTipo')?.addEventListener('change', updateUrlPreview);
    document.getElementById('testId')?.addEventListener('input',  updateUrlPreview);
    updateUrlPreview();

    // Actualizar estado al cambiar impresora seleccionada
    document.getElementById('printerName')
        ?.addEventListener('change', function () {
            checkPrinterStatus(this.value);
        });
});

// =============================================================================
// Funciones auxiliares
// =============================================================================

/**
 * Detecta impresoras via API y pobla el select.
 */
function detectPrinters(callback) {
    fetch('api/detect_printers.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const select       = document.getElementById('printerName');
            const countEl      = document.getElementById('printerCount');
            const currentValue = select?.value || data.current || '';

            if (select) {
                select.innerHTML = '<option value="">-- Seleccionar impresora --</option>';
                data.printers.forEach(name => {
                    const opt      = document.createElement('option');
                    opt.value      = name;
                    opt.textContent = name + (name.toLowerCase() === currentValue.toLowerCase() ? ' \u2713' : '');
                    if (name.toLowerCase() === currentValue.toLowerCase()) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });
            }

            if (countEl) {
                countEl.textContent = data.count + ' impresora(s) detectada(s)';
            }

            checkPrinterStatus(currentValue);
        })
        .catch(() => {
            const countEl = document.getElementById('printerCount');
            if (countEl) countEl.textContent = 'Error al detectar impresoras';
        })
        .finally(() => { if (callback) callback(); });
}

/**
 * Verifica si la impresora esta en la lista y actualiza el indicador.
 */
function checkPrinterStatus(printerName) {
    const dot  = document.querySelector('.status-dot');
    const text = document.getElementById('connectionText');
    if (!dot || !text) return;

    const name = printerName || document.getElementById('printerName')?.value || '';

    if (!name) {
        dot.className   = 'status-dot status-disconnected';
        text.textContent = 'No hay impresora seleccionada';
        return;
    }

    dot.className    = 'status-dot status-unknown';
    text.textContent = 'Verificando...';

    fetch('api/detect_printers.php')
        .then(r => r.json())
        .then(data => {
            const found = data.printers?.some(p => p.toLowerCase() === name.toLowerCase());
            if (found) {
                dot.className    = 'status-dot status-connected';
                text.textContent = 'Conectada - ' + name;
            } else {
                dot.className    = 'status-dot status-disconnected';
                text.textContent = 'No disponible - ' + name;
            }
        })
        .catch(() => {
            dot.className    = 'status-dot status-unknown';
            text.textContent = 'Estado desconocido';
        });
}

/**
 * Recolecta todos los datos del formulario en un objeto plano.
 */
function collectFormData() {
    const data = {};

    // Campos de texto / select
    const textFields = {
        PRINTER_NAME:    'printerName',
        PAPER_WIDTH:     'paperWidth',
        COMPANY_NAME:    'companyName',
        COMPANY_LOGO:    'companyLogo',
        FOOTER_MESSAGE:  'footerMessage',
        CONTACT_INFO:    'contactInfo',
        DB_HOST:         'dbHost',
        DB_PORT:         'dbPort',
        DB_DATABASE:     'dbDatabase',
        DB_USERNAME:     'dbUsername',
    };

    for (const [envKey, elId] of Object.entries(textFields)) {
        const el = document.getElementById(elId);
        if (el) data[envKey] = el.value;
    }

    // Contrasena: solo si se escribio algo
    const passEl = document.getElementById('dbPassword');
    if (passEl?.value) data.DB_PASSWORD = passEl.value;

    // Checkboxes
    data.SHOW_LOGO   = document.getElementById('showLogo')?.checked   ? 'true' : 'false';
    data.AUTO_CUT    = document.getElementById('autoCut')?.checked    ? 'true' : 'false';
    data.SHOW_QR     = document.getElementById('showQR')?.checked     ? 'true' : 'false';
    data.SOUND_ALERT = document.getElementById('soundAlert')?.checked ? 'true' : 'false';

    return data;
}

/**
 * Muestra una notificacion con SweetAlert2.
 */
function showAlert(type, message) {
    const iconMap = { success: 'success', danger: 'error', warning: 'warning', info: 'info' };
    Swal.fire({
        icon: iconMap[type] || 'info',
        text: String(message),
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
    });
}

/**
 * Activa / desactiva estado de carga en un boton.
 */
function setLoading(btn, loading, originalHtml) {
    if (!btn) return;
    if (loading) {
        btn.disabled   = true;
        btn.innerHTML  = '<i class="fas fa-spinner fa-spin me-1"></i> Espere...';
    } else {
        btn.disabled  = false;
        btn.innerHTML = originalHtml;
    }
}

/**
 * Escapa HTML para prevenir XSS en mensajes de alerta.
 */
function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}

/**
 * Emite un beep usando Web Audio API.
 */
function playBeep() {
    try {
        const ctx  = new (window.AudioContext || window.webkitAudioContext)();
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type      = 'sine';
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.4);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.4);
    } catch (e) { /* silencioso si el navegador no lo soporta */ }
}

/**
 * Lee el valor de SOUND_ALERT del data attribute del body (inyectado por PHP).
 */
function envBool(key) {
    return document.body.dataset[key.toLowerCase()] === 'true';
}
