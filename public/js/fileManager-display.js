document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('adventure-file-upload-form');
    const fileInput = document.getElementById('adventure-file-input');
    const typeInput = document.getElementById('adventure-file-type');
    const feedback = document.getElementById('adventure-files-feedback');
    const filesContainer = document.getElementById('uploaded-files-container');
    let files = [];

    // Vérifie si l'utilisateur est admin en regardant un attribut de body ou main
    const isAdmin = document.body.dataset.role === 'admin';

    // Init files depuis attribut data
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
            html += `<div class="file-empty">Aucun fichier disponible.</div>`;
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
                    </div>
                </li>`;
            });
            html += `</ul>`;
        }
        filesContainer.innerHTML = html;
    }

    renderFiles();
  
});
