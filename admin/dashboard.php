<?php
/**
 * LuxWrap Studio - Admin Dashboard
 * Portfolio management system
 */
require_once __DIR__ . '/../scripts/config.php';
session_start();

if (!isAdminAuthenticated()) {
    header('Location: index.php');
    exit;
}

$portfolio = getPortfolioData();
$csrf = generateCSRFToken();
$successMsg = $_SESSION['success_msg'] ?? '';
$errorMsg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Secreto de despliegue leído del .env del servidor.
// Solo se expone dentro de esta página, que está protegida por login de administrador.
$deploySecret = function_exists('luxwrap_env') ? luxwrap_env('DEPLOY_SECRET', '') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — LuxWrap Studio Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpeg">
    <link rel="stylesheet" href="css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-left">
            <img src="../assets/images/logo.jpeg" alt="LuxWrap Studio" class="admin-logo">
            <h1>Portfolio Manager</h1>
        </div>
        <div class="admin-header-right">
            <a href="../" target="_blank" class="btn-sm btn-outline-light"><i class="fas fa-external-link-alt"></i> View Site</a>
            <a href="logout.php" class="btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <main class="admin-main">
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <!-- Add New Project -->
        <section class="admin-section">
            <div class="section-header" onclick="toggleSection('addProject')">
                <h2><i class="fas fa-plus-circle"></i> Add New Project</h2>
                <i class="fas fa-chevron-down toggle-icon" id="addProject-icon"></i>
            </div>
            <div class="section-body" id="addProject" style="display:none;">
                <form method="POST" action="upload.php" enctype="multipart/form-data" class="project-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="create_project">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Project Name (English) *</label>
                            <input type="text" name="name_en" required placeholder="e.g. Angel's Roofing & Restoration">
                        </div>
                        <div class="form-group">
                            <label>Nombre del Proyecto (Español) *</label>
                            <input type="text" name="name_es" required placeholder="e.g. Angel's Roofing & Restoration">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Description (English)</label>
                            <textarea name="description_en" rows="3" placeholder="Brief description of the project..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Descripción (Español)</label>
                            <textarea name="description_es" rows="3" placeholder="Descripción breve del proyecto..."></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Project Images *</label>
                        <div class="upload-zone" id="uploadZone">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Drag & drop images here or <span class="browse-link">click to browse</span></p>
                            <p class="upload-hint">JPG, PNG, WebP — Max 10MB per image</p>
                            <input type="file" name="images[]" id="fileInput" multiple accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
                        </div>
                        <div class="preview-grid" id="previewGrid"></div>
                    </div>
                    
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Create Project</button>
                </form>
            </div>
        </section>

        <!-- Existing Projects -->
        <section class="admin-section">
            <h2><i class="fas fa-th-large"></i> Current Projects (<?= count($portfolio['projects']) ?>)</h2>
            
            <?php if (empty($portfolio['projects'])): ?>
                <div class="empty-state">
                    <i class="fas fa-images"></i>
                    <p>No projects yet. Add your first project above!</p>
                </div>
            <?php else: ?>
                <div class="projects-list" id="projectsList">
                    <?php foreach ($portfolio['projects'] as $index => $project): ?>
                        <div class="project-card" data-id="<?= htmlspecialchars($project['id']) ?>">
                            <div class="project-card-header">
                                <div class="drag-handle"><i class="fas fa-grip-vertical"></i></div>
                                <div class="project-info">
                                    <h3><?= htmlspecialchars($project['name_en']) ?></h3>
                                    <span class="project-meta"><?= count($project['images'] ?? []) ?> images · Created: <?= htmlspecialchars($project['created'] ?? 'N/A') ?></span>
                                </div>
                                <div class="project-actions">
                                    <button class="btn-sm btn-edit" onclick="toggleEdit('<?= htmlspecialchars($project['id']) ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this entire project and all its images?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="delete_project">
                                        <input type="hidden" name="project_id" value="<?= htmlspecialchars($project['id']) ?>">
                                        <button type="submit" class="btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Image thumbnails -->
                            <div class="project-images-grid">
                                <?php foreach (($project['images'] ?? []) as $img): ?>
                                    <div class="thumb-item">
                                        <img src="../<?= htmlspecialchars($img['path']) ?>" alt="" loading="lazy">
                                        <form method="POST" action="delete.php" class="thumb-delete" onsubmit="return confirm('Delete this image?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="action" value="delete_image">
                                            <input type="hidden" name="project_id" value="<?= htmlspecialchars($project['id']) ?>">
                                            <input type="hidden" name="filename" value="<?= htmlspecialchars($img['filename']) ?>">
                                            <button type="submit" class="btn-icon-danger"><i class="fas fa-times"></i></button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Edit form (hidden) -->
                            <div class="edit-form" id="edit-<?= htmlspecialchars($project['id']) ?>" style="display:none;">
                                <form method="POST" action="upload.php" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="update_project">
                                    <input type="hidden" name="project_id" value="<?= htmlspecialchars($project['id']) ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Name (EN)</label>
                                            <input type="text" name="name_en" value="<?= htmlspecialchars($project['name_en'] ?? '') ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Nombre (ES)</label>
                                            <input type="text" name="name_es" value="<?= htmlspecialchars($project['name_es'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Description (EN)</label>
                                            <textarea name="description_en" rows="2"><?= htmlspecialchars($project['description_en'] ?? '') ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Descripción (ES)</label>
                                            <textarea name="description_es" rows="2"><?= htmlspecialchars($project['description_es'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Add More Images</label>
                                        <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/gif">
                                    </div>
                                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Update Site from GitHub -->
        <section class="admin-section">
            <div class="section-header" onclick="toggleSection('updateSite')">
                <h2><i class="fas fa-sync-alt"></i> Update Site / Actualizar Sitio</h2>
                <i class="fas fa-chevron-down toggle-icon" id="updateSite-icon"></i>
            </div>
            <div class="section-body" id="updateSite" style="display:none;">
                <p style="margin-top:0;color:#c9c9d6;line-height:1.6;">
                    Trae los últimos cambios publicados en GitHub al sitio en vivo.
                    No necesitas escribir ninguna clave: el sistema ya la incluye de forma segura.
                </p>
                <?php if (empty($deploySecret)): ?>
                    <div class="alert alert-error" style="display:flex;">
                        <i class="fas fa-exclamation-triangle"></i>
                        No se encontró <code>DEPLOY_SECRET</code> en el archivo <code>.env</code> del servidor.
                        Agrégalo para poder actualizar desde aquí.
                    </div>
                <?php else: ?>
                    <button type="button" id="deployBtn" class="btn-primary">
                        <i class="fas fa-cloud-download-alt"></i> Actualizar sitio desde GitHub
                    </button>
                    <pre id="deployResult" style="display:none;white-space:pre-wrap;margin-top:18px;background:#0b0b14;border:1px solid rgba(255,255,255,.14);border-radius:12px;padding:16px;max-height:340px;overflow:auto;color:#e8e8f2;"></pre>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
    // Toggle sections
    function toggleSection(id) {
        const el = document.getElementById(id);
        const icon = document.getElementById(id + '-icon');
        if (el.style.display === 'none') {
            el.style.display = 'block';
            if (icon) icon.style.transform = 'rotate(180deg)';
        } else {
            el.style.display = 'none';
            if (icon) icon.style.transform = '';
        }
    }
    
    // Toggle edit form
    function toggleEdit(id) {
        const el = document.getElementById('edit-' + id);
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }

    // Drag & Drop Upload
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const previewGrid = document.getElementById('previewGrid');

    if (uploadZone && fileInput) {
        uploadZone.addEventListener('click', () => fileInput.click());
        uploadZone.addEventListener('dragover', (e) => { e.preventDefault(); uploadZone.classList.add('dragover'); });
        uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            showPreviews(e.dataTransfer.files);
        });
        fileInput.addEventListener('change', () => showPreviews(fileInput.files));
    }

    function showPreviews(files) {
        if (!previewGrid) return;
        previewGrid.innerHTML = '';
        Array.from(files).forEach(file => {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `<img src="${e.target.result}" alt="Preview"><span>${file.name}</span>`;
                previewGrid.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    // Auto-hide alerts
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => alert.style.display = 'none', 5000);
    });

    // ===== Update Site from GitHub (AJAX) =====
    (function () {
        const deployBtn = document.getElementById('deployBtn');
        if (!deployBtn) return;
        const deployResult = document.getElementById('deployResult');
        // Secreto inyectado desde el .env del servidor (solo visible para admin autenticado).
        const DEPLOY_SECRET = <?= json_encode($deploySecret) ?>;

        deployBtn.addEventListener('click', async () => {
            if (!confirm('¿Actualizar el sitio con los últimos cambios de GitHub?')) return;

            const originalHtml = deployBtn.innerHTML;
            deployBtn.disabled = true;
            deployBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            deployResult.style.display = 'block';
            deployResult.style.color = '#e8e8f2';
            deployResult.textContent = 'Conectando con el servidor...';

            try {
                const response = await fetch('../deploy.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json'
                    },
                    body: new URLSearchParams({ secret: DEPLOY_SECRET })
                });
                const text = await response.text();
                let json = null;
                try { json = JSON.parse(text); } catch (e) {}

                if (json && json.success) {
                    deployResult.style.color = '#65ff9a';
                    deployResult.textContent = '✅ ' + (json.message || 'Sitio actualizado correctamente') +
                        '\n\n' + JSON.stringify(json, null, 2);
                } else {
                    deployResult.style.color = '#ff7777';
                    deployResult.textContent = '❌ No se pudo actualizar.\n\n' +
                        (json ? JSON.stringify(json, null, 2) : text);
                }
            } catch (error) {
                deployResult.style.color = '#ff7777';
                deployResult.textContent = '❌ Error de conexión: ' + error.message;
            } finally {
                deployBtn.disabled = false;
                deployBtn.innerHTML = originalHtml;
            }
        });
    })();
    </script>
</body>
</html>
