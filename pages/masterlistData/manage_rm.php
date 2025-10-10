<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>

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
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="card-title mb-0">Components and Raw Material Inventory</h6>
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
                            <button class="btn btn-success btn-sm add-btn">Add New RM</button>
                        </div>
                    </div>

                    <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 10%; text-align: center;">Material No</th>
                                <th style="width: 15%; text-align: center;">Component Name</th>
                                <th style="width: 7%; text-align: center;">Component Usage</th>
                                <th style="width: 5%; text-align: center;">Raw Mat Usage</th>
                                <th style="width: 15%; text-align: center;">Raw Material</th>
                                <th style="width: 5%; text-align: center;">Status</th>
                                <th style="width: 15%; text-align: center;">Action</th>
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
        let componentsData = [];
        let paginator = null;

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

            data.forEach(item => {
                const row = document.createElement('tr');
                const displayMaterialNo = item.rm_material_no ?? item.comp_material_no ?? '-';
                const displayComponentName = item.rm_component_name ?? item.comp_component_name ?? '-';
                const displayUsage = item.rm_usage ?? '-';
                const displayUsageType = item.comp_usage_type ?? '-';
                const itemJson = encodeURIComponent(JSON.stringify(item));

                row.innerHTML = `
            <td style="text-align:center;">${highlightText(displayMaterialNo, query)}</td>
            <td style="text-align:center;white-space: normal; word-wrap: break-word;">${highlightText(displayComponentName, query)}</td>
            <td style="text-align:center;">${highlightText(displayUsageType, query)}</td>
            <td style="text-align:center;">${highlightText(displayUsage, query)}</td>
            <td style="text-align:center; font-weight:bold;">${highlightText(item.rm_material_description ?? '-', query)}</td>
            <td style="text-align:center; font-weight:bold;">${highlightText(item.match_status ?? '-', query)}</td>
            <td style="text-align:center;">
                <button class="btn btn-sm btn-info view-btn" data-item='${itemJson}'>View</button>
                <button class="btn btn-sm btn-primary edit-btn" data-item='${itemJson}'>Edit</button>
                <button class="btn btn-sm btn-danger delete-btn" data-item='${itemJson}'>Delete</button>
            </td>
        `;

                dataBody.appendChild(row);
            });

            // Attach event listeners for buttons
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.onclick = () => window.openViewRM(JSON.parse(decodeURIComponent(btn.dataset.item)));
            });
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.onclick = () => window.openEditRM(JSON.parse(decodeURIComponent(btn.dataset.item)));
            });
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.onclick = () => window.deleteRM(JSON.parse(decodeURIComponent(btn.dataset.item)));
            });

            document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
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