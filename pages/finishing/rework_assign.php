<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/qrcodeScanner.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
<?php include './components/reusable/reset_timein.php'; ?>
<?php include 'modal/assignOperator.php'; ?>
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
            <li class="breadcrumb-item" aria-current="page">Finishing Section</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="card-title mb-0">Production Instruction Kanban Board(REWORK)</h6>
                        <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
                    </div>


                    <div class="d-flex justify-content-between align-items-center mb-3" style="gap: 10px;">
                        <div class="col-md-3 p-0">
                            <input
                                type="text"
                                id="filter-input"
                                class="form-control"
                                placeholder="Type to filter..." />
                        </div>
                        <div class="d-flex" style="gap: 10px;">
                            <button id="time-in-btn" class="btn btn-primary btn-sm">ASSIGN</button>

                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="custom-hover table">
                            <thead>
                                <tr>
                                    <th style="text-align: center;">Material No</th>
                                    <th style="text-align: center;">Material Description</th>
                                    <th style="text-align: center;">Lot</th>
                                    <th style="text-align: center;">Rework</th>
                                    <th style="text-align: center;">Replace</th>

                                    <th style="text-align: center;">Pending Qty</th>
                                    <th style="text-align: center;">Total Qty</th>
                                    <th style="text-align: center;">Person Incharge</th>
                                    <th style="text-align: center;">Time In | Time Out</th>
                                </tr>
                            </thead>
                            <tbody id="data-body"></tbody>
                        </table>
                    </div>

                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination justify-content-center" id="pagination"></ul>
                    </nav>

                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="inspectionModal" tabindex="-1" aria-labelledby="inspectionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inspectionModalLabel">Inspection Input</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="inspectionForm">
                        <div class="mb-3">
                            <label for="rework" class="form-label">Rework</label>
                            <input type="number" class="form-control" id="rework" required>
                        </div>
                        <div class="mb-3">
                            <label for="replace" class="form-label">Replace</label>
                            <input type="number" class="form-control" id="replace" required>
                        </div>

                        <input type="hidden" id="totalQtyHidden">
                        <input type="hidden" id="recordIdHidden">
                        <div id="errorMsg" class="text-danger"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeInspection()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitInspection()">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 CDN -->
    <script src="assets/js/sweetalert2@11.js"></script>
    <script>
        let url = '';
        let selectedRowData = null;
        let inspectionModal = null;
        let fullDataSet = [];
        let filteredData = [];
        let paginator = null;
        let model = null;
        let rowsPerPage = 10;
        const userRole = "<?= $role ?>";
        const userProduction = <?= json_encode($section) ?>;
        const userProductionLocation = <?= json_encode($specific_section) ?>;

        const tbody = document.getElementById('data-body');
        const filterColumnSelect = document.getElementById('filter-column');
        const filterInput = document.getElementById('filter-input');
        const paginationContainerId = 'pagination';



        function sortByPriority(a, b) {
            const weight = item => {
                if (item.assembly_timein && !item.assembly_timeout) return 2;
                if (!item.assembly_timein) return 1;
                return 0;
            };
            return weight(b) - weight(a);
        }

        let globalSection = null;

        function renderTable(pageData, currentPage) {
            const tbody = document.getElementById('data-body');
            tbody.innerHTML = '';

            pageData.forEach(item => {
                if (item.assembly_timeout != null) return;

                const tr = document.createElement('tr');
                tr.innerHTML = `  
             
        <td style="text-align: center;">${highlightText(item.material_no, currentFilterQuery)}</td>
        <td style="text-align: center;white-space: normal; word-wrap: break-word;">${highlightText(item.material_description, currentFilterQuery)}</td>
        <td style="text-align: center;">${highlightText(item.lot_no, currentFilterQuery)}</td>
           <td style="text-align: center;">${highlightText(item.rework, currentFilterQuery)}</td>
        <td style="text-align: center;">${highlightText(item.replace, currentFilterQuery)}</td>
     
        <td style="text-align: center;">${highlightText(item.assembly_quantity, currentFilterQuery)}</td>
        <td style="text-align: center;">${highlightText(item.quantity, currentFilterQuery)}</td>
        <td style="text-align: center;">${highlightText(item.assembly_person_incharge || 'NONE', currentFilterQuery)}</td>
<td style="text-align: center;">
  <input 
    type="checkbox" 
    class="assign-checkbox"
    style="transform: scale(1.5); cursor: pointer; margin: 4px;"
    data-id="${item.id}"
    data-material_no="${item.material_no}"
    data-material_description="${item.material_description}"
    data-rework="${item.rework}"
    data-replace="${item.replace}"
    data-model="${item.model}"
    data-assembly_section="${item.assembly_section}"
    data-user_production_location="${userProductionLocation}"
    data-user_role="${userRole}"
    data-user_production="${userProduction}"
    data-section="finishing"
  >
</td>

      `;
                tbody.appendChild(tr);
            });

            document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
        }
        // Fetch data for the selected model
        function getData(model) {
            fetch(`api/finishing/getData_toassign?model=${encodeURIComponent(model)}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data)
                    fullDataSet = data.items ?? []; // ✅ use items property
                    filteredData = [...fullDataSet];
                    paginator = createPaginator({
                        data: filteredData,
                        rowsPerPage,
                        renderPageCallback: renderTable,
                        paginationContainerId,
                        defaultSortFn: sortByPriority
                    });

                    paginator.render();

                    // ✅ Reusable search/filter only on visible table columns
                    const searchableFields = [
                        'material_no',
                        'material_description',
                        'model',
                        'lot_no',
                        'replace',
                        'rework',
                        'assembly_quantity',
                        'quantity',
                        'assembly_person_incharge',
                        'date_needed',
                        'shift'
                    ];

                    setupSearchFilter({
                        filterColumnSelector: '#filter-column',
                        filterInputSelector: '#filter-input',
                        data: fullDataSet,
                        searchableFields,
                        customValueResolver: (item, column) => item[column] ?? '',
                        onFilter: (filtered, query) => {
                            currentFilterQuery = query || '';
                            filteredData = filtered;
                            paginator.setData(filteredData);
                        }
                    });
                })
                .catch(error => console.error('Fetch error:', error));
        }
        document.getElementById('time-in-btn').addEventListener('click', () => {
            const checked = document.querySelectorAll('.assign-checkbox:checked');
            if (checked.length === 0) {
                alert('Please select at least one item to assign.');
                return;
            }

            const selectedItems = [...checked].map(cb => ({
                id: cb.dataset.id,
                material_no: cb.dataset.material_no,
                material_description: cb.dataset.material_description,

                sub_component: cb.dataset.rework ?? '',
                assembly_process: cb.dataset.replace ?? '',

                model: cb.dataset.model,
                assembly_section: cb.dataset.assembly_section,
                user_production_location: cb.dataset.user_production_location,
                user_role: cb.dataset.user_role,
                user_production: cb.dataset.user_production,
                section: cb.dataset.section
            }));

            getTasks(selectedItems);
        });



        function closeInspection() {
            inspectionModal.hide()
        }



        enableTableSorting(".table");
    </script>

    <style>
        /* Hover effect */
        .custom-hover tbody tr:hover {
            background-color: #dde0e2;
        }

        /* Tablet only: horizontal scroll and column widths */
        @media (max-width: 991.98px) {
            .table-wrapper {
                overflow-x: auto;
                width: 100%;
            }

            .custom-hover tbody tr:hover td,
            .custom-hover tbody tr:hover th {
                background-color: #dde0e2;
            }

            .custom-hover {
                width: 1200px;
                table-layout: fixed;
            }

            .custom-hover th,
            .custom-hover td {
                white-space: nowrap;
                font-size: 0.85rem;
                padding: 6px 8px;
            }

            .custom-hover th:nth-child(1),
            .custom-hover td:nth-child(1) {
                position: sticky;
                left: 0;
                background: #ffffff;
                z-index: 10;
                width: 100px;
            }

            /* Sticky second column */
            .custom-hover th:nth-child(2),
            .custom-hover td:nth-child(2) {
                position: sticky;
                left: 100px;
                /* must match first column width */
                background: #ffffff;
                z-index: 10;
                width: 200px;
            }

            .custom-hover th:nth-child(3),
            .custom-hover td:nth-child(3) {
                width: 80px;
            }

            .custom-hover th:nth-child(8),
            .custom-hover td:nth-child(8) {
                width: 180px;
            }
        }

        /* Mobile adjustments */
        @media (max-width: 576px) {

            .custom-hover th,
            .custom-hover td {
                font-size: 0.75rem;
                padding: 4px 6px;
            }
        }
    </style>