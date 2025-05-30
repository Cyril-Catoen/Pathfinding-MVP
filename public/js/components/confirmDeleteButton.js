document.addEventListener('DOMContentLoaded', () => {
    const deleteButtons = document.querySelectorAll('.delete-contact-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            event.preventDefault(); // empêche le lien direct
            const url = button.dataset.url;

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirige vers l'URL de suppression
                    window.location.href = url;
                }
            });
        });
    });
});