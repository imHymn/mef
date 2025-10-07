<?php include './components/reusable/tablesorting.php'; ?>

<?php include './components/reusable/qrcodeScanner.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<?php include './components/reusable/reset_timein.php'; ?>
<?php include 'modal/assignOperator.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
<!-- <style>
  /* CSS for hover effect on specific rows */
  tr.hoverable-row:hover {
    background-color: #ddd9d9ff;
    cursor: pointer;
  }
</style> -->
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
                        <h6 class="card-title mb-0">Production Instruction Kanban Board (SKU)</h6>
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

                    <div class="table-responsive-wrapper">
                        <div class="table-responsive">
                            <table class="custom-hover table" style="table-layout: fixed; min-width: 1200px; width: 100%;">
                                <thead>
                                    <tr>

                                        <th class="sticky-col col-2" style="width: 10%; text-align: center;">Material No <span class="sort-icon"></span></th>
                                        <th class="sticky-col col-3" style="width: 15%; text-align: center; white-space: normal; word-wrap: break-word;">Material Description <span class="sort-icon"></span></th>
                                        <th style="width: 10%; text-align: center; white-space: normal; word-wrap: break-word;">Sub Component <span class="sort-icon"></span></th>
                                        <th style="width: 10%; text-align: center;">Process <span class="sort-icon"></span></th>
                                        <th style="width: 5%; text-align: center;">Lot <span class="sort-icon"></span></th>
                                        <th style="width: 5%; text-align: center;">Total Qty <span class="sort-icon"></span></th>

                                        <th style="width: 5%; text-align: center; white-space: normal; word-wrap: break-word;">Action<span class="sort-icon"></span></th>
                                    </tr>
                                </thead>
                                <tbody id="data-body"></tbody>
                            </table>
                        </div>
                    </div>


                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination justify-content-center" id="pagination"></ul>
                    </nav>


                </div>
            </div>
        </div>
        <!-- Drawer -->


    </div>



    <div class="modal fade" id="quantityModal" tabindex="-1" aria-labelledby="quantityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="quantityForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quantityModalLabel">Enter Quantity</h5>

                </div>
                <div class="modal-body">
                    <input
                        type="number"
                        class="form-control"
                        id="quantityInput"
                        name="quantity"
                        min="1"
                        placeholder="Enter quantity"
                        required />
                    <div class="invalid-feedback">
                        Please enter a valid quantity (1 or more).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelQuantityBtn">Cancel</button>

                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>


    <script src="assets/js/sweetalert2@11.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.min.js"></script>
    <link rel="stylesheet" href="assets/css/choices.min.css" />
    <script src="assets/js/choices.min.js"></script>

    <script>
        let assemblyData = [];
        let currentMaterialId = null;
        let currentItem = null;
        let currentMode = null;
        let timeout_id = null;
        let quantityModal;
        let currentPage = 1;
        const rowsPerPage = 10;
        let originalPageData = null;
        let paginatedData = [];
        let paginator = null;
        const userRole = "<?= $role ?>";
        const userProduction = <?= json_encode($section) ?>;
        const userProductionLocation = <?= json_encode($specific_section) ?>;

        quantityModal = new bootstrap.Modal(document.getElementById('quantityModal'));
        let globalSection = null;

        let model = null;

        function getData(model) {

            fetch('api/assembly/getData_toassign?model=' + encodeURIComponent(model || 'L300 DIRECT') + '&_=' + new Date().getTime())
                .then(response => response.json())
                .then(data => {
                    deliveryData = data.delivery;
                    assemblyData = data.assembly;
                    let filteredDeliveryData = deliveryData
                        .filter(item => item.section === 'DELIVERY' || item.section === 'ASSEMBLY')
                        .filter(item => item.process !== 'import');
                    filteredDeliveryData = filteredDeliveryData.filter(item => {
                        const pending = parseInt(item.assembly_pending ?? item.total_quantity) || 0;
                        if (item.status === "done") return false;

                        // Normalize section
                        const section = (item.assembly_section ?? '').toLowerCase().replace(/[- ]/g, '');

                        // Normalize userProductionLocation
                        let rawUserLoc = userProductionLocation;
                        const userLoc = (Array.isArray(rawUserLoc) ? rawUserLoc : [rawUserLoc])
                            .map(loc => (loc ?? '').toLowerCase().replace(/[- ]/g, ''));

                        // Normalize userProduction
                        let rawUserProd = userProduction;
                        const userProd = (Array.isArray(rawUserProd) ? rawUserProd : [rawUserProd])
                            .map(p => (p ?? '').toLowerCase().replace(/[- ]/g, ''));

                        if (userRole === 'administrator') return true;

                        if (userRole === 'supervisor' || userRole === 'line leader') {
                            // For finishing and painting → check production
                            if (section === 'finishing' || section === 'painting') {
                                return userProd.includes(section);
                            }
                            // For all other sections → check specific_section
                            return userLoc.includes(section);
                        }

                        return true;
                    });

                    const sortFn = (a, b) => {
                        const aPending = parseInt(a.assembly_pending ?? 0) > 0 ? 1 : 0;
                        const bPending = parseInt(b.assembly_pending ?? 0) > 0 ? 1 : 0;

                        if (aPending !== bPending) return bPending - aPending;

                        const aAssembly = assemblyData.find(x => String(x.itemID) === String(a.id));
                        const bAssembly = assemblyData.find(x => String(x.itemID) === String(b.id));

                        const aCanTimeout = aAssembly && aAssembly.time_in && !aAssembly.time_out ? 1 : 0;
                        const bCanTimeout = bAssembly && bAssembly.time_in && !bAssembly.time_out ? 1 : 0;
                        if (aCanTimeout !== bCanTimeout) return bCanTimeout - aCanTimeout;

                        const aInProgress = aAssembly && !!aAssembly.time_in ? 1 : 0;
                        const bInProgress = bAssembly && !!bAssembly.time_in ? 1 : 0;
                        if (aInProgress !== bInProgress) return bInProgress - aInProgress;

                        const aContinue = a.status?.toLowerCase() === 'continue' ? 1 : 0;
                        const bContinue = b.status?.toLowerCase() === 'continue' ? 1 : 0;
                        if (aContinue !== bContinue) return bContinue - aContinue;

                        const dateA = new Date(a.date_needed);
                        const dateB = new Date(b.date_needed);
                        if (dateA.getTime() !== dateB.getTime()) return dateA - dateB;

                        const lotA = parseInt(a.lot_no) || 0;
                        const lotB = parseInt(b.lot_no) || 0;
                        return lotA - lotB;
                    };


                    filteredDeliveryData.sort(sortFn);

                    lotGroups = {};
                    filteredDeliveryData.forEach(item => {
                        let lotKey = item.lot_no; // Prefer lot number first

                        if (!lotKey) {
                            // Only use created_at if no lot number
                            if (item.created_at) {
                                lotKey = new Date(item.created_at).toISOString(); // Use full datetime for grouping
                            } else {
                                lotKey = 'NO_LOT';
                            }
                        }

                        if (!lotGroups[lotKey]) lotGroups[lotKey] = [];
                        lotGroups[lotKey].push(item);
                    });

                    lotPages = Object.values(lotGroups);
                    currentPage = 0;

                    renderPaginationControls();
                    renderPaginatedTable(lotPages[currentPage]);

                    const searchableFields = [
                        'material_no',
                        'material_description',
                        'model',
                        'assembly_section',
                        'lot_no',
                        'person_incharge',
                        'date_needed',
                        'shift',
                        'assembly_process',
                        'sub_component',

                    ];

                    setupSearchFilter({
                        filterInputSelector: '#filter-input',
                        data: filteredDeliveryData.flat(), // or keep as a single array
                        searchableFields,
                        onFilter: (filtered, query) => {
                            currentFilterQuery = query; // update global query for highlighting
                            renderPaginatedTable(filtered);
                        }
                    });


                });

        }

        function renderPaginatedTable(pageData) {
            const tbody = document.getElementById('data-body');
            tbody.innerHTML = '';

            originalPageData = [...pageData];

            pageData.forEach(item => {
                if (item.assembly_section === 'PAINTING' || item.assembly_section === 'FINISHING') return; // only show assembly section
                const assemblyRecord = assemblyData.find(a => String(a.itemID) === String(item.id));
                const personInCharge = assemblyRecord?.person_incharge || '<i>NONE</i>';
                const currentRef = item.reference_no;
                const currentSection = item.assembly_section?.trim() || 'NONE';

                let timeStatus = '';


                const row = document.createElement('tr');
                row.classList.add('hoverable-row');

                row.innerHTML = `

  <td style="text-align: center;" class="sticky-col col-2">
      ${highlightText(item.material_no + (item.fuel_type ? ` (${item.fuel_type})` : ""), currentFilterQuery)}
  </td>
  <td style="text-align: center; white-space: normal; word-wrap: break-word;" class="sticky-col col-3">
      ${highlightText(item.material_description, currentFilterQuery)}
  </td>
  <td style="text-align: center; white-space: normal; word-wrap: break-word;">
      ${highlightText(item.sub_component && item.sub_component.replace(/"/g, '') !== item.material_description
          ? item.sub_component.replace(/"/g, '')
          : '<i>NONE</i>', currentFilterQuery)}
  </td>
  <td style="text-align: center; white-space: normal; word-wrap: break-word;">
      ${highlightText(item.assembly_process ? item.assembly_process.replace(/"/g, '') : '<i>NONE</i>', currentFilterQuery)}
  </td>
  <td style="text-align: center;">
      ${highlightText(item.lot_no ? `${item.variant} - ${item.lot_no}` : '<i>NONE</i>', currentFilterQuery)}
  </td>
  <td style="text-align: center;">
    ${
      item.assembly_pending
        ? highlightText(item.assembly_pending, currentFilterQuery)
        : highlightText(item.total_quantity, currentFilterQuery)
    }
  </td>
<td style="text-align: center;">
  <input 
    type="checkbox" 
    class="assign-checkbox"
    style="transform: scale(1.5); cursor: pointer; margin: 4px;"
    data-id="${item.id}"
    data-material_no="${item.material_no}"
    data-material_description="${item.material_description}"
    data-sub_component="${item.sub_component}"
    data-assembly_process="${item.assembly_process}"
    data-model="${item.model}"
    data-assembly_section="${item.assembly_section}"
    data-type="sku"
  >
</td>


`;

                tbody.appendChild(row);
            });

            document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
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
                sub_component: cb.dataset.sub_component,
                assembly_process: cb.dataset.assembly_process,
                model: cb.dataset.model,
                assembly_section: cb.dataset.assembly_section,
                type: cb.dataset.type,
            }));

            getTasks(selectedItems);
        });



        document.getElementById('cancelQuantityBtn').addEventListener('click', () => {
            quantityModal.hide();
        });

        const filterColumnSelect = document.getElementById('filter-column');
        const filterInput = document.getElementById('filter-input');
        const tbody = document.getElementById('data-body');

        function renderPaginationControls() {
            const container = document.getElementById('pagination');
            container.innerHTML = '';

            const totalPages = lotPages.length;
            const visibleCount = 3;
            let start = Math.max(0, currentPage - Math.floor(visibleCount / 2));
            let end = start + visibleCount;

            if (end > totalPages) {
                end = totalPages;
                start = Math.max(0, end - visibleCount);
            }

            const prevBtn = document.createElement('button');
            prevBtn.textContent = 'Previous';
            prevBtn.className = 'btn btn-sm btn-secondary mx-1';
            prevBtn.disabled = currentPage === 0;
            prevBtn.onclick = () => {
                if (currentPage > 0) {
                    currentPage--;
                    renderPaginatedTable(lotPages[currentPage]);
                    renderPaginationControls();
                }
            };
            container.appendChild(prevBtn);

            for (let i = start; i < end; i++) {
                const lotNo = Object.keys(lotGroups)[i];
                const btn = document.createElement('button');
                const lotKey = Object.keys(lotGroups)[i];

                let displayLot = lotKey;
                if (!lotGroups[lotKey][0].lot_no && !isNaN(Date.parse(lotKey))) {
                    displayLot = new Date(lotKey).toISOString().split('T')[0]; // YYYY-MM-DD
                }

                btn.textContent = `${displayLot}`;

                btn.className = 'btn btn-sm mx-1 ' + (i === currentPage ? 'btn-primary' : 'btn-outline-primary');
                btn.onclick = () => {
                    currentPage = i;
                    renderPaginatedTable(lotPages[currentPage]);
                    renderPaginationControls();
                };
                container.appendChild(btn);
            }

            const nextBtn = document.createElement('button');
            nextBtn.textContent = 'Next';
            nextBtn.className = 'btn btn-sm btn-secondary mx-1';
            nextBtn.disabled = currentPage >= totalPages - 1;
            nextBtn.onclick = () => {
                if (currentPage < totalPages - 1) {
                    currentPage++;
                    renderPaginatedTable(lotPages[currentPage]);
                    renderPaginationControls();
                }
            };
            container.appendChild(nextBtn);
        }




        enableTableSorting(".table");
    </script>
    <style>
        /* Table responsiveness wrapper */
        .table-responsive-wrapper {
            overflow-x: auto;
        }

        /* Sticky columns only for tablets (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991.98px) {

            .custom-hover tbody tr:hover .col-1,
            .custom-hover tbody tr:hover .col-2,
            .custom-hover tbody tr:hover .col-3 {
                background-color: #dde0e2 !important;
            }


            /* First column */
            .col-1 {
                position: sticky;
                left: 0;
                background-color: #fff;
                z-index: 10;
                width: 5% !important;

            }

            /* Second column */
            .col-2 {
                position: sticky;
                left: 55px;
                /* width of col-1 */
                background-color: #fff;
                z-index: 4;

            }

            /* Third column */
            .col-3 {
                position: sticky;
                left: 170px;
                /* col-1 + col-2 width */
                background-color: #fff;
                z-index: 3;

            }
        }
    </style>