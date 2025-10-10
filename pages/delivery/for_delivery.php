<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>

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
                        <h6 class="card-title mb-0">To Be Deliver/Pull out</h6>
                        <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12 col-sm-6 col-md-3">
                            <input type="text" id="search-input" class="form-control" placeholder="Type to filter..." />
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-hover table " style="table-layout: fixed; width: 100%;">
                            <thead>
                                <tr>
                                    <th style="width: 10%; text-align: center;">Material No <span class="sort-icon"></span></th>
                                    <th style="width: 20%; text-align: center;">Material Description <span class="sort-icon"></span></th>
                                    <th style="width: 10%; text-align: center;">Total Qty <span class="sort-icon"></span></th>
                                    <th style="width: 8%; text-align: center;">Lot <span class="sort-icon"></span></th>
                                    <th style="width: 10%; text-align: center;">Date Needed <span class="sort-icon"></span></th>
                                    <th style="width: 10%; text-align: center;">Pull Out <span class="sort-icon"></span></th>
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

        function getData(model) {
            fetch(
                    'api/delivery/getPendingDelivery?model=' +
                    encodeURIComponent(model || 'L300 DIRECT') +
                    '&_=' +
                    new Date().getTime()
                )
                .then(res => res.json())
                .then(data => {
                    document.getElementById('last-updated').textContent =
                        `Last updated: ${new Date().toLocaleString()}`;

                    // PRIORITIZE rows where quantity !== total_quantity
                    const incomplete = data.filter(item => item.quantity !== item.total_quantity);
                    const complete = data.filter(item => item.quantity === item.total_quantity);
                    originalData = [...incomplete, ...complete];
                    filteredData = originalData;

                    paginator = createPaginator({
                        data: filteredData,
                        rowsPerPage: 10,
                        paginationContainerId: 'pagination',
                        renderPageCallback: renderPulledOutTable,
                        defaultSortFn: (a, b) => {
                            const dateA = new Date(a.date_needed || '9999-12-31');
                            const dateB = new Date(b.date_needed || '9999-12-31');

                            if (dateA < dateB) return -1;
                            if (dateA > dateB) return 1;

                            const lotA = a.lot_no?.toString().padStart(10, '0') || '';
                            const lotB = b.lot_no?.toString().padStart(10, '0') || '';

                            return lotA.localeCompare(lotB, undefined, {
                                numeric: true
                            });
                        }
                    });

                    paginator.render();

                    const searchableFields = [
                        'material_no',
                        'material_description',
                        'fuel_type',
                        'model',
                        'total_quantity',
                        'lot_no',
                        'date_needed'
                    ];

                    setupSearchFilter({
                        filterInputSelector: '#search-input',
                        data: originalData,
                        searchableFields,
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
            const tbody = document.getElementById('data-body');
            tbody.innerHTML = '';

            const groupMap = {};
            pageData.forEach(item => {
                const key = `${item.material_description}|${item.model}|${item.date_needed}`;
                groupMap[key] = (groupMap[key] || 0) + 1;
            });

            pageData.forEach(item => {
                const row = document.createElement('tr');

                const actionButton =
                    (["MILLIARD", "APS", "KOMYO"].includes(item.model?.toUpperCase()) && item.active === null) ?
                    `<button class="btn btn-sm btn-secondary" disabled>ONGOING</button>` :
                    (item.action ?
                        `<button class="btn btn-sm btn-success" disabled>DONE</button>` :
                        `<button class="btn btn-sm btn-warning" onclick='handleAction(${JSON.stringify(item)})'>CONFIRM</button>`);

                const groupKey = `${item.material_description}|${item.model}|${item.date_needed}`;
                const lotDisplay = groupMap[groupKey] > 1 ? 'S' : item.lot_no;

                row.innerHTML = `
      <td style="text-align: center;">
        ${highlightText(item.material_no, currentFilterQuery)}
      </td>
      <td style="text-align: center; white-space: normal; word-wrap: break-word;">
        ${highlightText(item.material_description, currentFilterQuery)}
        ${item.fuel_type ? ` (${highlightText(item.fuel_type, currentFilterQuery)})` : ''}
      </td>

      <td style="text-align: center;">
        ${highlightText(item.total_quantity, currentFilterQuery)}
      </td>
    <td style="text-align: center;">
  ${highlightText(lotDisplay && lotDisplay.trim() !== "" ? lotDisplay : "<i>NONE</i>", currentFilterQuery)}
</td>

      <td style="text-align: center;">
        ${highlightText(item.date_needed || '<i>NONE</i>', currentFilterQuery)}
      </td>
      <td style="text-align: center;">
        ${actionButton}
      </td>
    `;

                tbody.appendChild(row);
            });
        }

        function handleAction(item) {
            let specialModels = ['MILLIARD', 'APS', 'KOMYO'];
            const modelKey = (item.model || '').toUpperCase();

            if (item.active === null && specialModels.includes(modelKey)) {
                showAlert(
                    'warning',
                    'Cannot Proceed',
                    'The order is still in process.'
                );
                return; // stop further action
            }


            specialModels = [...specialModels, 'VALERIE', 'PNR'];

            fetch('api/delivery/getTruck')
                .then(res => res.json())
                .then(truckList => {
                    const options = truckList
                        .map(t => `<option value="${t.name}">${t.name}</option>`)
                        .join('');

                    Swal.fire({
                        title: 'Confirm Action',
                        html: `
          <p><strong>Material No:</strong> ${item.material_no}</p>
          <p><strong>Component:</strong> ${item.material_description}</p>
          <label for="truck-select">Select Truck:</label>
          <select id="truck-select" class="swal2-input">
            <option value="" disabled selected>Select a truck</option>
            ${options}
          </select>
          <label for="qty-input">Quantity (max ${item.total_quantity})</label>
          <input id="qty-input" type="number" min="1" max="${item.total_quantity}"class="swal2-input" placeholder="Enter quantity">
        `,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Confirm',
                        cancelButtonText: 'Cancel',
                        customClass: {
                            popup: 'swal-sm'
                        },
                        preConfirm: () => {
                            const truck = document.getElementById('truck-select').value;
                            const qty = parseInt(document.getElementById('qty-input').value, 10);

                            if (!truck) {
                                Swal.showValidationMessage('Please select a truck', {
                                    customClass: {
                                        popup: 'swal-sm'
                                    }
                                });

                                return false;
                            }
                            if (!qty || qty < 1 || qty > item.total_quantity) {
                                Swal.showValidationMessage(
                                    `Quantity must be between 1 and ${item.total_quantity}`, {
                                        customClass: {
                                            popup: 'swal-sm'
                                        }
                                    }
                                );

                                return false;
                            }
                            return {
                                truck,
                                qty
                            };
                        }
                    }).then(result => {
                        if (!result.isConfirmed) return;

                        const {
                            truck,
                            qty
                        } = result.value;

                        const payload = {
                            ...item,
                            truck,
                            qty_allocated: qty
                        };

                        console.log('Payload to be sent:', payload);

                        const url = specialModels.includes(modelKey) ?
                            'api/delivery/component_delivery' :
                            'api/delivery/sku_delivery';

                        fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify(payload)
                            })
                            .then(r => r.json())
                            .then(res => {
                                if (res.status === "success") {
                                    showAlert('success', 'Success', res.message || 'Action completed successfully.');
                                    item.quantity -= qty;
                                    // window.location.reload();
                                } else {
                                    showAlert('error', 'Error', res.message || 'Action failed.');
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                showAlert('error', 'Error', 'Something went wrong while connecting to the server.');
                            });

                    });
                })
                .catch(err => {
                    console.error(err);
                    showAlert('error', 'Error', 'Failed to load truck list.');


                });
        }

        enableTableSorting(".table");
    </script>
    <style>
        .custom-hover tbody tr:hover {
            background-color: #dde0e2ff !important;
            /* light blue */
        }

        /* General table styling */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        .custom-hover th,
        .custom-hover td {
            text-align: center;
            white-space: nowrap;
            padding: 8px 6px;
            vertical-align: middle;
        }

        /* Tablet-specific adjustments */
        @media (max-width: 991.98px) {

            /* Reduce font and padding */
            .custom-hover th,
            .custom-hover td {
                font-size: 13px;
                padding: 10px 8px;
            }

            /* Wrap long text in descriptions */
            .custom-hover th,
            .custom-hover td {
                white-space: normal;
                word-wrap: break-word;
            }

            /* Adjust column widths proportionally */
            .custom-hover th:nth-child(1),
            .custom-hover td:nth-child(1) {
                width: 15%;
            }

            .custom-hover th:nth-child(2),
            .custom-hover td:nth-child(2) {
                width: 25%;
            }


            .custom-hover th:nth-child(3),
            .custom-hover td:nth-child(3) {
                width: 15%;
            }

            .custom-hover th:nth-child(4),
            .custom-hover td:nth-child(4) {
                width: 10%;
            }

            .custom-hover th:nth-child(5),
            .custom-hover td:nth-child(5) {
                width: 10%;
            }

            .custom-hover th:nth-child(6),
            .custom-hover td:nth-child(6) {
                width: 10%;
            }

            /* Search input smaller on tablets */
            #search-input {
                font-size: 0.9rem;
                padding: 0.35rem 0.5rem;
            }
        }
    </style>