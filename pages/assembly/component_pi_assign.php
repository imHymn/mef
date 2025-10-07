<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
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
                                        <th class="sticky-col col-1" style="width: 3%; text-align: center;"><span class="sort-icon"></span></th>
                                        <th class="sticky-col col-2" style="width: 5%; text-align: center;">Material No <span class="sort-icon"></span></th>
                                        <th class="sticky-col col-3" style="width: 10%; text-align: center; white-space: normal; word-wrap: break-word;">Material Description <span class="sort-icon"></span></th>
                                        <th style="width: 5%; text-align: center; white-space: normal; word-wrap: break-word;">PROCESS <span class="sort-icon"></span></th>
                                        <th style="width: 5%; text-align: center;">SECTION <span class="sort-icon"></span></th>
                                        <th style="width: 5%; text-align: center;">Total Quantity <span class="sort-icon"></span></th>
                                        <th style="width: 10%; text-align: center;">Person Incharge <span class="sort-icon"></span></th>
                                        <th style="width: 5%; text-align: center;">Machine<span class="sort-icon"></span></th>

                                        <th style="width: 3%; text-align: center; white-space: normal; word-wrap: break-word;">Action<span class="sort-icon"></span></th>
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


        let globalSection = null;

        let model = null;


        function getData(model) {
            return fetch(`api/stamping/getData_toassign?model=${encodeURIComponent(model)}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data)
                    fullData = preprocessData(data);
                    paginator = createPaginator({
                        data: fullData,
                        rowsPerPage: 20,
                        renderPageCallback: renderPaginatedTable,

                        paginationContainerId: 'pagination'
                    });
                    paginator.render(); // Initial render
                    // 👉 put this immediately after paginator.render();
                    setupSearchFilter({
                        filterColumnSelector: '#filter-column',
                        filterInputSelector: '#filter-input',
                        data: fullData, // the full dataset you already prepared
                        onFilter: filtered => paginator.setData(filtered),

                        // ── handlers for columns that need custom logic ──
                        // (keys MUST match the values in your <select>)
                        customColumnHandler: {
                            material_no: row => row.material_no ?? '',
                            components_name: row => row.components_name ?? '',
                            stage_name: row => row.stage_name ?? '',
                            section: row => row.section ?? '',
                            total_quantity: row => String(row.total_quantity ?? ''),
                            person_incharge: row => row.person_incharge ?? '',
                            time_in: row => row.time_in ?? '',
                            time_out: row => row.time_out ?? ''
                            // add more if you later add columns
                        }
                    });

                })
                .catch(console.error);
        }

        function preprocessData(data) {
            const grouped = {};

            data.forEach(item => {
                if (!grouped[item.reference_no]) grouped[item.reference_no] = [];
                grouped[item.reference_no].push(item);
            });

            const sorted = Object.values(grouped)
                .flatMap(group =>
                    group.sort((a, b) => (parseInt(a.stage || 0) - parseInt(b.stage || 0)))
                );

            return sorted;
        }

        function renderPaginatedTable(pageData) {
            const tbody = document.getElementById('data-body');
            tbody.innerHTML = '';

            originalPageData = [...pageData];
            const allowedSections = ['l300 assy', 'hpi fender assy', 'mig welding'];

            pageData.forEach(item => {
                const sectionName = (item.section || '').toLowerCase();
                if (!allowedSections.includes(sectionName)) {
                    return; // ❌ Skip if not in allowed list
                }


                const personInCharge = item.person_incharge || '<i>NONE</i>';
                const currentRef = item.reference_no;
                const currentSection = item.section?.trim() || 'NONE';

                const row = document.createElement('tr');
                row.classList.add('hoverable-row');

                row.innerHTML = `
            <td style="text-align: center;" class="sticky-col col-1">
                <span style="cursor: pointer; margin-right: 12px;" 
                    onclick="handleRefresh('${item.id}','stamping','${item.section}','reset')">🔄</span>
            </td>
            <td style="text-align: center;" class="sticky-col col-2">
                ${highlightText(item.material_no + (item.fuel_type ? ` (${item.fuel_type})` : ""), currentFilterQuery)}
            </td>
            <td style="text-align: center; white-space: normal; word-wrap: break-word;" class="sticky-col col-3">
                ${highlightText(item.components_name, currentFilterQuery)}
            </td>
            <td style="text-align: center; white-space: normal; word-wrap: break-word;">
                ${highlightText(item.stage_name ?? '<i>NONE</i>', currentFilterQuery)}
            </td>
            <td style="text-align: center;">
                ${highlightText(item.section, currentFilterQuery)}
            </td>
            <td style="text-align: center;">
                ${highlightText(item.total_quantity, currentFilterQuery)}
            </td>
            <td style="text-align: center;">
                ${highlightText(personInCharge, currentFilterQuery)}
            </td>
            <td style="text-align: center; white-space: normal; word-wrap: break-word;">
                ${highlightText(item.machine_name ?? '<i>NONE</i>', currentFilterQuery)}
            </td>
            <td style="text-align: center;">
                <input 
                    type="checkbox" 
                    class="assign-checkbox"
                    style="transform: scale(1.5); cursor: pointer; margin: 4px;"
                    data-id="${item.id}"
                    data-material_no="${item.material_no}"
                    data-components_name="${item.components_name}"
                    data-stage_name="${item.stage_name}"
                    data-model="${item.model}"
                          data-total_quantity="${item.total_quantity}"
                    data-section="${item.section}"
                    data-user_production_location="${userProductionLocation}"
                    data-user_role="${userRole}"
                    data-user_production="${userProduction}"
                    data-section_type="stamping"
                    data-type="component"
                >
            </td>
        `;
                tbody.appendChild(row);
            });

            document.getElementById('last-updated').textContent =
                `Last updated: ${new Date().toLocaleString()}`;
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('time-in-btn').addEventListener('click', () => {
                console.log(true);
                const checked = document.querySelectorAll('.assign-checkbox:checked');
                if (checked.length === 0) {
                    alert('Please select at least one item to assign.');
                    return;
                }

                const selectedItems = [...checked].map(cb => ({
                    id: cb.dataset.id,
                    material_no: cb.dataset.material_no,
                    components_name: cb.dataset.components_name,
                    stage_name: cb.dataset.stage_name,
                    model: cb.dataset.model,
                    total_quantity: cb.dataset.total_quantity,
                    section: cb.dataset.section,
                    user_production_location: cb.dataset.user_production_location,
                    user_role: cb.dataset.user_role,
                    user_production: cb.dataset.user_production,
                    type: cb.dataset.type,
                }));

                getTasks(selectedItems);
            });
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