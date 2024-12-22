document.addEventListener('DOMContentLoaded', function() {
    function toggleUserStatus(userId) {
        if (!confirm('Voulez-vous vraiment changer le statut de cet utilisateur ?')) {
            return;
        }

        window.location.href = 'toggle_user_status.php?user_id=' + userId;
    }

    function searchUsers() {
        const searchInput = document.getElementById('user-search-input');
        const userTable = document.getElementById('users-table');
        const rows = userTable.querySelectorAll('tbody tr');
        const searchTerm = searchInput.value.toLowerCase();

        rows.forEach(row => {
            const cells = row.getElementsByTagName('td');
            const shouldShow = Array.from(cells).some(cell => 
                cell.textContent.toLowerCase().includes(searchTerm)
            );
            row.style.display = shouldShow ? '' : 'none';
        });
    }

    const toggleStatusButtons = document.querySelectorAll('.toggle-status-btn');
    toggleStatusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            toggleUserStatus(userId);
        });
    });

    const searchInput = document.getElementById('user-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', searchUsers);
    }

    function setupPagination() {
        const itemsPerPage = 10;
        const rows = document.querySelectorAll('#users-table tbody tr');
        const pageCount = Math.ceil(rows.length / itemsPerPage);
        const paginationContainer = document.getElementById('pagination');

        paginationContainer.innerHTML = '';

        for (let i = 1; i <= pageCount; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            pageButton.addEventListener('click', () => {
                showPage(i);
            });
            paginationContainer.appendChild(pageButton);
        }

        function showPage(page) {
            const startIndex = (page - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;

            rows.forEach((row, index) => {
                row.style.display = (index >= startIndex && index < endIndex) ? '' : 'none';
            });
        }

        showPage(1);
    }

    setupPagination();

    const userForms = document.querySelectorAll('.user-management-form');
    userForms.forEach(form => {
        form.addEventListener('submit', function(e) {
        });
    });
});