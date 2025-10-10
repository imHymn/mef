<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
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
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm add-btn">Add New Component</button>
                        </div>
                    </div>
                    <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width:15%; text-align:center;">Material No</th>
                                <th style="width:20%; text-align:center;">Component Name</th>
                                <th style="width:10%; text-align:center;">Inventory</th>
                                <th style="width:15%; text-align:center;">Action</th>
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
        const filterInput = document.getElementById('filter-input');
        const paginationContainerId = 'pagination';
        const userRole = "<?= $role ?>";
        const userProduction = <?= json_encode($section) ?>;
        const userProductionLocation = <?= json_encode($specific_section) ?>;

        let paginator = null;
        let componentsData = [];

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

            data.forEach(item => {
                const itemJson = encodeURIComponent(JSON.stringify(item));

                const row = document.createElement('tr');
                row.innerHTML = `
      <td style="text-align:center;">${highlightText(item.material_no, query)}</td>
      <td style="text-align:center;white-space:normal;word-wrap:break-word;">
        ${highlightText(item.components_name, query)}
      </td>
      <td style="text-align:center;">${item.actual_inventory ?? 0}</td>
  
      <td style="text-align:center;">
        <button class="btn btn-sm btn-info view-btn" data-item='${itemJson}'>View</button>
        <button class="btn btn-sm btn-primary edit-btn" data-item='${itemJson}'>Edit</button>
        <button class="btn btn-sm btn-danger delete-btn" data-item='${itemJson}'>Delete</button>
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

            feather.replace();
            document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            const addSkuBtn = document.querySelector('.add-btn');
            if (addSkuBtn) {
                addSkuBtn.addEventListener('click', () => {
                    if (window.openAddModal) window.openAddModal();
                    else console.warn('openAddModal not found');
                });
            }

            // Call getData() for initial load (or pass model dynamically)
            getData('default');
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