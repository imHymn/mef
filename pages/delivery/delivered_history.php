<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
<style>
    /* Row hover highlight */
    .custom-hover tbody tr:hover {
        background-color: #dde0e2;
        /* light blue */
    }

    /* Make table horizontally scrollable */
    .table-responsive {
        overflow-x: auto;
        width: 100%;
    }

    /* Table cell styling */
    .custom-hover th,
    .custom-hover td {
        text-align: center;
        white-space: nowrap;
        padding: 8px 6px;
        vertical-align: middle;
    }
</style>

<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Pages</a></li>
            <li class="breadcrumb-item" aria-current="page">Delivery Section</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="card-title mb-0">Pulled out History</h6>
                        <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
                    </div>

                    <div class="row mb-3 align-items-end g-2">
                        <!-- Left: Search input -->
                        <div class="col-md-3">
                            <input type="text" id="search-input" class="form-control" placeholder="Type to filter..." />
                        </div>

                        <!-- Right: From and To Dates -->
                        <div class="col-md-9 d-flex justify-content-end gap-2">
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
                        <table class="custom-hover table" style="min-width: 900px; table-layout: fixed;">
                            <thead>
                                <tr>
                                    <th style="width: 10%; text-align: center;">Material No <span class="sort-icon"></span></th>
                                    <th style="width: 15%; text-align: center;">Material Description <span class="sort-icon"></span></th>
                                    <th style="width: 8%; text-align: center;">Total Qty <span class="sort-icon"></span></th>
                                    <th style="width: 8%; text-align: center;">Lot <span class="sort-icon"></span></th>
                                    <th style="width: 10%; text-align: center;">Date Needed <span class="sort-icon"></span></th>
                                    <th style="width: 10%; text-align: center;">Date Loaded <span class="sort-icon"></span></th>
                                    <th style="width: 10%; text-align: center;">Truck <span class="sort-icon"></span></th>
                                    <th style="width: 10%; text-align: center;">Status <span class="sort-icon"></span></th>
                                </tr>
                            </thead>
                            <tbody id="data-body"></tbody>
                        </table>
                    </div>



                    <div id="pagination" class="mt-3 d-flex justify-content-center"></div>
                </div>
            </div>
        </div>
    </div>


    <script>
        let paginator;
        let originalData = [];
        let filteredData = [];
        let model = null;
        const fromDateInput = document.getElementById('from-date');
        const toDateInput = document.getElementById('to-date');

        function applyCombinedFilters() {
            const selectedColumn = document.querySelector('#column-select').value;
            const searchValue = document.querySelector('#search-input').value.toLowerCase();
            const fromDate = fromDateInput.value;
            const toDate = toDateInput.value;

            let filtered = originalData.filter(row => {
                // ✅ Text column filtering
                if (selectedColumn && searchValue) {
                    let val = row[selectedColumn] ?? '';
                    if (!val.toString().toLowerCase().includes(searchValue)) {
                        return false;
                    }
                }

                // ✅ Date range filtering
                if (fromDate || toDate) {
                    const createdAt = new Date(row.created_at);
                    const from = fromDate ? new Date(fromDate + 'T00:00:00') : null;
                    const to = toDate ? new Date(toDate + 'T23:59:59') : null;

                    if ((from && createdAt < from) || (to && createdAt > to)) {
                        return false;
                    }
                }

                return true;
            });

            filteredData = filtered;
            paginator.setData(filteredData);
        }

        // Listen to changes
        fromDateInput.addEventListener('change', applyCombinedFilters);
        toDateInput.addEventListener('change', applyCombinedFilters);



        function getData(model) {
            fetch('api/delivery/getDeliveryHistory?model=' + encodeURIComponent(model || '') + '&_=' + new Date().getTime())
                .then(res => res.json())
                .then(data => {
                    document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;

                    originalData = data;
                    filteredData = data;

                    paginator = createPaginator({
                        data: filteredData,
                        rowsPerPage: 10,
                        paginationContainerId: 'pagination',
                        renderPageCallback: renderPulledOutTable,
                        defaultSortFn: (a, b) => new Date(b.date_needed) - new Date(a.date_needed)
                    });
                    paginator.render();

                    // Only search/filter visible columns
                    const searchableFields = [
                        'material_no',
                        'material_description',
                        'fuel_type',
                        'total_quantity',
                        'lot_no',
                        'date_needed',
                        'date_loaded',
                        'truck',
                        'status'
                    ];

                    setupSearchFilter({
                        filterInputSelector: '#search-input',
                        data: originalData,
                        searchableFields,
                        onFilter: (filtered, query) => {
                            currentFilterQuery = query || '';
                            filteredData = filtered;
                            paginator.setData(filteredData);
                        }
                    });
                })
                .catch(console.error);
        }

        function renderPulledOutTable(pageData) {
            const tbody = document.getElementById('data-body');
            tbody.innerHTML = ''; // clear rows

            pageData.forEach(item => {
                const row = document.createElement('tr');

                row.innerHTML = `
            <td style="text-align: center;">${highlightText(item.material_no, currentFilterQuery)}</td>
            <td style="text-align: center; white-space: normal; word-wrap: break-word;">
                ${highlightText(item.material_description, currentFilterQuery)}
                ${item.fuel_type ? ` (${highlightText(item.fuel_type, currentFilterQuery)})` : ''}
            </td>
            <td style="text-align: center;">${highlightText(item.total_quantity?.toString() ?? '', currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.lot_no ?? '<i>NONE</i>', currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.date_needed || '<i>NONE</i>', currentFilterQuery)}</td>
            <td style="text-align: center;white-space: normal; word-wrap: break-word;">${highlightText(item.date_loaded || '<i>NONE</i>', currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.truck || '<i>NONE</i>', currentFilterQuery)}</td>
            <td style="text-align: center;">
                <span class="btn btn-sm btn-primary" tabindex="-1" role="button" aria-disabled="true">
                    ${highlightText(item.status?.toUpperCase() ?? '', currentFilterQuery)}
                </span>
            </td>
        `;
                tbody.appendChild(row);
            });
        }



        enableTableSorting(".table");
    </script>