function setupSearchBar(inputId, clearId, rowSelector) {
    const input = document.getElementById(inputId);
    const clear = document.getElementById(clearId);

    if (!input || !clear) return;

    function filterRows() {
        const val = input.value.toLowerCase();
        clear.style.display = val ? 'inline' : 'none';

        document.querySelectorAll(rowSelector).forEach(row => {
            const name = row.cells[0]?.textContent.toLowerCase() || '';
            const surname = row.cells[1]?.textContent.toLowerCase() || '';
            row.style.display = (name.includes(val) || surname.includes(val)) ? '' : 'none';
        });
    }

    input.addEventListener('input', filterRows);

    clear.addEventListener('click', () => {
        input.value = '';
        clear.style.display = 'none';
        document.querySelectorAll(rowSelector).forEach(row => {
            row.style.display = '';
        });
    });

    filterRows(); // Appel initial
}

window.setupSearchBar = setupSearchBar;
