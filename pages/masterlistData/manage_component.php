<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
<?php include 'modal/addComponent.php'; ?>
<?php include 'modal/viewComponent.php'; ?>
<?php include 'modal/editComponent.php'; ?>
<style>
    .custom-hover tbody tr:hover {
        background-color: #dde0e2ff !important;
        /* light blue */
    }
</style>
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Pages</a></li>
            <li class="breadcrumb-item" aria-current="page">Assembly Section</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <!-- Header with title and last-updated -->
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-2">
                        <h6 class="card-title mb-2 mb-md-0">SKU Information</h6>
                        <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
                    </div>

                    <!-- Filter and Add button -->
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between w-100 mb-2 gap-2">
                        <input
                            type="text"
                            id="filter-input"
                            class="form-control form-control-sm"
                            style="max-width: 300px;"
                            placeholder="Type to filter..." />

                        <button class="btn btn-success btn-sm add-btn">Add New Component</button>
                    </div>

                    <!-- Responsive scrollable table -->
                    <div class="table-responsive">
                        <table class="table table-hover" style="table-layout: fixed; width: 100%;">
                            <thead>
                                <tr>
                                    <th style="width:10%; text-align:center;">Material No</th>
                                    <th style="width:20%; text-align:center;">Component Name</th>
                                    <th style="width:10%; text-align:center;">Quantity</th>
                                    <th style="width:5%; text-align:center;">Usage</th>
                                    <th style="width:15%; text-align:center;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="data-body"></tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div id="pagination" class="mt-3 d-flex justify-content-center"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const dataBody = document.getElementById('data-body');
        const filterInput = document.getElementById('filter-input');
        const paginationContainerId = 'pagination';
        const userRole = "<?= $role ?>";
        const userProduction = <?= json_encode($section) ?>;
        const userProductionLocation = <?= json_encode($specific_section) ?>;

        let paginator = null;
        let componentsData = [];
        document.addEventListener('DOMContentLoaded', () => {
            const addBtn = document.querySelector('.add-btn');
            if (addBtn) {
                addBtn.addEventListener('click', () => {
                    // Clear previous form values (optional)
                    document.getElementById('addComponentForm')?.reset();

                    // Reset any dynamic rows if you have them
                    const tbody = document.querySelector('#addStageTable tbody');
                    if (tbody) tbody.innerHTML = '';

                    // Show the modal
                    $('#addComponentModal').modal('show');
                });
            }

            // Optional: Close modal
            document.querySelectorAll('.closeAddComponentBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    $('#addComponentModal').modal('hide');
                });
            });
        });

        function getData(model) {
            fetch(`api/masterlist/getComponentData?model=${encodeURIComponent(model)}`)
                .then(res => res.json())
                .then(data => {
                    componentsData = data.map(item => ({
                        ...item,
                    }));

                    paginator = createPaginator({
                        data: componentsData,
                        rowsPerPage: 10,
                        paginationContainerId,
                        renderPageCallback: renderTable
                    });

                    paginator.render();

                    setupSearchFilter({
                        filterInputSelector: '#filter-input',
                        data: componentsData,
                        searchableFields: [
                            'material_no',
                            'components_name',
                            'parsed_stages',
                            'actual_inventory'
                        ],
                        onFilter: (filtered, query) => paginator.setData(filtered)
                    });
                })
                .catch(err => {
                    console.error('Error fetching component data:', err);
                    showAlert('error', 'Error', 'Failed to load component data.');
                });
        }



        // Render table
        function renderTable(data) {
            dataBody.innerHTML = '';
            const query = filterInput.value.toLowerCase();
            const isAdmin = userRole.toLowerCase() === "administrator";
            const disabledAttr = isAdmin ? "" : "disabled";
            data.forEach(item => {
                const itemJson = encodeURIComponent(JSON.stringify(item));

                const row = document.createElement('tr');
                row.innerHTML = `
      <td style="text-align:center;">${highlightText(item.material_no, query)}</td>
      <td style="text-align:center;white-space:normal;word-wrap:break-word;">
        ${highlightText(item.components_name, query)}
      </td>
      <td style="text-align:center;">${item.actual_inventory ?? 0}</td>
      <td style="text-align:center;">${item.usage_type ?? 0}</td>
      <td style="text-align:center;">
        <button class="btn btn-sm btn-info view-btn" data-item='${itemJson}'>View</button>
        <button class="btn btn-sm btn-primary edit-btn" data-item='${itemJson}' ${disabledAttr}>Edit</button>
        <button class="btn btn-sm btn-danger delete-btn" data-item='${itemJson}' ${disabledAttr}>Delete</button>
      </td>
    `;
                dataBody.appendChild(row);
            });
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', e => {
                    const item = JSON.parse(decodeURIComponent(btn.dataset.item));
                    window.openViewComponent(item);
                });
            });
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', e => {
                    const item = JSON.parse(decodeURIComponent(btn.dataset.item));
                    window.openEditComponent(item);
                });
            });
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', async e => {
                    const item = JSON.parse(decodeURIComponent(btn.dataset.item));

                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Do you want to delete component "${item.components_name}"?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                            try {
                                const res = await fetch('api/masterlist/deleteComponent', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        id: item.id
                                    }) // assuming your DB has id field
                                });
                                const response = await res.json();

                                if (response.success) {
                                    Swal.fire('Deleted!', 'Component has been deleted.', 'success');
                                    // Optionally, remove row from table
                                    btn.closest('tr').remove();
                                } else {
                                    Swal.fire('Failed', response.message || 'Failed to delete.', 'error');
                                }
                            } catch (err) {
                                console.error('Delete error:', err);
                                Swal.fire('Error', 'An error occurred. Check console.', 'error');
                            }
                        }
                    });
                });
            });

            feather.replace();
            document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {

            const userRole = "<?= $role ?>"; // your PHP role
            const addBtn = document.querySelector(".add-btn");

            if (addBtn && userRole.toLowerCase() !== "administrator") {
                addBtn.disabled = true;
                addBtn.title = "Only administrators can add new SKUs";
            }
            if (addBtn) {
                addBtn.addEventListener('click', () => {
                    if (window.openAddModal) window.openAddModal();
                    else console.warn('openAddModal not found');
                });
            }

        });
    </script>

    <style>
        /* Hover effect */
        .custom-hover tbody tr:hover {
            background-color: #dde0e2ff !important;
        }

        /* Responsive tweaks */
        @media (max-width: 991.98px) {

            /* Tablet */
            .custom-hover th,
            .custom-hover td {
                white-space: normal;
                /* allow wrapping */
                font-size: 0.85rem;
                padding: 6px 8px;
            }
        }

        @media (max-width: 576px) {

            /* Mobile */
            .custom-hover th,
            .custom-hover td {
                font-size: 0.75rem;
                padding: 4px 6px;
            }
        }
    </style>