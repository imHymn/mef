<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<?php include './components/reusable/exportMPEFF.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>

<style>
    .custom-hover tbody tr:hover {
        background-color: #dde0e2ff !important;
        /* light blue */
    }
</style>
<script src="assets/js/sweetalert2@11.js"></script>

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
                        <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
                            <thead>
                                <tr>

                                    <th>Date <span class="sort-icon"></span></th>
                                    <th>Person Incharge <span class="sort-icon"></span></th>
                                    <th>Quantity <span class="sort-icon"></span></th>
                                    <th>Target CT(Piece) <span class="sort-icon"></span></th>
                                    <th>Actual CT(Piece) <span class="sort-icon"></span></th>
                                    <th>Total Target CT <span class="sort-icon"></span></th>
                                    <th>Total Actual CT <span class="sort-icon"></span></th>
                                    <th>MPEFF <span class="sort-icon"></span></th>
                                </tr>
                            </thead>
                            <tbody id="data-body"></tbody>
                        </table>
                    </div>
                    <nav>
                        <ul id="pagination" class="pagination justify-content-center"></ul>
                    </nav>

                    <div class="d-flex justify-content-end mt-2 gap-2">

                        <button id="open-filter-modal" class="btn btn-sm btn-primary">
                            Export <i class="bi bi-funnel"></i>
                        </button>
                    </div>
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



        function renderPageCallback(pageData) {
            tbody.innerHTML = '';
            console.log(pageData)
            let assembly_targetmpeff = localStorage.getItem('ASSEMBLY_TARGETMPEFF');

            const targetMpeff = parseFloat(assembly_targetmpeff) || 0;

            const sectionGrouped = {};
            pageData.forEach(entry => {
                const sectionKey = (entry.section || 'NO SECTION').toUpperCase();
                if (!sectionGrouped[sectionKey]) sectionGrouped[sectionKey] = [];
                sectionGrouped[sectionKey].push(entry);
            });

            Object.entries(sectionGrouped).forEach(([section, entries]) => {
                const mergedRows = {};

                entries.forEach(entry => {
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
                                entry,
                            };
                        }

                        mergedRows[key].qty += qty;
                        mergedRows[key].totalWorkSeconds += workSeconds;
                        mergedRows[key].totalStandbySeconds += standbySeconds;
                    });
                });

                const groupedByMonth = {};
                Object.values(mergedRows).forEach(merged => {
                    const month = new Date(merged.date).toISOString().slice(0, 7); // YYYY-MM
                    if (!groupedByMonth[month]) groupedByMonth[month] = [];
                    groupedByMonth[month].push(merged);
                });

                Object.entries(groupedByMonth).forEach(([month, monthRows]) => {
                    let totalQty = 0;
                    let totalCycleTime = 0;
                    let cycleCount = 0;
                    let totalWT = 0;

                    monthRows.forEach(merged => {
                        const {
                            qty,
                            entry,
                            material,
                            totalWorkSeconds
                        } = merged;

                        let cycle = 0;

                        // 🔧 Use correct cycle source
                        if (entry.source === 'stamping') {
                            cycle = parseFloat(entry.cycle_time || 0);
                        } else if (entry.source === 'assembly' || entry.source === 'rework') {
                            cycle = parseFloat(entry.cycle_time || 0); // 🔧 REPLACED lookup with direct use
                        }

                        if (cycle > 0 && qty > 0) {
                            totalCycleTime += cycle;
                            cycleCount++;
                        }

                        totalQty += qty;
                        totalWT += totalWorkSeconds;
                    });

                    const avgCycle = cycleCount > 0 ? (totalCycleTime / cycleCount) : 0;
                    const actualCycle = totalWT / totalQty;
                    const totalCT = totalQty * avgCycle;
                    const MPEFF = totalCT / totalWT * 100;

                    const headerRow = document.createElement('tr');
                    headerRow.classList.add('group-header');
                    headerRow.innerHTML = `
   
        <td style="background: #dff0d8; font-weight: bold;">${month}</td>
        <td style="background: #dff0d8; font-weight: bold;">${section}</td>
        <td style="background: #dff0d8; font-weight: bold; text-align: center;">${totalQty}</td>
        <td style="background: #dff0d8; font-weight: bold; text-align: center;">${Math.ceil(avgCycle)} sec</td>
        <td style="background: #dff0d8; font-weight: bold; text-align: center;">${Math.ceil(actualCycle)} sec</td>
        <td style="background: #dff0d8; font-weight: bold; text-align: center;">${Math.ceil(totalCT)} sec</td>
        <td style="background: #dff0d8; font-weight: bold; text-align: center;">${Math.ceil(totalWT)} sec</td>
        <td style="background: #dff0d8; font-weight: bold; text-align: center;">${MPEFF.toFixed(1)}%</td>
      `;
                    tbody.appendChild(headerRow);

                    const groupedByDatePerson = {};

                    monthRows.forEach(merged => {
                        const {
                            date,
                            person,
                            qty,
                            totalWorkSeconds,
                            totalStandbySeconds,
                            material,
                            entry
                        } = merged;

                        // 🔧 Consistently use entry.cycle_time
                        let cycle = parseFloat(entry.cycle_time) || 0;

                        const key = `${date}||${person}`;
                        if (!groupedByDatePerson[key]) {
                            groupedByDatePerson[key] = {
                                date,
                                person,
                                totalQty: 0,
                                totalWorkSeconds: 0,
                                totalStandbySeconds: 0,
                                totalCycle: 0,
                                count: 0,
                                entry
                            };
                        }

                        const group = groupedByDatePerson[key];
                        group.totalQty += qty;
                        group.totalWorkSeconds += totalWorkSeconds;
                        group.totalStandbySeconds += totalStandbySeconds;
                        group.totalCycle += cycle;
                        group.count += 1;
                    });

                    Object.values(groupedByDatePerson).forEach(group => {
                        const {
                            date,
                            person,
                            totalQty,
                            totalWorkSeconds,
                            totalStandbySeconds,
                            totalCycle,
                            count,
                            entry
                        } = group;

                        const avgCycle = count > 0 ? totalCycle / count : 0;

                        const totalWT = totalWorkSeconds;

                        const actual = totalWT / totalQty;
                        const totalCT = totalQty * avgCycle;
                        const mpeff = totalCT / totalWT * 100;
                        const row = document.createElement('tr');
                        const safeSection = section.replace(/\s+/g, '_');
                        row.classList.add(`batch-group-${safeSection}`);
                        row.innerHTML = `

<td style="text-align: center;">${highlightText(date, currentFilterQuery)}</td>
<td style="text-align: center;">${highlightText(person, currentFilterQuery)}</td>
<td style="text-align: center;">${highlightText(totalQty, currentFilterQuery)}</td>
<td style="text-align: center;">${highlightText(Math.ceil(avgCycle), currentFilterQuery)} sec</td>
<td style="text-align: center;">${highlightText(Math.ceil(actual), currentFilterQuery)} sec</td>
<td style="text-align: center;">${highlightText(Math.ceil(totalCT), currentFilterQuery)} sec</td>
<td style="text-align: center;">${highlightText(Math.ceil(totalWT), currentFilterQuery)} sec</td>
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
</td>`;


                        if (avgCycle === 0) {
                            row.style.backgroundColor = '#ffe6e6';
                            row.title = '⚠️ Missing or unmatched cycle time';
                        }

                        tbody.appendChild(row);
                    });
                });
            });

            document.getElementById('last-updated').textContent =
                `Last updated: ${new Date().toLocaleString()}`;
        }




        function getData(model) {
            return Promise.all([

                    fetch(`api/assembly/getManpowerRework.php?model=${encodeURIComponent(model)}`).then(res => res.json()),

                ])
                .then(([reworkData]) => {

                    reworkData = Array.isArray(reworkData) ? reworkData : [];

                    const mergedData = {};

                    function addEntry(id, itemID, materialNo, materialDescription, person, date, reference, timeIn, timeOut, finishedQty, material_no = '', component_name = '', section = '', source = '', process_no = null, duplicated = null, lot_no = null, reference_no = null, sub_component = '', assembly_process = '', cycle_time = '') {

                        const key = `${person}_${date}_${section}_${materialDescription}_${process_no}_${id}`;
                        if (!mergedData[key]) {
                            mergedData[key] = {
                                id,
                                itemID,
                                materialNo,
                                materialDescription,
                                person,
                                date,
                                section,
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


                    reworkData.forEach(item => {
                        if (!item.assembly_timeout || !item.assembly_timein || !item.assembly_person_incharge || !item.created_at) return;

                        const day = extractDateOnly(item.created_at);
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
                            'finishing', // section
                            'rework', // source
                            item.process_no ?? null, // process_no
                            item.duplicated ?? null, // duplicated
                            item.lot_no,
                            item.reference_no,
                            item.sub_component ?? '',
                            item.assembly_process ?? '',
                            item.cycle_time ?? null // ✅ now lands in cycle_time
                        );

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
                    const fromDateInput = document.querySelector('#from-date');
                    const toDateInput = document.querySelector('#to-date');

                    const mergedDataArray = Object.values(mergedData);
                    const userLocationNormalized = Array.isArray(userProductionLocation) ?
                        userProductionLocation.map(loc =>
                            String(loc || '').replace(/\s|-/g, '').toLowerCase()
                        ) : [String(userProductionLocation || '').replace(/\s|-/g, '').toLowerCase()];

                    const userIsAdmin = userRole === 'administrator';

                    roleFilteredData = mergedDataArray.filter(entry => {
                        if (userIsAdmin) return true;

                        const sectionKeyNormalized = (entry.section || '')
                            .replace(/\s|-/g, '')
                            .toLowerCase();

                        return userLocationNormalized.includes(sectionKeyNormalized);
                    });


                    // State for current filtered result
                    let filteredData = [...roleFilteredData];


                    renderPageCallback(filteredData);

                    function compileSectionMonthDailyAvg(filteredData) {
                        const cacheObj = {};
                        // console.log(filteredData)
                        filteredData.forEach(item => {
                            if (
                                !item.timeOuts?.length ||
                                !item.timeIns?.length ||
                                !item.section ||
                                !item.date ||
                                item.totalFinished == null
                            ) return;

                            const section = item.section.toUpperCase();
                            const dateStr = item.date; // YYYY-MM-DD
                            const yearMonth = dateStr.slice(0, 7); // YYYY-MM
                            const key = `${section}__${yearMonth}`;

                            const timeIn = new Date(item.timeIns[0]);
                            const timeOut = new Date(item.timeOuts[0]);
                            const workMinutes = (timeOut - timeIn) / 60000; // convert ms to minutes

                            if (!cacheObj[key]) cacheObj[key] = {};
                            if (!cacheObj[key][dateStr]) {
                                cacheObj[key][dateStr] = {
                                    totals: {
                                        totalQty: 0,
                                        totalCycleTime: 0,
                                        entryCount: 0,
                                        avgCycleTime: 0,
                                        totalWorkingTime: 0
                                    }
                                };
                            }

                            const entry = cacheObj[key][dateStr].totals;

                            entry.totalQty += item.totalFinished || 0;
                            entry.totalCycleTime += item.cycle_time || 0;
                            entry.totalWorkingTime += isFinite(workMinutes) ? workMinutes * 60 : 0;
                            entry.entryCount += 1;
                            entry.avgCycleTime = +(entry.totalCycleTime / entry.entryCount).toFixed(2);
                        });

                        return cacheObj;
                    }


                    const filterButton = document.getElementById('open-filter-modal');

                    if (filterButton) {
                        filterButton.addEventListener('click', () => {
                            const sectionMonthDailyAvgCache = compileSectionMonthDailyAvg(filteredData);
                            openSectionMonthModal(sectionMonthDailyAvgCache, (selected) => {
                                console.log('✅ User selected:', filteredData);
                            });
                        });
                    }


                    document.getElementById('data-body').addEventListener('click', function(event) {
                        const groupRow = event.target.closest('.group-header');
                        if (!groupRow) return;

                        const batch = groupRow.getAttribute('data-batch');
                        const safeBatch = batch.replace(/\s+/g, '_');
                        const rows = document.querySelectorAll(`.batch-group-${safeBatch}`);

                        if (rows.length === 0) return;

                        const isVisible = rows[0].style.display !== 'none';
                        rows.forEach(row => {
                            row.style.display = isVisible ? 'none' : '';
                        });

                        groupRow.querySelector('td').innerHTML = `${isVisible ? '▶️' : '🔽'} Section: ${batch}`;
                    });

                    // ✅ Setup search + date filter logic
                    let latestSearchFiltered = [...roleFilteredData];

                    function applyCombinedFilters() {
                        const dateFiltered = applyDateRangeFilter(latestSearchFiltered); // filter after search
                        filteredData = dateFiltered;
                        renderPageCallback(filteredData);
                    }

                    // Setup search
                    setupSearchFilter({
                        filterColumnSelector: '#filter-column',
                        filterInputSelector: '#filter-input',
                        data: roleFilteredData,
                        onFilter: filtered => {
                            currentFilterQuery = document.querySelector('#filter-input')?.value.toLowerCase() || '';
                            latestSearchFiltered = filtered;
                            applyCombinedFilters();
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


                    // Setup date filter listeners
                    [fromDateInput, toDateInput].forEach(input =>
                        input.addEventListener('change', () => {
                            applyCombinedFilters(); // reuse logic
                        })
                    );



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

        /* Tablet only: horizontal scroll + sticky first columns */
        @media (max-width: 991.98px) {

            .table-wrapper {
                overflow-x: auto;
                width: 100%;
            }

            .custom-hover {
                min-width: 1200px;
                /* ensures scroll */
                table-layout: fixed;
            }

            .custom-hover th,
            .custom-hover td {
                white-space: nowrap;
                font-size: 0.85rem;
                padding: 12px 12px;
            }

            /* Sticky first column (checkbox or icon) */
            .custom-hover th:nth-child(1),
            .custom-hover td:nth-child(1) {
                position: sticky;
                left: 0;
                background: #f9f9f9;
                z-index: 10;
                width: 100px;
            }

            /* Sticky second column (Date) */
            .custom-hover th:nth-child(2),
            .custom-hover td:nth-child(2) {
                position: sticky;
                left: 100px;
                /* width of first column */
                background: #f9f9f9;
                z-index: 10;
                min-width: 120px;
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