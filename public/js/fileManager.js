document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('adventure-file-upload-form');
    const fileInput = document.getElementById('adventure-file-input');
    const typeInput = document.getElementById('adventure-file-type');
    const feedback = document.getElementById('adventure-files-feedback');
    const filesContainer = document.getElementById('uploaded-files-container');
    let files = [];

    // Init files from data attribute (json)
    if (filesContainer && filesContainer.dataset.uploadedFiles) {
        files = JSON.parse(filesContainer.dataset.uploadedFiles);
    }

    const maxFiles = 5;
    const maxTotalSize = 50 * 1024 * 1024;

    function renderFiles() {
        filesContainer.innerHTML = '';
        let totalSize = 0;
        files.forEach(f => totalSize += f.size);

        let html = `<div class="file-list-header">Fichiers (${files.length}/5, ${(totalSize/1024/1024).toFixed(1)} Mo / 50 Mo)</div>`;
        if (files.length === 0) {
            html += `<div class="file-empty">No files added.</div>`;
        } else {
            html += `<ul class="file-list">`;
            files.forEach(f => {
                html += 
                `<li class="file-item flex-between" data-id="${f.id}">
                    <div>
                        <i class="fas ${f.typeIcon}"></i> 
                        <span class="file-label">${f.typeLabel}</span> :
                    </div> 
                    <div>
                        <a href="${f.url}" target="_blank">${f.name}</a>
                        <span class="file-size">(${(f.size/1024).toFixed(1)} Ko)</span>
                        <button class="btn-icon file-delete-btn" title="Supprimer" data-id="${f.id}">🗑</button>
                    </div>
                </li>`;
            });
            html += `</ul>`;
        }
        filesContainer.innerHTML = html;
    }
    renderFiles();

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            feedback.textContent = '';
            if (files.length >= maxFiles) {
                feedback.textContent = "Limite de 5 fichiers atteinte.";
                return;
            }
            let totalSize = files.reduce((acc, f) => acc + f.size, 0);
            if (fileInput.files[0] && (totalSize + fileInput.files[0].size > maxTotalSize)) {
                feedback.textContent = "Limite totale de 50 Mo dépassée.";
                return;
            }
            if (!typeInput.value) {
                feedback.textContent = "Choisissez un type de fichier.";
                return;
            }
            if (!fileInput.files[0]) {
                feedback.textContent = "Sélectionnez un fichier.";
                return;
            }

            const data = new FormData();
            data.append('file', fileInput.files[0]);
            data.append('type', typeInput.value);

            fetch(document.getElementById('adventure-file-upload-form').action, {
                method: 'POST',
                body: data
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    files.push(res.file);
                    renderFiles();
                    feedback.textContent = "Fichier ajouté.";
                    form.reset();
                } else {
                    feedback.textContent = res.error || "Erreur lors de l'ajout.";
                }
            })
            .catch(() => {
                feedback.textContent = "Erreur lors de l'ajout.";
            });
        });
    }

    // Suppression AJAX
    filesContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('file-delete-btn')) {
            const id = e.target.dataset.id;
            const adventureId = document.querySelector('[data-adventure-id]')?.dataset.adventureId;
            fetch(`/Pathfinding-MVP/public/user/adventure/${adventureId}/delete-file/${id}`, { method: 'DELETE' })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        files = files.filter(f => f.id != id);
                        renderFiles();
                        feedback.textContent = "Fichier supprimé.";
                    } else {
                        feedback.textContent = res.error || "Erreur lors de la suppression.";
                    }
                });
        }
    });
});
