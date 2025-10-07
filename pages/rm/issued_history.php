<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<script src="assets/js/html5.qrcode.js"></script>
<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<link rel="stylesheet" href="assets/css/all.min.css" />
<script src="assets/js/bootstrap.bundle.min.js"></script>
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
            <li class="breadcrumb-item" aria-current="page">Raw Materials Section</li>
        </ol>
    </nav>
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="card-title mb-0">Issued History</h6>
                        <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
                    </div>

                    <div class="row mb-3 align-items-end g-2">
                        <!-- Left Section: Column Select + Search -->
                        <div class="col-md-3 d-flex gap-2">

                            <div class="flex-grow-1">
                                <label for="filter-input" class="form-label">Search</label>
                                <input type="text" id="filter-input" class="form-control" placeholder="Type to filter..." />
                            </div>
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

                    <div class="table-wrapper">
                        <table class="custom-hover table">
                            <thead>
                                <tr>
                                    <th style="width: 5%; text-align: center;">Material No <span class="sort-icon"></span></th>
                                    <th style="width: 10%; text-align: center;">Material Description <span class="sort-icon"></span></th>
                                    <th style="width: 20%; text-align: center;">Raw Materials <span class="sort-icon"></span></th>
                                    <th style="width: 5%; text-align: center;">Quantity <span class="sort-icon"></span></th>
                                    <th style="width: 5%; text-align: center;">Status <span class="sort-icon"></span></th>
                                    <th style="width: 10%; text-align: center;">Time & Date <span class="sort-icon"></span></th>
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
</div>

<script>
    let fullData = [];
    let paginator;

    const dataBody = document.getElementById('data-body');

    function highlightText(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query})`, 'gi');
        return text?.toString().replace(regex, '<mark>$1</mark>') || '';
    }

    function getData(model) {
        fetch(`api/rm/getIssuedHistory?model=${encodeURIComponent(model)}`)
            .then(response => response.json())
            .then(data => {
                fullData = data.data || [];

                // Initialize paginator
                paginator = createPaginator({
                    data: fullData,
                    rowsPerPage: 10,
                    paginationContainerId: 'pagination',
                    renderPageCallback: renderTable
                });
                paginator.render();

                const fromDateInput = document.getElementById('from-date');
                const toDateInput = document.getElementById('to-date');

                let lastFiltered = fullData;

                const applyDateFilter = () => {
                    const from = fromDateInput.value;
                    const to = toDateInput.value;

                    const result = lastFiltered.filter(row => {
                        const delivered = (row.delivered_at ?? '').split(' ')[0];
                        if (from && delivered < from) return false;
                        if (to && delivered > to) return false;
                        return true;
                    });

                    paginator.setData(result);
                };

                fromDateInput.addEventListener('change', applyDateFilter);
                toDateInput.addEventListener('change', applyDateFilter);

                // Apply search filter
                setupSearchFilter({
                    filterColumnSelector: '#filter-column',
                    filterInputSelector: '#filter-input',
                    data: fullData,
                    onFilter: filtered => {
                        lastFiltered = filtered;
                        applyDateFilter();
                    },
                    customColumnHandler: {
                        material_no: row => row.material_no ?? '',
                        component_name: row => row.component_name ?? '',
                        quantity: row => String(row.quantity ?? ''),
                        created_at: row => (row.delivered_at ?? '').split(' ')[0]
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });
    }

    function renderTable(data) {
        const dataBody = document.getElementById('data-body');
        dataBody.innerHTML = '';

        if (!data || !data.length) {
            dataBody.innerHTML = `<tr><td colspan="6" style="text-align:center;">No records found</td></tr>`;
            return;
        }

        const currentFilterQuery = document.getElementById('filter-input')?.value.toLowerCase() || '';

        data.forEach(item => {
            const rawMaterials = (() => {
                try {
                    return typeof item.raw_materials === 'string' ? JSON.parse(item.raw_materials) : item.raw_materials || [];
                } catch {
                    return [];
                }
            })();

            const quantity = parseInt(item.rm_quantity) || 0;

            const rawHTML = rawMaterials.length ? `
  <div style="overflow-x:auto; max-width: 100%;">
    <table class="table table-sm table-bordered mb-0" 
           style="margin:0; table-layout: auto; min-width: 250px; width: auto; white-space: nowrap;">
      <thead>
        <tr>
          <th style="font-size:10px; padding: 2px; width: 1%;">No</th>
          <th style="font-size:10px; padding: 2px; width: 1%;">Desc</th>
          <th style="font-size:10px; padding: 2px; width: 1%;">Total</th>
        </tr>
      </thead>
      <tbody>
        ${rawMaterials.map(rm => `
          <tr>
            <td style="font-size:10px; padding: 2px;">${highlightText(rm.material_no, currentFilterQuery)}</td>
            <td style="font-size:10px; padding: 2px;">${highlightText(rm.material_description, currentFilterQuery)}</td>
            <td style="font-size:10px; padding: 2px;">${Math.ceil(quantity / parseFloat(rm.usage || '1'))}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>
  </div>` :
                '<em style="font-size:12px;">None</em>';


            const statusStyleMap = {
                'Maximum': 'color: green; font-weight: bold; text-shadow: -1px -1px 0 #004d00, 1px -1px 0 #004d00, -1px 1px 0 #004d00, 1px 1px 0 #004d00;',
                'Critical': 'color: red; font-weight: bold; text-shadow: -1px -1px 0 #800000, 1px -1px 0 #800000, -1px 1px 0 #800000, 1px 1px 0 #800000;',
                'Minimum': 'color: orange; font-weight: bold; text-shadow: -1px -1px 0 #cc6600, 1px -1px 0 #cc6600, -1px 1px 0 #cc6600, 1px 1px 0 #cc6600;',
                'Reorder': 'color: yellow; font-weight: bold; text-shadow: -1px -1px 0 #999900, 1px -1px 0 #999900, -1px 1px 0 #999900, 1px 1px 0 #999900;'
            };
            const style = statusStyleMap[item.status] || 'color: gray; font-weight: bold;';
            const statusHTML = `<span style="${style}">${highlightText(item.status, currentFilterQuery).toUpperCase()}</span>`;

            const row = document.createElement('tr');
            row.innerHTML = `
        <td style="text-align:center;">${highlightText(item.material_no, currentFilterQuery)}</td>
        <td style="text-align:center;">${highlightText(item.component_name, currentFilterQuery)}</td>
        <td style="text-align:center;">${rawHTML}</td>
        <td style="text-align:center;">${highlightText(quantity, currentFilterQuery)}</td>
        <td style="text-align:center;">${statusHTML}</td>
        <td style="text-align:center;">${highlightText(item.delivered_at, currentFilterQuery)}</td>
      `;

            dataBody.appendChild(row);
        });

        document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;

        enableTableSorting(".table");
    }
</script>
<style>
    .table-wrapper {
        width: 100%;
    }

    /* Apply horizontal scroll only on tablets (768px–991px) */
    @media (min-width: 768px) and (max-width: 991.98px) {
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            /* smooth scrolling */
        }

        .table-wrapper table {
            min-width: 900px;
            /* force scroll if table is wider than viewport */
        }
    }
</style>