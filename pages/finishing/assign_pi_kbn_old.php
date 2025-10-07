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

                    <div class="row mb-3 col-md-3">


                        <input
                            type="text"
                            id="filter-input"
                            class="form-control"
                            placeholder="Type to filter..." />

                    </div>

                    <div class="table-wrapper">
                        <table class="custom-hover table">
                            <thead>
                                <tr>
                                    <th style=" text-align: center;"></th>
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
        const userProduction = <?= json_encode($production) ?>;
        const userProductionLocation = <?= json_encode($production_location) ?>;

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
                <td style="text-align: center;" class="sticky-col col-1">
                <span style="cursor: pointer" onclick="handleRefresh('${item.id}','finishing','${item.assembly_section}','reset')">🔄</span>
            </td>
        <td style="text-align: center;">${highlightText(item.material_no, currentFilterQuery)}</td>
        <td style="text-align: center;white-space: normal; word-wrap: break-word;">${highlightText(item.material_description, currentFilterQuery)}</td>
        <td style="text-align: center;">${highlightText(item.lot_no, currentFilterQuery)}</td>
           <td style="text-align: center;">${highlightText(item.rework, currentFilterQuery)}</td>
        <td style="text-align: center;">${highlightText(item.replace, currentFilterQuery)}</td>
     
        <td style="text-align: center;">${highlightText(item.assembly_quantity, currentFilterQuery)}</td>
        <td style="text-align: center;">${highlightText(item.quantity, currentFilterQuery)}</td>
        <td style="text-align: center;">${highlightText(item.assembly_person_incharge || '-', currentFilterQuery)}</td>
<td style="text-align: center;">
  <button 
    type="button" 
    class="btn btn-primary btn-sm" 
onclick="getTasks(
  '${item.id}',
  '${item.material_no}',
  '${item.material_description}',
  '${item.rework}',
  '${item.replace}',
  '${item.model}',
  '${item.assembly_section}',
  '${userProductionLocation}'
)"


  >ASSIGN</button>
</td>
      `;
                tbody.appendChild(tr);
            });

            document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
        }
        // Fetch data for the selected model
        function getData(model) {
            fetch(`api/finishing/getTask.php?model=${encodeURIComponent(model)}`)
                .then(response => response.json())
                .then(data => {
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

        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('time-in-btn') || event.target.classList.contains('time-out-btn')) {
                const button = event.target;
                const materialId = button.getAttribute('data-materialid');
                selectedRowData = JSON.parse(button.getAttribute('data-item').replace(/&apos;/g, "'"));
                const mode = button.getAttribute('data-mode');
                const id = button.getAttribute('data-id');

                globalSection = selectedRowData.assembly_section;
                const {
                    material_no,
                    material_description
                } = selectedRowData;

                Swal.fire({
                    icon: 'question',
                    title: `Confirm ${mode === 'timeIn' ? 'Time-In' : 'Time-Out'}`,
                    html: `<b>Material No:</b> ${material_no}<br><b>Component:</b> ${material_description}`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Proceed',
                    cancelButtonText: 'Cancel'
                }).then(result => {
                    if (result.isConfirmed) {
                        if (mode === 'timeIn') {
                            openQRModal(selectedRowData, mode, globalSection);
                        } else if (mode === 'timeOut') {
                            document.getElementById('recordIdHidden').value = selectedRowData.id;
                            document.getElementById('totalQtyHidden').value = selectedRowData.quantity;

                            // Reset form and clear messages
                            document.getElementById('inspectionForm').reset();
                            if (document.getElementById('errorMsg')) {
                                document.getElementById('errorMsg').textContent = '';
                            }
                            if (document.getElementById('followUpErrorMsg')) {
                                document.getElementById('followUpErrorMsg').textContent = '';
                            }
                            if (document.getElementById('followUpSection')) {
                                document.getElementById('followUpSection').style.display = 'none';
                            }


                            // Show the modal
                            inspectionModal = new bootstrap.Modal(document.getElementById('inspectionModal'));
                            inspectionModal.show();
                        }
                    }
                });
            }
        });

        function closeInspection() {
            inspectionModal.hide()
        }

        function submitInspection() {
            if (!selectedRowData) { // no row selected
                Swal.fire('Error', 'No record selected.', 'error');
                return;
            }

            const reworkInput = document.getElementById('rework');
            const replaceInput = document.getElementById('replace');

            // Treat blank as NaN so isNaN() can detect “no data”
            const reworkRaw = reworkInput.value.trim();
            const replaceRaw = replaceInput.value.trim();

            if (reworkRaw === '' && replaceRaw === '') {
                Swal.fire('Missing Data', 'Please enter Rework or Replace quantity.', 'warning');
                return;
            }

            const rework = parseInt(reworkRaw, 10) || 0;
            const replace = parseInt(replaceRaw, 10) || 0;
            const quantity = rework + replace;

            if (quantity === 0) { // both zero (or invalid)
                Swal.fire('Invalid Quantity', 'Total quantity cannot be zero.', 'error');
                return;
            }

            if (selectedRowData.assembly_quantity && quantity > selectedRowData.assembly_quantity) {
                Swal.fire('Invalid Quantity',
                    `Quantity must be ≤ ${selectedRowData.assembly_quantity}.`, 'error');
                return;
            }

            Swal.fire({
                title: 'Confirm Submission',
                html: `
      <strong>Rework:</strong> ${rework}<br>
      <strong>Replace:</strong> ${replace}<br>
      <strong>Total:</strong> ${quantity}
    `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, submit'
            }).then(({
                isConfirmed
            }) => {
                if (!isConfirmed) return;

                selectedRowData.rework = rework;
                selectedRowData.replace = replace;
                selectedRowData.inputQty = quantity;
                globalSection = selectedRowData.assembly_section;
                inspectionModal.hide();
                openQRModal(selectedRowData, 'timeOut', globalSection);
            });
        }


        function openQRModal(selectedRowData, mode, globalSection) {
            console.log(selectedRowData)
            const role = "operator";
            const section = "ASSEMBLY";
            scanQRCodeForUser({
                section,
                role,
                userProductionLocation: selectedRowData.assembly_section,
                onSuccess: ({
                    user_id,
                    full_name
                }) => {
                    // ✅ Only check person-in-charge during timeout
                    const expectedPersonInCharge = selectedRowData.assembly_person_incharge || '';
                    if (mode === 'timeOut' && full_name !== expectedPersonInCharge) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Person In-Charge Mismatch',
                            text: `Scanned name "${full_name}" does not match assigned person "${expectedPersonInCharge}".`,
                            confirmButtonText: 'OK'
                        });
                        return;
                    }

                    const data = {
                        id: selectedRowData.id,
                        full_name: full_name,
                        inputQty: selectedRowData.inputQty,
                        replace: selectedRowData.replace,
                        rework: selectedRowData.rework,
                        reference_no: selectedRowData.reference_no,
                        quantity: selectedRowData.quantity,
                        assembly_pending_quantity: selectedRowData.assembly_pending_quantity,
                        rework_no: selectedRowData.rework_no,
                        assembly_section: selectedRowData.assembly_section,
                        cycle_time: selectedRowData.cycle_time,
                        fuel_type: selectedRowData.fuel_type,
                        model: selectedRowData.model
                    };

                    let url = '/mes/api/finishing/timeinOperator.php';
                    if (mode === 'timeOut') {
                        url = '/mes/api/finishing/timeoutOperator.php';
                    }

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(data)
                        })
                        .then(res => res.json())
                        .then(response => {

                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: 'Your operation was successful!',
                                    confirmButtonColor: '#3085d6'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Operation failed.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Request failed', err);
                            Swal.fire('Error', 'Something went wrong.', 'error');
                        });
                },
                onCancel: () => {

                }
            });
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