<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<?php include 'modal/createAccount.php'; ?>
<?php include 'modal/updateAccount.php'; ?>
<?php include 'modal/generateQRCode.php'; ?>
<style>
    table.custom-hover th,
    table.custom-hover td {
        white-space: nowrap;
        /* keep in one line */
        overflow: hidden;
        /* hide overflow text */
        text-overflow: ellipsis;
        /* show … for overflow */
        max-width: 250px;
        /* prevent long content from expanding table */
    }

    .table-responsive {
        overflow-x: auto;
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
                        <div>
                            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                                Create Account
                            </button>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateQR">
                                Generate QR Code
                            </button>

                        </div>
                    </div>

                    <div class="row mb-3 col-md-3">

                        <input
                            type="text"
                            id="filter-input"
                            class="form-control"
                            placeholder="Type to filter..." />

                    </div>

                    <table class="custom-hover table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Username <span class="sort-icon"></span></th>
                                <th style="width: 20%;">Name <span class="sort-icon"></span></th>
                                <th style="width: 15%;">Role <span class="sort-icon"></span></th>
                                <th style="width: 20%;">Department <span class="sort-icon"></span></th>
                                <th style="width: 20%;">Section <span class="sort-icon"></span></th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="data-body"></tbody>
                    </table>

                    <div id="pagination-controls" class="mt-2 text-center"></div>

                </div>
            </div>
        </div>
    </div>

    </form>
</div>
</div>
</div>
<link href="assets/css/all.min.css" rel="stylesheet">
</link>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<link href="./assets/css/bootstrap-icons.css" rel="stylesheet">

<script>
    const updateUserModal = new bootstrap.Modal(document.getElementById('updateUserModal'));


    const productionWrapper = document.getElementById('productionWrapper');
    const productionSelect = document.getElementById('production');
    const productionLocationWrapper = document.getElementById('productionLocationWrapper');



    // Account table rendering and pagination logic
    let allUsers = [];
    let paginator = null;

    function renderTable(users) {
        const tbody = document.getElementById('data-body');
        tbody.innerHTML = '';

        users.forEach(user => {
            if (user.role === 'administrator') return; // Skip ADMINISTRATOR accounts
            const tr = document.createElement('tr');
            tr.innerHTML = `
       <td >${highlightText(user.user_id ?? '<i>NONE</i>', currentFilterQuery)}</td>
      <td>${highlightText(user.name ?? '<i>NONE</i>', currentFilterQuery)}</td>
      <td>${highlightText(user.role ? user.role.toUpperCase() : '<i>NONE</i>', currentFilterQuery)}</td>
     <td>
  ${user.section 
    ? highlightText(
        (() => {
          try {
            const arr = JSON.parse(user.section);
            return Array.isArray(arr) 
              ? arr.map(p => p.toUpperCase()).join(', ')
              : String(user.section).toUpperCase();
          } catch {
            return String(user.section).toUpperCase();
          }
        })(),
        currentFilterQuery
      )
    : '<i>NONE</i>'
  }
</td>

<td>
  ${user.specific_section 
    ? highlightText(
        (() => {
          try {
            const arr = JSON.parse(user.specific_section);
            return Array.isArray(arr) 
              ? arr.join(', ')
              : String(user.specific_section);
          } catch {
            return String(user.specific_section);
          }
        })(),
        currentFilterQuery
      ) 
    : '<i>NONE</i>'
  }
</td>

      <td>
        <button class="btn btn-sm btn-outline-primary btn-update-user" 
                title="Update"
                data-user-id="${user.user_id}" 
                data-id="${user.id}">
          <i class="bi bi-pencil-square" style="font-size:16px"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger btn-delete-user" 
                title="Delete"
                data-user-id="${user.user_id}" 
                data-id="${user.id}">
          <i class="bi bi-trash" style="font-size:16px"></i>
        </button>
      </td>
    `;
            tbody.appendChild(tr);
        });

        bindUpdateButtons(users);
        bindDeleteButtons(users);
    }


    function bindUpdateButtons(users) {

        document.querySelectorAll('.btn-update-user').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const selectedUser = users.find(u => u.user_id === userId);
                if (!selectedUser) return;

                // ✅ Correct modal ID
                const modalEl = document.getElementById('updateUserModal');
                const event = new CustomEvent('openUpdateModal', {
                    detail: selectedUser
                });

                modalEl.dispatchEvent(event);

                // ✅ Show modal
                const updateModal = new bootstrap.Modal(modalEl);
                updateModal.show();
            });
        });
    }



    function bindDeleteButtons(users) {
        document.querySelectorAll('.btn-delete-user').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const id = this.getAttribute('data-id');

                const selectedUser = users.find(u => u.user_id === userId);
                if (!selectedUser) return;

                Swal.fire({
                    title: `Delete ${selectedUser.name}?`,
                    text: "This action cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    customClass: {
                        popup: 'swal-sm' // add your custom CSS class
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        fetch('api/accounts/deleteAccount', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    id: id
                                })
                            })
                            .then(res => res.json())
                            .then(response => {
                                if (response.success) {
                                    showAlert('success', 'Deleted!', response.message || 'Record deleted successfully.');
                                    loadAccounts(); // Refresh table
                                } else {
                                    showAlert('error', 'Failed!', response.message || 'Failed to delete record.');
                                }
                            })
                            .catch(error => {
                                console.error('Delete error:', error);
                                showAlert('error', 'Error!', 'An error occurred while deleting.');
                            });

                    }
                });
            });
        });
    }

    function loadAccounts() {
        fetch('api/accounts/getAccounts')
            .then(res => res.json())
            .then(data => {
                allUsers = data;

                if (!paginator) {
                    paginator = createPaginator({
                        data: allUsers,
                        rowsPerPage: 20,
                        renderPageCallback: (pageData, searchTerm = '') => {
                            renderTable(pageData, searchTerm); // ✅ forward searchTerm
                        },
                        paginationContainerId: 'pagination-controls'
                    });
                    paginator.render();
                } else {
                    paginator.setData(allUsers);
                }

                setupSearchFilter({
                    filterInputSelector: '#filter-input',
                    data: allUsers,
                    searchableFields: ['name', 'role', 'production', 'specific_section', 'user_id'], // ✅ only table columns
                    onFilter: (filteredData, query) => {
                        currentFilterQuery = query;
                        paginator.setData(filteredData);
                        paginator.currentPage = 1;
                        paginator.render();
                    }
                });

            })
            .catch(err => console.error('Error fetching accounts:', err));
    }

    loadAccounts();
    enableTableSorting(".table");
</script>