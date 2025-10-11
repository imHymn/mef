<!-- Include modal component -->
<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<?php include 'modal/updateAccount.php'; ?>
<?php include 'modal/assignOperator.php'; ?>
<?php include 'modal/viewTasks.php'; ?>

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

                    <div class="row mb-3">
                        <div class="col d-flex justify-content-between align-items-center">
                            <input type="text" id="filter-input" class="form-control me-2 col-md-3" placeholder="Type to filter..." />
                            <button id="viewTaskBtn" class="btn btn-sm btn-primary"
                                style="font-size: 18px;">
                                View Task
                            </button>


                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-sm custom-hover">
                            <thead>
                                <tr>
                                    <th style="width:10%;">Username <span class="sort-icon"></span></th>
                                    <th style="width:15%;">Name <span class="sort-icon"></span></th>
                                    <th style="width:10%;">Role <span class="sort-icon"></span></th>
                                    <th style="width:10%;">Department <span class="sort-icon"></span></th>
                                    <th style="width:10%;">Section <span class="sort-icon"></span></th>
                                    <th style="width:5%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="data-body"></tbody>
                        </table>
                    </div>

                    <div id="pagination-controls" class="mt-2 text-center"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="selectedModelDisplay" class="fw-bold text-primary"></div>


<script>
    const userRole = "<?= $role ?>";
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;

    function normalize(str) {
        return (str ?? '').toLowerCase().replace(/[\s\-]/g, '');
    }

    document.getElementById('viewTaskBtn').addEventListener('click', function() {
        viewTasks(userProductionLocation);
    });

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

    function renderTable(users) {
        const tbody = document.getElementById('data-body');
        tbody.innerHTML = '';

        users.forEach(user => {
            const tr = document.createElement('tr');
            // Parse production and specific_section safely
            const production = Array.isArray(user.section) ?
                user.section.join(', ') :
                (() => {
                    try {
                        return JSON.parse(user.section).join(', ');
                    } catch {
                        return user.section ?? 'null';
                    }
                })();

            const productionLocation = Array.isArray(user.specific_section) ?
                user.specific_section.join(', ') :
                (() => {
                    try {
                        return JSON.parse(user.specific_section).join(', ');
                    } catch {
                        return user.specific_section ?? '';
                    }
                })();

            tr.innerHTML = `

    <td>${highlightText(user.user_id ?? 'null', currentFilterQuery)}</td>
    <td>${highlightText(user.name ?? 'null', currentFilterQuery)}</td>
    <td>${highlightText(user.role ? user.role.toUpperCase() : 'null', currentFilterQuery)}</td>
    <td>${highlightText(production.toUpperCase(), currentFilterQuery)}</td>
    <td style="white-space: normal; word-wrap: break-word;">${highlightText(productionLocation ?  productionLocation : '', currentFilterQuery)}</td>
<td>
  <!-- Update Button -->
  <button 
      class="btn btn-sm btn-outline-primary btn-update-user" 
      title="Update"
      data-user-id="${user.user_id}"
      ${user.role === 'line leader' && userRole === 'line leader' ? 'disabled' : ''}>
      <i class="bi bi-pencil-square" style="font-size:16px"></i>
  </button>

  <!-- View Button -->
</td>



`;

            tbody.appendChild(tr);
        });

        bindUpdateButtons(users);
    }

    function loadAccounts() {
        fetch('api/accounts/getAssemblyAccounts')
            .then(res => res.json())
            .then(data => {

                console.log(data)
                if (userRole === 'administrator') {
                    // Admin: show everyone
                    allUsers = data;
                } else if (userRole === 'supervisor') {
                    // Supervisor: filter by production
                    const myProd = normalize((userProduction || [])[0] || "");
                    allUsers = data.filter(user => {
                        let userProdArr = [];
                        try {
                            userProdArr = JSON.parse(user.section);
                        } catch {}
                        return userProdArr.some(p => normalize(p) === myProd);
                    });

                } else if (userRole === 'line leader') {

                    const myLocs = (userProductionLocation || []).map(loc => normalize(loc));
                    allUsers = data.filter(user => {
                        let userLocArr = [];
                        try {
                            userLocArr = JSON.parse(user.specific_section);
                        } catch {}
                        return userLocArr.some(loc => myLocs.includes(normalize(loc)));
                    });
                } else {
                    allUsers = [];
                }

                console.log('Filtered Users:', allUsers);
                console.log('User Role:', userRole, 'User Production:', userProduction, 'User Locations:', userProductionLocation);
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

                setupSearchFilter({
                    filterColumnSelector: '#filter-column',
                    filterInputSelector: '#filter-input',
                    data: allUsers,
                    onFilter: (filteredData, query) => {
                        currentFilterQuery = query; // update the current search term
                        paginator.setData(filteredData);
                        paginator.currentPage = 1;
                        paginator.render();
                    },
                    customColumnHandler: {
                        production: user => `${user.section ?? ''} ${user.specific_section ?? ''}`
                    }
                });
            })
            .catch(err => console.error('Error fetching accounts:', err));
    }


    loadAccounts();
    enableTableSorting(".table");
</script>