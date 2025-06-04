// photoManager.js

const maxPhotos = 12;
let uploadedPhotos = [];
let modalPhotosArray = [];
let carouselIndex = 0;

// Initialisation principale
document.addEventListener('DOMContentLoaded', initPhotoManager);

function initPhotoManager() {
    const photoData = document.getElementById('photo-data');
    if (!photoData) return;

    try {
        uploadedPhotos = JSON.parse(photoData.dataset.pictures2 || '[]');
    } catch (e) {
        console.error("Erreur parsing uploaded photos:", e);
    }

    initDisplayCarousel();
    updateModalCounter();
}

// Affichage carrousel en mode lecture seule
function initDisplayCarousel() {
    const photoData = document.getElementById('photo-data');
    if (!photoData?.dataset.pictures) return;

    const displayPictures = JSON.parse(photoData.dataset.pictures);
    const left = document.getElementById('carousel-left');
    const center = document.getElementById('carousel-center');
    const right = document.getElementById('carousel-right');

    if (!left || !center || !right || displayPictures.length < 4) return;

    function updateDisplayCarousel() {
        const total = displayPictures.length;
        const indexes = [
            (carouselIndex - 1 + total) % total,
            carouselIndex % total,
            (carouselIndex + 1) % total
        ];

        left.src = displayPictures[indexes[0]];
        center.src = displayPictures[indexes[1]];
        right.src = displayPictures[indexes[2]];
    }

    window.prevDisplayCarousel = function () {
        carouselIndex = (carouselIndex - 1 + displayPictures.length) % displayPictures.length;
        updateDisplayCarousel();
    };

    window.nextDisplayCarousel = function () {
        carouselIndex = (carouselIndex + 1) % displayPictures.length;
        updateDisplayCarousel();
    };

    updateDisplayCarousel();
}

// Gestion des modales d'édition de photo
function openPhotoModal() {
    document.getElementById('photo-edit-modal')?.classList.remove('d-none');
    updateModalCounter();
    renderModalPreviews();
}

function closePhotoModal() {
    document.getElementById('photo-edit-modal')?.classList.add('d-none');
    modalPhotosArray = [];
    document.getElementById('photo-preview-grid').innerHTML = '';
    updateModalCounter();
}

function enterPhotoEditMode() {
    document.getElementById('photo-display-mode')?.classList.add('d-none');
    document.getElementById('photo-upload-form')?.classList.remove('d-none');
}

function exitPhotoEditMode() {
    document.getElementById('photo-display-mode')?.classList.remove('d-none');
    document.getElementById('photo-upload-form')?.classList.add('d-none');
    modalPhotosArray = [];
    carouselIndex = 0;
    document.getElementById('photo-preview-grid').innerHTML = '';
    updateModalCounter();
}

function handlePhotoSelection(event) {
    const files = Array.from(event.target.files);
    const uploadedCount = uploadedPhotos.length;
    const remaining = maxPhotos - uploadedCount - modalPhotosArray.length;

    if (files.length > remaining) {
        alert(`Vous ne pouvez ajouter que ${remaining} photo(s) supplémentaire(s).`);
        return;
    }

    files.forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            modalPhotosArray.push({ file, url: e.target.result });
            renderModalPreviews();
            updateModalCounter();
        };
        reader.readAsDataURL(file);
    });
    event.target.value = '';
}

function renderModalPreviews() {
    const container = document.getElementById('photo-preview-grid');
    container.innerHTML = '';
    modalPhotosArray.forEach((p, index) => {
        const wrapper = document.createElement('div');
        wrapper.classList.add('photo-thumb-wrapper');

        const img = document.createElement('img');
        img.src = p.url;

        const delBtn = document.createElement('button');
        delBtn.textContent = '❌';
        delBtn.onclick = () => {
            modalPhotosArray.splice(index, 1);
            renderModalPreviews();
            updateModalCounter();
        };

        wrapper.appendChild(img);
        wrapper.appendChild(delBtn);
        container.appendChild(wrapper);
    });
}

function updateModalCounter() {
    const total = uploadedPhotos.length + modalPhotosArray.length;
    const counter = document.getElementById('photo-counter');
    if (counter) counter.textContent =  `${total}/12 pictures`;
}

function submitPhotoUpload() {
    if (modalPhotosArray.length === 0) return;

    const formData = new FormData();
    modalPhotosArray.forEach(p => formData.append('photos[]', p.file));

    fetch(document.getElementById('photo-upload-form').action, {
        method: 'POST',
        body: formData
    })
    .then(res => res.ok ? location.reload() : alert("Erreur lors de l'envoi"))
    .catch(() => alert("Erreur réseau."));
}

function deletePhotoFromElement(button) {
    const photoId = button.dataset.id;
    const adventureId = document.getElementById('photo-data').dataset.adventureId;

    if (!confirm("Supprimer cette photo ?")) return;

    fetch(`/Pathfinding-MVP/public/user/adventure/${adventureId}/delete-photo/${photoId}`, {
        method: 'DELETE',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            uploadedPhotos = uploadedPhotos.filter(p => p.id !== parseInt(photoId));
            updateModalCounter();

            const wrapper = button.closest('.photo-full-wrapper') || button.closest('.photo-thumb-wrapper');
            if (wrapper) wrapper.remove();

            refreshPreviewLimitedGrid();
        } else {
            alert(data.error || "Erreur lors de la suppression.");
        }
    })
    .catch(err => {
        console.error("Erreur réseau :", err);
        alert("Une erreur réseau est survenue.");
    });
}

function refreshPreviewLimitedGrid() {
    const previewGrid = document.getElementById('photo-preview-limited');
    if (!previewGrid) return;

    const btnSeeAll = previewGrid.querySelector('.btn-see-all');

    // Nettoyage des anciennes miniatures, sauf le bouton "See all"
    Array.from(previewGrid.children).forEach(child => {
        if (!child.classList.contains('btn-see-all')) {
            previewGrid.removeChild(child);
        }
    });

    // Créer un Set des IDs déjà affichés pour éviter les doublons
    const displayedIds = new Set(
        Array.from(previewGrid.querySelectorAll('img')).map(img => parseInt(img.dataset.id, 10))
    );

    // Sélectionner jusqu'à 6 photos à afficher
    const newThumbs = uploadedPhotos
        .filter(p => !displayedIds.has(p.id))
        .slice(0, 6);

    // Ajouter les nouvelles miniatures
    newThumbs.forEach(photo => {
        const wrapper = document.createElement('div');
        wrapper.classList.add('photo-thumb-wrapper');
        wrapper.innerHTML = `
            <img src="${photo.path}" alt="Photo" class="thumb-img" data-id="${photo.id}" onclick="openImagePreview('${photo.path}')">
            <button type="button" class="delete-photo-btn" 
                    data-id="${photo.id}" 
                    data-adventure-id="${document.getElementById('photo-data').dataset.adventureId}"
                    onclick="deletePhotoFromElement(this)">
                <i class="fas fa-times"></i>
            </button>
        `;
        previewGrid.insertBefore(wrapper, btnSeeAll);
    });

    // Supprimer le bouton "See all" s’il y a 6 photos ou moins
    if (uploadedPhotos.length <= 6 && btnSeeAll) {
        btnSeeAll.remove();
    }
}


function togglePhotoPopup(show) {
    const popup = document.getElementById('photo-popup');
    popup?.classList.toggle('d-none', !show);
}

function openImagePreview(src) {
    const overlay = document.getElementById('image-overlay');
    const img = document.getElementById('overlay-image');
    img.src = src;
    overlay?.classList.remove('d-none');
}

function closeImagePreview() {
    const overlay = document.getElementById('image-overlay');
    overlay?.classList.add('d-none');
}

function handleCarouselClick() {
    const center = document.getElementById('carousel-center');
    if (center?.src) {
        openImagePreview(center.src);
    }
}
