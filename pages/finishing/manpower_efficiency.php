<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="assets/js/sweetalert2@11.js"></script>
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
            <li class="breadcrumb-item" aria-current="page">Finishing Section</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">


                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="card-title mb-0">Manpower Efficiency Data (Individual)</h6>
                        <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
                    </div>
                    <div class="row mb-3 align-items-end g-2">
                        <!-- Left Section: Column Select + Search -->
                        <div class="col-md-3 d-flex gap-2">

                            <div class="flex-grow-1">

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
                                    <th></th>
                                    <th>Date</th>
                                    <th>Material Description</th>
                                    <th>Person Incharge</th>
                                    <th>Quantity</th>
                                    <th>Target CT(Piece)</th>
                                    <th>Actual CT(Piece)</th>
                                    <th>Total Target CT</th>
                                    <th>Total Actual CT</th>
                                    <th>MPEFF</th>
                                </tr>
                            </thead>
                            <tbody id="data-body"></tbody>
                        </table>
                    </div>

                    <nav>
                        <ul id="pagination" class="pagination justify-content-center"></ul>
                    </nav>


                </div>
            </div>
        </div>
    </div>
    <script>
        const filterColumn = document.getElementById('filter-column');
        const filterInput = document.getElementById('filter-input');
        const tbody = document.getElementById('data-body');

        let mergedDataArray = [];
        let filteredData = [];
        let paginator;
        let assembly_targetmpeff;
        const userRole = "<?= $role ?>";
        const userProduction = <?= json_encode($production) ?>;
        const userProductionLocation = <?= json_encode($production_location) ?>;

        let globalSection = null;
        let model = null;


        function formatHoursMinutes(decimalHours) {
            const hours = Math.floor(decimalHours);
            const minutes = Math.round((decimalHours - hours) * 60);
            return `${hours} hrs${minutes > 0 ? ' ' + minutes + ' mins' : ''}`;
        }


        function extractDateOnly(datetimeStr) {
            return datetimeStr ? datetimeStr.slice(0, 10) : '';
        }

        function handleRefresh(id, itemID, materialNo, materialDescription, lotNo, referenceNo, totalQuantity, duplicated, process_no) {



            Swal.fire({
                title: 'Supervisor Authorization Required',
                html: `
      <p>This will reset data for Material No: <strong>${id}</strong></p>
      <input type="password" id="supervisor-code" class="swal2-input" placeholder="Enter Supervisor Authorization Code">
      <input type="number" id="input-quantity" class="swal2-input" placeholder="Enter New Quantity (max: ${totalQuantity})" min="0" max="${totalQuantity}">
    `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, reset it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                preConfirm: () => {
                    const code = document.getElementById('supervisor-code').value.trim();
                    const quantity = document.getElementById('input-quantity').value.trim();

                    if (!code) {
                        Swal.showValidationMessage('Authorization code is required');
                        return false;
                    }

                    const qtyNum = parseInt(quantity, 10);
                    if (isNaN(qtyNum) || qtyNum < 0) {
                        Swal.showValidationMessage('Please enter a valid quantity');
                        return false;
                    }

                    if (qtyNum > totalQuantity) {
                        Swal.showValidationMessage(`Quantity must not exceed ${totalQuantity}`);
                        return false;
                    }

                    return {
                        code,
                        quantity: qtyNum
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const {
                        code: supervisorCode,
                        quantity
                    } = result.value;



                    fetch('api/assembly/reset_manpower.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id,
                                itemID,
                                auth_code: supervisorCode,
                                quantity,
                                production_location: userProductionLocation,
                                production: userProduction,
                                role: userRole,
                                material_no: materialNo,
                                material_description: materialDescription,
                                lot_no: lotNo,
                                reference_no: referenceNo,
                                total_quantity: totalQuantity,
                                duplicated,
                                process_no
                            })
                        })
                        .then(async res => {
                            const text = await res.text();
                            try {
                                const json = JSON.parse(text);
                                if (json.success) {
                                    Swal.fire({
                                        title: 'Reset!',
                                        text: `Data has been reset and quantity updated.`,
                                        icon: 'success',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire('Error', json.message || 'Authorization failed.', 'error');
                                }
                            } catch (err) {
                                console.error('❌ JSON parse error:', err);
                                Swal.fire('Error', 'Invalid JSON response from server.', 'error');
                            }
                        })
                        .catch(err => {
                            Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                            console.error(err);
                        });
                }
            });
        }

        function renderPageCallback(pageData) {
            tbody.innerHTML = '';
            console.log(pageData)
            let assembly_targetmpeff = localStorage.getItem('ASSEMBLY_TARGETMPEFF');

            const targetMpeff = parseFloat(assembly_targetmpeff) || 0;

            // Group by date + component + person
            const mergedRows = {};

            pageData.forEach(entry => {
                const firstIn = new Date(Math.min(...entry.timeIns.map(t => t.getTime())));
                const lastOut = new Date(Math.max(...entry.timeOuts.map(t => t.getTime())));
                const spanSeconds = (lastOut - firstIn) / 1000;
                const workSeconds = entry.totalWorkMinutes * 60;
                const standbySeconds = spanSeconds - workSeconds;

                Object.entries(entry.materialCount).forEach(([material, matData]) => {
                    const componentName = matData.component_name || '-';
                    const person = entry.person;
                    const id = entry.id;

                    const date = entry.date;
                    const qty = matData.qty;
                    const process_no = entry.processNo ?? 1;

                    const key = `${date}||${componentName}||${person}||${process_no}||${id}`;

                    if (!mergedRows[key]) {
                        mergedRows[key] = {
                            date,
                            componentName,
                            person,
                            qty: 0,
                            totalWorkSeconds: 0,
                            totalStandbySeconds: 0,
                            material,
                            entry // Keep ref for cycle lookup
                        };
                    }

                    mergedRows[key].qty += qty;
                    mergedRows[key].totalWorkSeconds += workSeconds;
                    mergedRows[key].totalStandbySeconds += standbySeconds;
                });
            });

            Object.values(mergedRows).forEach(merged => {
                const {
                    date,
                    componentName,
                    person,
                    qty,
                    totalWorkSeconds,
                    totalStandbySeconds,
                    material,
                    entry
                } = merged;

                const {
                    source,
                    materialNo,
                    materialDescription,
                    lotNo
                } = entry;

                let cycle = parseFloat(entry.cycle_time) || 0;

                const actual = (totalWorkSeconds > 0 && qty > 0) ? (totalWorkSeconds / qty) : null;
                const mpeff = (cycle > 0 && totalWorkSeconds > 0 && qty > 0) ?
                    ((cycle * qty) / totalWorkSeconds) * 100 :
                    null;

                const row = document.createElement('tr');

                row.innerHTML = `
<!--<td style="text-align: center;">
    <span
        style="cursor: pointer; margin-left: 8px;"
        onclick="handleRefresh(
            '${entry.id}',
            '${entry.itemID}',
            '${materialNo}',
            '${materialDescription}',
            '${lotNo}',
            '${entry.reference_no}',
            ${entry.totalQuantity || 0},
            '${entry.duplicated ?? 0}',
            '${entry.processNo}'
        )"
    >🔄</span>
</td>-->
<td style="text-align: center;">${highlightText(date, currentFilterQuery)}</td>
<td style="text-align: center; white-space: normal;">${highlightText(componentName, currentFilterQuery)}</td>
<td style="text-align: center;white-space: normal; word-wrap: break-word;">${highlightText(person, currentFilterQuery)}</td>
<td style="text-align: center;">${highlightText(qty, currentFilterQuery)}</td>
<td style="text-align: center;">${highlightText(Math.ceil(cycle), currentFilterQuery)} sec</td>
<td style="text-align: center;">${highlightText(actual !== null ? Math.ceil(actual) : '-', currentFilterQuery)} sec</td>
<td style="text-align: center;">${highlightText(Math.ceil(qty * cycle), currentFilterQuery)} sec</td>
<td style="text-align: center;">
    ${highlightText(Math.ceil(totalWorkSeconds), currentFilterQuery)} sec
    ${totalStandbySeconds > 0 ? ` (${highlightText(Math.ceil(totalStandbySeconds), currentFilterQuery)} sec)` : ''}
</td>
<td style="text-align: center; color: ${
    mpeff !== null && targetMpeff !== null
        ? (mpeff > targetMpeff ? 'green' : 'red')
        : 'inherit'
};">
    ${mpeff !== null
        ? `${highlightText(mpeff.toFixed(1), currentFilterQuery)}% ${
            mpeff > targetMpeff
                ? '<span style="color: green;">▲</span>'
                : '<span style="color: red;">▼</span>'
          }`
        : '-'}
</td>
`;


                if (cycle === 0) {
                    row.style.backgroundColor = '#ffe6e6';
                    row.title = '⚠️ Missing or unmatched cycle time';
                }

                tbody.appendChild(row);
            });

            document.getElementById('last-updated').textContent =
                `Last updated: ${new Date().toLocaleString()}`;
        }



        function getData(model) {
            return Promise.all([
                    fetch(`api/assembly/getManpowerRework.php?model=${encodeURIComponent(model)}`).then(res => res.json())
                ]).then(([reworkData]) => {
                    reworkData = Array.isArray(reworkData) ? reworkData : [];

                    const mergedData = {};

                    function addEntry(
                        id,
                        itemID,
                        materialNo,
                        materialDescription,
                        person,
                        date,
                        reference,
                        timeIn,
                        timeOut,
                        finishedQty,
                        material_no = '',
                        component_name = '',
                        source = '',
                        process_no = null,
                        duplicated = null,
                        lot_no = null,
                        reference_no = null,
                        sub_component = '',
                        assembly_process = '',
                        cycle_time = null
                    ) {
                        const key = `${person}_${date}_${materialDescription}_${process_no}_${id}`;
                        if (!mergedData[key]) {
                            mergedData[key] = {
                                id,
                                itemID,
                                materialNo,
                                materialDescription,
                                person,
                                date,
                                totalFinished: 0,
                                timeIns: [],
                                timeOuts: [],
                                totalWorkMinutes: 0,
                                materialCount: {},
                                source,
                                processNo: process_no ?? null,
                                duplicated: duplicated ?? null,
                                lotNo: lot_no ?? null,
                                reference_no: reference_no ?? null,
                                sub_component: sub_component ?? null,
                                assembly_process: assembly_process ?? null,
                                cycle_time: cycle_time ?? null
                            };
                        }

                        const group = mergedData[key];
                        const timeInDate = new Date(timeIn);
                        const timeOutDate = new Date(timeOut);

                        if (!isNaN(timeInDate) && !isNaN(timeOutDate) && timeOutDate > timeInDate && finishedQty > 0) {
                            const workedMin = (timeOutDate - timeInDate) / (1000 * 60);
                            group.totalWorkMinutes += workedMin;
                            group.timeIns.push(timeInDate);
                            group.timeOuts.push(timeOutDate);
                            group.totalFinished += finishedQty;

                            if (material_no) {
                                if (!group.materialCount[material_no]) {
                                    group.materialCount[material_no] = {
                                        qty: 0,
                                        component_name
                                    };
                                }
                                group.materialCount[material_no].qty += finishedQty;

                                if (!group.materialCount[material_no].component_name && component_name) {
                                    group.materialCount[material_no].component_name = component_name;
                                }
                            }
                        }

                        if (process_no !== null && group.processNo !== process_no) {
                            group.processNo = process_no;
                        }
                    }

                    // ➤ Add rework records only
                    reworkData.forEach(item => {
                        const day = extractDateOnly(item.assembly_timeout);
                        const qty = (parseInt(item.rework) || 0) + (parseInt(item.replace) || 0);
                        const mat = item.material_no || '';
                        const desc = item.material_description || '';

                        addEntry(
                            item.id,
                            item.itemID,
                            item.material_no,
                            item.material_description,
                            item.assembly_person_incharge,
                            day,
                            item.reference_no,
                            item.assembly_timein,
                            item.assembly_timeout,
                            qty,
                            item.material_no,
                            item.material_description,
                            'rework',
                            item.process_no ?? null,
                            item.duplicated ?? null,
                            item.lot_no,
                            item.reference_no,
                            item.sub_component ?? '',
                            item.assembly_process ?? '',
                            item.cycle_time ?? null
                        );
                    });



                    const fromDateInput = document.querySelector('#from-date');
                    const toDateInput = document.querySelector('#to-date');

                    mergedDataArray = Object.values(mergedData);


                    const userLocationNormalized = Array.isArray(userProductionLocation) ?
                        userProductionLocation.map(loc =>
                            String(loc || '').replace(/\s|-/g, '').toLowerCase()
                        ) : [String(userProductionLocation || '').replace(/\s|-/g, '').toLowerCase()];

                    const userIsAdmin = userRole === 'administrator';
                    filteredData = mergedDataArray.filter(entry => {
                        if (userIsAdmin) {
                            return true; // Admin sees all
                        }

                        const sectionKeyNormalized = (entry.section || '')
                            .replace(/\s|-/g, '')
                            .toLowerCase();

                        // If Supervisor → check if "finishing" is one of their locations
                        if (userRole === 'supervisor') {
                            if (userLocationNormalized.includes('finishing')) {
                                return true; // Supervisor with finishing access → see all
                            }
                        }

                        // Otherwise → normal location-based filtering
                        return userLocationNormalized.includes(sectionKeyNormalized);
                    });


                    paginator = createPaginator({
                        data: filteredData,
                        rowsPerPage: 10,
                        renderPageCallback: (page) => renderPageCallback(page),
                        paginationContainerId: 'pagination'
                    });

                    paginator.render();

                    document.getElementById('data-body').addEventListener('click', function(event) {
                        const groupRow = event.target.closest('.group-header');
                        if (!groupRow) return;

                        const batch = groupRow.getAttribute('data-batch');
                        const safeBatch = batch.replace(/\s+/g, '_'); // Match the rendered class
                        const rows = document.querySelectorAll(`.batch-group-${safeBatch}`);

                        if (rows.length === 0) return;

                        const isVisible = rows[0].style.display !== 'none';

                        rows.forEach(row => {
                            row.style.display = isVisible ? 'none' : '';
                        });

                        const componentName = groupRow.getAttribute('data-component');
                        groupRow.querySelector('td').innerHTML =
                            `${isVisible ? '▶️' : '🔽'} Section: ${batch} `;
                    });



                    function applyDateRangeFilter(data) {
                        const fromVal = fromDateInput?.value;
                        const toVal = toDateInput?.value;

                        if (!fromVal && !toVal) return data;

                        return data.filter(row => {
                            if (!row.date) return false;
                            const rowDate = new Date(row.date);
                            const from = fromVal ? new Date(fromVal) : null;
                            const to = toVal ? new Date(toVal) : null;
                            return (!from || rowDate >= from) && (!to || rowDate <= to);
                        });
                    }

                    // Hook up date filter listeners
                    [fromDateInput, toDateInput].forEach(input =>
                        input.addEventListener('change', () => {
                            const dateFiltered = applyDateRangeFilter(filteredData); // filteredData = result from text search
                            paginator.setData(dateFiltered);
                        })
                    );

                    // Hook up search filter
                    setupSearchFilter({
                        filterColumnSelector: '#filter-column',
                        filterInputSelector: '#filter-input',
                        data: mergedDataArray,
                        onFilter: filtered => {
                            currentFilterQuery = document.querySelector('#filter-input')?.value.toLowerCase() || '';
                            filteredData = filtered; // save for date filter to use
                            const dateFiltered = applyDateRangeFilter(filtered);
                            paginator.setData(dateFiltered);
                        },
                        customColumnHandler: {
                            person: item => item.person,
                            material_no: item => (item.materialNo ?? '').toString(),
                            material_description: item => item.materialDescription ?? '',
                            model: item => item.model,
                            lot: item => item.lot,
                            date: item => item.date
                        }
                    });



                })
                .catch(console.error);

        }


        enableTableSorting(".table");
    </script>
    <style>
        /* Hover effect */
        .custom-hover tbody tr:hover {
            background-color: #dde0e2;
        }

        /* Tablet only: horizontal scroll + sticky first 2 columns */
        @media (max-width: 991.98px) {
            .table-wrapper {
                overflow-x: auto;
                width: 100%;
            }

            .custom-hover {
                width: 1200px;
                /* adjust as needed */
                table-layout: fixed;
            }

            .custom-hover tbody tr:hover td,
            .custom-hover tbody tr:hover th {
                background-color: #dde0e2;
            }

            .custom-hover th,
            .custom-hover td {
                white-space: nowrap;
                font-size: 0.85rem;
                padding: 6px 8px;
                text-align: center;
            }

            /* Sticky first column */
            .custom-hover th:nth-child(1),
            .custom-hover td:nth-child(1) {
                position: sticky;
                left: 0;
                background: #ffffff;
                z-index: 10;
                width: 50px;
            }

            /* Sticky second column */
            .custom-hover th:nth-child(2),
            .custom-hover td:nth-child(2) {
                position: sticky;
                left: 50px;
                /* match first column width */
                background: #ffffff;
                z-index: 10;
                width: 120px;
            }

            /* Optionally, adjust widths of other columns */
            .custom-hover th:nth-child(3),
            .custom-hover td:nth-child(3) {
                position: sticky;
                left: 170px;
                /* match first column width */
                background: #f9f9f9;
                z-index: 10;
                width: 150px;
            }

            .custom-hover th:nth-child(4),
            .custom-hover td:nth-child(4) {
                width: 150px;
            }

            .custom-hover th:nth-child(5),
            .custom-hover td:nth-child(5) {
                width: 100px;
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