<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
<?php include 'modal/addRM.php'; ?>
<?php include 'modal/editRM.php'; ?>

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
            <li class="breadcrumb-item" aria-current="page">RM Warehouse</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <!-- Header -->
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-2">
                        <h6 class="card-title mb-2 mb-md-0">Components and Raw Material Inventory</h6>
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

                        <button class="btn btn-success btn-sm add-btn">Add Raw Material</button>
                    </div>

                    <!-- Responsive scrollable table -->
                    <div class="table-responsive">
                        <table class="table table-hover" style="table-layout: fixed; width: 100%;">
                            <thead>
                                <tr>
                                    <th style="width:5%; text-align:center;">Material No</th>
                                    <th style="width:10%; text-align:center;">Component Name</th>
                                    <th style="width:5%; text-align:center;white-space: normal; word-wrap: break-word;">Component Usage</th>
                                    <th style="width:5%; text-align:center;">Raw Mat Usage</th>
                                    <th style="width:10%; text-align:center;">Raw Material</th>
                                    <th style="width:5%; text-align:center;">Status</th>
                                    <!-- <th style="width:15%; text-align:center;">Action</th> -->
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

        let componentsData = [];
        let paginator = null;
        document.querySelectorAll('.add-btn').forEach(btn => {
            btn.onclick = () => {
                const item = JSON.parse(decodeURIComponent(btn.dataset.item));
                openAddRM(item);
            };
        });
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

        function getData(model) {
            fetch(`api/masterlist/getRMData?model=${encodeURIComponent(model)}`)
                .then(res => res.json())
                .then(data => {
                    componentsData = Array.isArray(data) ? data : [];

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
                            'rm_material_no',
                            'rm_component_name',
                            'comp_material_no',
                            'comp_component_name',
                            'match_status'
                        ],
                        onFilter: filtered => paginator.setData(filtered)
                    });
                });
        }

        function renderTable(data) {
            dataBody.innerHTML = '';
            const query = filterInput.value.toLowerCase();
            const isAdmin = userRole.toLowerCase() === "administrator";
            const disabledAttr = isAdmin ? "" : "disabled";
            data.forEach(item => {
                const row = document.createElement('tr');
                const displayMaterialNo = item.rm_material_no ?? item.comp_material_no ?? '-';
                const displayComponentName = item.rm_component_name ?? item.comp_component_name ?? '-';
                const displayUsage = item.rm_usage ?? '-';
                const displayUsageType = item.comp_usage_type ?? '-';
                const itemJson = encodeURIComponent(JSON.stringify(item));

                // Only show "Add" if missing in components
                const addButtonHtml = item.match_status === 'Missing in Components' ?
                    `<button class="btn btn-sm btn-success add-btn" data-item='${itemJson}'>Add</button>` :
                    '';

                row.innerHTML = `
            <td style="text-align:center;">${highlightText(displayMaterialNo, query)}</td>
            <td style="text-align:center;white-space: normal; word-wrap: break-word;">${highlightText(displayComponentName, query)}</td>
            <td style="text-align:center;">${highlightText(displayUsageType, query)}</td>
            <td style="text-align:center;">${highlightText(displayUsage, query)}</td>
            <td style="text-align:center; font-weight:bold;white-space: normal; word-wrap: break-word;">${highlightText(item.rm_material_description ?? '-', query)}</td>
            <!--<td style="text-align:center; font-weight:bold;">${highlightText(item.match_status ?? '-', query)}</td>-->
            <td style="text-align:center;">

                <button class="btn btn-sm btn-primary edit-btn" data-item='${itemJson}' ${disabledAttr}>Edit</button>
                <button class="btn btn-sm btn-danger delete-btn" data-item='${itemJson}' ${disabledAttr}>Delete</button>
            </td>
         
        `;

                dataBody.appendChild(row);
            });

            // Listen on the table body itself
            dataBody.addEventListener('click', (e) => {
                const target = e.target;

                // Edit button
                if (target.classList.contains('edit-btn')) {
                    const item = JSON.parse(decodeURIComponent(target.dataset.item));
                    openEditRM(item);
                }

                // Delete button
                if (target.classList.contains('delete-btn')) {
                    const item = JSON.parse(decodeURIComponent(target.dataset.item));
                    deleteRM(item);
                }

                // Add button (if you have add-btn)
                if (target.classList.contains('add-btn')) {
                    const item = JSON.parse(decodeURIComponent(target.dataset.item));
                    openAddRM(item);
                }
            });

            document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
        }

        function deleteRM(item) {
            console.log('Attempting to delete RM:', item);
            Swal.fire({
                title: `Delete ${item.rm_material_no}?`,
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api/masterlist/deleteRM', {
                            method: 'POST', // or DELETE if your API supports it
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: item.rm_id
                            })
                        })
                        .then(res => res.json())
                        .then(response => {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'Raw material has been deleted.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire('Failed', response.message || 'Failed to delete.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Error deleting RM:', err);
                            Swal.fire('Error', 'Something went wrong. Check console.', 'error');
                        });
                }
            });
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            const addBtn = document.querySelector('.add-btn');
            if (addBtn) {
                addBtn.onclick = () => window.openAddRM();
            }

            getData('default'); // Or pass selected model
        });

        enableTableSorting(".table");
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