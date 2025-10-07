<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>

<?php include './pages/planner/modal/deleteAll.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>





<style>
    .action-trigger.disabled {
        pointer-events: none;
        opacity: 0.5;
    }

    .custom-hover tbody tr:hover {
        background-color: #dde0e2ff !important;
        /* light blue */
    }
</style>

<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Pages</a></li>
            <li class="breadcrumb-item" aria-current="page">Planner Section</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between ">
                        <h6 class="card-title mb-0 d-flex align-items-center gap-2">
                            Form History

                        </h6>
                        <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
                    </div>


                    <div class="row mb-3 align-items-end ">

                        <div class="col-md-3 d-flex ">
                            <input type="text" id="search-input" class="form-control" placeholder="Type to filter..." />

                        </div>

                        <!-- Right Section: From and To Dates -->
                        <div class="col-md-9 d-flex justify-content-end gap-2 ms-auto">
                            <div>
                                <label for="from-date" class="form-label">From</label>
                                <input type="date" id="from-date" class="form-control" />
                            </div>
                            <div>
                                <label for="to-date" class="form-label">To</label>
                                <input type="date" id="to-date" class="form-control" />
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-hover table">
                            <thead>
                                <tr>
                                    <th class="material-no">Material No <span class="sort-icon"></span></th>
                                    <th class="material-desc">Material Description <span class="sort-icon"></span></th>
                                    <th class="status">Status <span class="sort-icon"></span></th>
                                    <th class="qty">Qty <span class="sort-icon"></span></th>
                                    <th class="supplement">Supplement <span class="sort-icon"></span></th>
                                    <th class="total-qty">Total Qty <span class="sort-icon"></span></th>
                                    <th class="lot">Lot <span class="sort-icon"></span></th>
                                    <th class="date-needed">Date Needed <span class="sort-icon"></span></th>
                                    <th class="date-filed">Date Filed <span class="sort-icon"></span></th>

                                </tr>
                            </thead>
                            <tbody id="data-body"></tbody>
                        </table>
                    </div>


                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div></div> <!-- Empty placeholder for left alignment -->

                        <div id="pagination" class="d-flex justify-content-center"></div>

                        <div>
                            <button class="btn btn-danger btn-sm" onclick="openDeleteModal()">Multiple Delete</button>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        let paginator;
        let originalData = [];
        let filteredData = [];

        let model = null;
        let currentPageData = [];




        function getData(model) {

            fetch('api/planner/getFormHistory?model=' + encodeURIComponent(model || 'L300 DIRECT') + '&_=' + new Date().getTime())
                .then(res => res.json())
                .then(allData => {
                    document.getElementById('last-updated').textContent =
                        `Last updated: ${new Date().toLocaleString()}`;

                    const combined = [
                        ...(allData.delivery_form || []),
                        ...(allData.customer_form || []),
                        ...(allData.qcForm || []).map(item => {
                            const isStamping = item.process === 'stamping';
                            const originalQty = item.total_quantity || 0;

                            let quantity = 0;
                            let supplement = 0;

                            if (isStamping) {
                                if (originalQty % 30 === 0) {
                                    quantity = originalQty;
                                    supplement = 0;
                                } else {
                                    quantity = 0;
                                    supplement = originalQty % 30;
                                }
                            } else {
                                quantity = originalQty;
                                supplement = item.supplement_order || 0;
                            }

                            return {
                                ...item,
                                material_description: item.material_description || '<i>NONE</i>',
                                model: item.model || '<i>NONE</i>',
                                quantity: quantity,
                                supplement_order: supplement,
                                total_quantity: originalQty,
                                lot_no: item.lot_no ?? '<i>NONE</i>',
                                date_needed: item.date_needed ?? '<i>NONE</i>',
                                created_at: item.created_at ?? '<i>NONE</i>',
                                id: item.id,
                                isQC: true
                            };
                        })
                    ];

                    const incomplete = combined.filter(item => item.quantity !== item.total_quantity);
                    const complete = combined.filter(item => item.quantity === item.total_quantity);
                    originalData = [...incomplete, ...complete];
                    filteredData = originalData;

                    paginator = createPaginator({
                        data: filteredData,
                        rowsPerPage: 10,
                        paginationContainerId: 'pagination',
                        renderPageCallback: renderPulledOutTable,
                        defaultSortFn: (a, b) => {
                            const dateA = a.date_needed ? new Date(a.date_needed + 'T00:00:00') : new Date(0);
                            const dateB = b.date_needed ? new Date(b.date_needed + 'T00:00:00') : new Date(0);
                            if (dateA.getTime() !== dateB.getTime()) return dateA - dateB;
                            const lotA = parseInt(a.lot_no) || 0;
                            const lotB = parseInt(b.lot_no) || 0;
                            return lotB - lotA;
                        }
                    });

                    paginator.render();

                    // ✅ Filter only by visible table columns
                    const searchableFields = [
                        'material_no',
                        'material_description',
                        'fuel_type',
                        'model',
                        'section',
                        'quantity',
                        'supplement_order',
                        'total_quantity',
                        'lot_no',
                        'date_needed',
                        'created_at'
                    ];

                    setupSearchFilter({
                        filterInputSelector: '#search-input',
                        data: originalData,
                        searchableFields, // 🔑 restrict to these fields
                        onFilter: (filtered, query) => {
                            currentFilterQuery = query || '';
                            const incompleteFiltered = filtered.filter(i => i.quantity !== i.total_quantity);
                            const completeFiltered = filtered.filter(i => i.quantity === i.total_quantity);
                            filteredData = [...incompleteFiltered, ...completeFiltered];
                            paginator.setData(filteredData);
                        }
                    });
                })
                .catch(console.error);
        }

        function renderPulledOutTable(pageData) {
            currentPageData = pageData;
            const tbody = document.getElementById('data-body');
            tbody.innerHTML = '';

            if (!Array.isArray(pageData) || pageData.length === 0) {
                const noDataRow = document.createElement('tr');
                noDataRow.innerHTML = `
            <td colspan="12" style="text-align: center; font-style: italic; color: #666;font-size:18px">
                No data for the specified model.
            </td>`;
                tbody.appendChild(noDataRow);
                document.getElementById('last-updated').textContent =
                    `Last updated: ${new Date().toLocaleString()}`;
                return;
            }

            pageData.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
            <td style="text-align: center;" class="material-no">${highlightText(item.material_no, currentFilterQuery)}</td>
            <td style="text-align: center; white-space: normal; word-wrap: break-word;" class="material-desc">
                ${highlightText(item.material_description, currentFilterQuery)}
                ${item.fuel_type ? ` (${highlightText(item.fuel_type, currentFilterQuery)})` : ''}
            </td>

            <td style="text-align: center; white-space: normal; word-wrap: break-word;">
                ${highlightText(
                    item.section?.toUpperCase() === 'DELIVERY' ? 'DELIVERY & ASSEMBLY' : (item.section ?? '').toUpperCase(),
                    currentFilterQuery
                )}
            </td>
            <td style="text-align: center;">${highlightText(item.quantity?.toString() ?? '', currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.supplement_order ?? '<i>NONE</i>', currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.total_quantity?.toString() ?? '', currentFilterQuery)}</td>
            <td style="text-align: center;display:none;">${highlightText(item.variant ?? '<i>NONE</i>', currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.lot_no ?? '<i>NONE</i>', currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.date_needed || '<i>NONE</i>', currentFilterQuery)}</td>
            <td style="text-align: center; white-space: normal; word-wrap: break-word;">
                ${highlightText(item.created_at || '<i>NONE</i>', currentFilterQuery)}
            </td>
      `;
                tbody.appendChild(row);
            });
        }



        function isActionAllowed(createdAt) {
            if (!createdAt) return true; // allow if no timestamp

            const createdDate = new Date(createdAt);
            if (isNaN(createdDate)) return true; // invalid date, allow by default

            // Build deadline: next day 6AM
            const deadline = new Date(createdDate);
            deadline.setDate(deadline.getDate() + 1); // next day
            deadline.setHours(6, 0, 0, 0); // 06:00:00

            const now = new Date();
            return now <= deadline; // allowed if still before deadline
        }



        enableTableSorting(".table");
    </script>
    <style>
        /* General table styling */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        .custom-hover {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .custom-hover th,
        .custom-hover td {
            text-align: center;
            white-space: nowrap;
            padding: 8px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #ddd;
        }

        /* Sticky first column: Material No */
        .custom-hover th.material-no,
        .custom-hover td.material-no {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 3;
            min-width: 120px;
            font-weight: 500;
        }

        /* Sticky second column: Material Description */
        .custom-hover th.material-desc,
        .custom-hover td.material-desc {
            position: sticky;
            left: 120px;
            /* match width of first column */
            background: #fff;
            z-index: 2;
            min-width: 220px;
            border-right: 2px solid #ddd;

            /* make description left-aligned for readability */
            padding-left: 12px;
        }

        /* Keep table header on top */
        .custom-hover th {
            top: 0;
            z-index: 4;

        }

        /* Wrap long text in certain columns */
        .custom-hover td.material-desc,
        .custom-hover td.status,
        .custom-hover td.date-filed {
            white-space: normal;
            word-wrap: break-word;
        }

        .custom-hover tbody tr:hover .material-no,
        .custom-hover tbody tr:hover .material-desc {
            background-color: #dde0e2 !important;
        }

        /* Tablet-specific adjustments */
        @media (min-width: 768px) and (max-width: 991px) {

            .custom-hover th,
            .custom-hover td {
                font-size: 14px;
                padding: 6px 4px;
            }

            /* Adjust sticky offsets */
            .custom-hover th.material-desc,
            .custom-hover td.material-desc {
                left: 120px;
            }

            /* Adjust widths for better tablet layout */
            .material-no {
                min-width: 100px;
            }

            .material-desc {
                min-width: 180px;
            }


            .status {
                width: 12%;
            }

            .qty,
            .supplement,
            .total-qty {
                width: 10%;
            }

            .lot {
                width: 8%;
            }

            .date-needed,
            .date-filed {
                width: 12%;
            }

            .actions {
                width: 10%;
            }
        }

        /* Hover effect */
        .custom-hover tbody tr:hover {
            background-color: #dde0e2ff !important;
        }

        /* Optional: sort icon spacing */
        .sort-icon {
            margin-left: 4px;
        }
    </style>