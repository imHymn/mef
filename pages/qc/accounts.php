<!-- Include modal component -->
<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>

<style>
    .table.custom-hover tbody tr:hover {
        background-color: #dde0e2ff !important;
    }
</style>
<div class="page-content">
    <nav class="page-breadcrumb d-flex justify-content-between align-items-center">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#">Pages</a></li>
            <li class="breadcrumb-item" aria-current="page">Accounts</li>
        </ol>
    </nav>

    <div class="row mt-3">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0">Account List</h6>
                    </div>

                    <div class="row mb-3 col-md-3">
                        <input type="text" id="filter-input" class="form-control" placeholder="Type to filter..." />
                    </div>

                    <table class="custom-hover table">
                        <thead>
                            <tr>
                                <th class="col-md-2" style="text-align: center;">Username <span class="sort-icon"></span></th>
                                <th class="col-md-2" style="text-align: center;">Name <span class="sort-icon"></span></th>
                                <th class="col-md-2" style="text-align: center;">Role <span class="sort-icon"></span></th>
                            </tr>
                        </thead>
                        <tbody id="data-body"></tbody>
                    </table>
                    <div id="pagination-controls" class="mt-2 text-center"></div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    const userRole = "<?= $role ?>";
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;

    function normalize(str) {
        return (str ?? '').toLowerCase().replace(/[\s\-]/g, '');
    }

    let allUsers = [];
    let paginator = null;

    // Ensure modal setup only if it exists
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const productionWrapper = document.getElementById('productionWrapper');
        const productionSelect = document.getElementById('production');
        const productionLocationWrapper = document.getElementById('productionLocationWrapper');

        const modalElement = document.getElementById('updateProductionModal');
        if (modalElement) {
            const productionSelect = document.getElementById('production-edit-type');
            const locationSelect = document.getElementById('production-edit-location');
            const form = document.getElementById('update-production-form');
            const updateModal = new bootstrap.Modal(modalElement);

            // Dynamic filtering for production location
            productionSelect.addEventListener('change', () => {
                const selected = productionSelect.value;
                Array.from(locationSelect.options).forEach(option => {
                    const group = option.closest('optgroup');
                    if (!group) return;
                    option.hidden = group.dataset.group !== selected;
                });
                locationSelect.value = '';
            });

            // Open modal with data via custom event
            modalElement.addEventListener('openProductionModal', (event) => {
                const user = event.detail;
                if (!user) return;

                document.getElementById('production-edit-id').value = user.id ?? '';
                productionSelect.value = user.section ?? '';
                productionSelect.dispatchEvent(new Event('change'));
                locationSelect.value = user.specific_section ?? '';
                updateModal.show();
            });


        }

        // Show/hide form fields based on role & production
        function toggleFields() {
            const role = roleSelect?.value;
            const prod = productionSelect?.value;

            if (role === 'administrator' || role === 'user manager') {
                productionWrapper?.classList.add('d-none');
                productionLocationWrapper?.classList.add('d-none');
            } else if (role) {
                productionWrapper?.classList.remove('d-none');
                if (role === 'line leader' && prod === 'stamping') {
                    productionLocationWrapper?.classList.remove('d-none');
                } else {
                    productionLocationWrapper?.classList.add('d-none');
                }
            } else {
                productionWrapper?.classList.add('d-none');
                productionLocationWrapper?.classList.add('d-none');
            }
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', () => {
                productionSelect.selectedIndex = 0;
                toggleFields();
            });
        }

        if (productionSelect) {
            productionSelect.addEventListener('change', toggleFields);
        }

        toggleFields();
    });


    function renderTable(users) {
        const tbody = document.getElementById('data-body');
        tbody.innerHTML = '';

        users.forEach(user => {
            const tr = document.createElement('tr');
            let production = user.section;
            try {
                production = JSON.parse(production); // will become ["qc"]
                if (Array.isArray(production)) {
                    production = production[0] || ""; // take first element
                }
            } catch (e) {
                // leave it as-is if not valid JSON
            }

            let productionLocation = user.specific_section;
            try {
                productionLocation = JSON.parse(productionLocation);
                if (Array.isArray(productionLocation)) {
                    productionLocation = productionLocation[0] || "";
                }
            } catch (e) {
                // leave it as-is
            }
            tr.innerHTML = `
                <td style="text-align: center;">${highlightText(user.user_id ?? 'null', currentFilterQuery)}</td>
                <td style="text-align: center;">${highlightText(user.name ?? 'null', currentFilterQuery)}</td>
                <td style="text-align: center;">${highlightText(user.role ? user.role.toUpperCase() : 'null', currentFilterQuery)}</td>
      
  </td>
  
      `;
            tbody.appendChild(tr);
        });

        bindUpdateButtons(users);
    }

    function bindUpdateButtons(users) {
        document.querySelectorAll('.btn-update-user').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const selectedUser = users.find(u => u.user_id === userId);
                if (!selectedUser) return;

                const modalEl = document.getElementById('updateProductionModal');
                if (!modalEl) return;

                const event = new CustomEvent('openProductionModal', {
                    detail: selectedUser
                });
                modalEl.dispatchEvent(event);
            });
        });
    }

    function loadAccounts() {
        fetch('api/accounts/getQCAccounts')
            .then(res => res.json())
            .then(data => {
                console.log('Fetched data:', data);
                allUsers = userRole === 'administrator' ?
                    data :
                    data.filter(user => normalize(user.role) === 'operator' && normalize(user.specific_section) === userProductionLocation);

                // Initialize paginator if not already
                if (!paginator) {
                    paginator = createPaginator({
                        data: allUsers,
                        rowsPerPage: 20,
                        renderPageCallback: renderTable,
                        paginationContainerId: 'pagination-controls'
                    });
                    paginator.render();
                } else {
                    paginator.setData(allUsers);
                }

                // Global search filter
                setupSearchFilter({
                    filterInputSelector: '#filter-input',
                    data: allUsers,
                    onFilter: (filtered) => {
                        currentFilterQuery = document.querySelector('#filter-input').value || '';
                        paginator.setData(filtered);
                        paginator.currentPage = 1;
                        paginator.render();
                    },
                    customValueResolver: (user) => {
                        // Concatenate all searchable fields
                        return [
                            user.name,
                            user.role,
                            user.section,
                            user.specific_section,
                            user.user_id
                        ].join(' ') || '';
                    }
                });
            })
            .catch(err => console.error('Error fetching accounts:', err));
    }

    loadAccounts();
    enableTableSorting(".table");
</script>