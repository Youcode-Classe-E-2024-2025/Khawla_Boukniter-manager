document.addEventListener('DOMContentLoaded', function() {
    // Toggle user status
    function toggleUserStatus(userId) {
        // Confirm before changing status
        if (!confirm('Voulez-vous vraiment changer le statut de cet utilisateur ?')) {
            return;
        }

        // Redirect to toggle_user_status.php with user ID
        window.location.href = 'toggle_user_status.php?user_id=' + userId;
    }

    // Search and filter users
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

    // Attach event listeners
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

    // Pagination functionality
    function setupPagination() {
        const itemsPerPage = 10;
        const rows = document.querySelectorAll('#users-table tbody tr');
        const pageCount = Math.ceil(rows.length / itemsPerPage);
        const paginationContainer = document.getElementById('pagination');

        // Clear existing pagination
        paginationContainer.innerHTML = '';

        // Create pagination buttons
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

        // Show first page by default
        showPage(1);
    }

    // Initialize pagination
    setupPagination();

    // Handle form submissions
    const userForms = document.querySelectorAll('.user-management-form');
    userForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Optional: Add client-side validation here
            // e.g., check password strength, required fields, etc.
        });
    });
});