<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
<?php include 'modal/viewSKU.php'; ?>
<?php include 'modal/editSKU.php'; ?>
<?php include 'modal/addSKU.php'; ?>

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
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="card-title mb-0">SKU Information</h6>
                        <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
                    </div>

                    <div class="d-flex align-items-center justify-content-between w-100 mb-2">
                        <input
                            type="text"
                            id="filter-input"
                            class="form-control form-control-sm me-2"
                            style="max-width: 300px;"
                            placeholder="Type to filter..." />

                        <!-- Inline Edit button on the right -->
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm add-btn">Add New SKU</button>
                        </div>

                    </div>




                    <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 15%; text-align: center;">Material No <span class="sort-icon"></span></th>
                                <th style="width: 25%; text-align: center;">Material Name <span class="sort-icon"></span></th>
                                <th style="width: 10%; text-align: center;">Quantity<span class="sort-icon"></span></th>
                                <th style="width: 10%; text-align: center;">Action<span class="sort-icon"></span></th>
                            </tr>
                        </thead>
                        <tbody id="data-body"></tbody>
                    </table>


                    <div id="pagination" class="mt-3 d-flex justify-content-center"></div>




                </div>
            </div>
        </div>
    </div>
    <script>
        const dataBody = document.getElementById('data-body');
        const filterColumn = document.getElementById('filter-column');
        const filterInput = document.getElementById('filter-input');
        const paginationContainerId = 'pagination';
        const userRole = "<?= $role ?>";
        const userProduction = <?= json_encode($section) ?>;
        const userProductionLocation = <?= json_encode($specific_section) ?>;

        let paginator = null;
        let model = null;
        let componentsData = []; // global flattened data

        document.addEventListener('DOMContentLoaded', () => {
            const addSkuBtn = document.querySelector('.add-btn');
            if (addSkuBtn) {
                addSkuBtn.addEventListener('click', () => {
                    if (window.openAddModal) window.openAddModal();
                    else console.warn('openAddModal not found');
                });
            }
        });


        function getData(model) {
            fetch(`api/masterlist/getSKUData?model=${encodeURIComponent(model)}`)
                .then(res => res.json())
                .then(data => {
                    componentsData = data; // flatten for pagination

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
                            'material_description',
                            'sub_component',
                            'assembly_section',
                            'assembly_process',
                            'assembly_processtime',
                            'manpower'
                        ],
                        onFilter: (filtered, query) => {
                            paginator.setData(filtered);
                        }
                    });
                })
                .catch(err => {
                    console.error('Error fetching SKU processes:', err);
                    showAlert('error', 'Error', 'Failed to load SKU processes.');
                });
        }

        function renderTable(data) {
            dataBody.innerHTML = '';
            const query = filterInput.value.toLowerCase();

            data.forEach(item => {
                const row = document.createElement('tr');
                const itemJson = encodeURIComponent(JSON.stringify(item));

                row.innerHTML = `
            <td style="text-align: center;">${highlightText(item.material_no, query)}</td>
            <td style="text-align: center;white-space: normal; word-wrap: break-word;">${highlightText(item.material_description, query)}</td>
            <td style="text-align: center;white-space: normal; word-wrap: break-word;">${highlightText(item.quantity, query)}</td>
           <td style="text-align: center;">
     <button class="btn btn-sm btn-info view-btn" data-item='${itemJson}'>
        View
    </button>
    <button class="btn btn-sm btn-primary edit-btn" data-item='${itemJson}'>
        Edit
    </button>
   
    <button class="btn btn-sm btn-danger delete-btn" data-item='${itemJson}'>
        Delete
    </button>
</td>

        `;

                dataBody.appendChild(row);
            });
            feather.replace();
            // attach once, works across renders
            dataBody.addEventListener('click', e => {
                const btn = e.target.closest('button');
                if (!btn) return;
                const raw = btn.getAttribute('data-item');
                if (!raw) return;
                let selectedItem;
                try {
                    selectedItem = JSON.parse(decodeURIComponent(raw));
                } catch (err) {
                    console.error('data-item parse error', err);
                    return;
                }

                if (btn.classList.contains('view-btn')) {
                    if (window.openViewModal) window.openViewModal(selectedItem);
                } else if (btn.classList.contains('edit-btn')) {
                    if (window.openEditModal) window.openEditModal(selectedItem);
                    else console.warn('openEditModal not found on window');
                } else if (btn.classList.contains('delete-btn')) {
                    // handle delete
                }
            });

            document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
        }


        filterColumn.addEventListener('change', () => {
            filterInput.value = '';
            filterInput.disabled = !filterColumn.value;
            if (!filterColumn.value) {
                paginator.setData(componentsData);
            }
        });

        // Filter input
        filterInput.addEventListener('input', () => {
            const column = filterColumn.value;
            const filterText = filterInput.value.trim().toLowerCase();

            if (!column) return;

            const filtered = componentsData.filter(item => {
                const value = item[column];
                return value && value.toString().toLowerCase().includes(filterText);
            });

            paginator.setData(filtered);
        });

        // Enable sorting on table
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