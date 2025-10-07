<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<?php include './components/reusable/exportQC.php'; ?>
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
            <li class="breadcrumb-item" aria-current="page">Quality Control Section</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between ">
                        <h6 class="card-title mb-0">Direct OK (Sectional)</h6>
                        <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
                    </div>

                    <div class="row mb-3 align-items-end ">

                        <div class="col-md-3 ">
                            <input type="text" id="filter-input" class="form-control" placeholder="Type to filter..." />
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

                    <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 15%; text-align: center;">Date <span class="sort-icon"></span></th>
                                <th style="width: 15%; text-align: center;">Model <span class="sort-icon"></span></th>
                                <!-- <th style="width: 15%; text-align: center;">Material Description <span class="sort-icon"></span></th>
                                <th style="width: 10%; text-align: center;">Person Incharge <span class="sort-icon"></span></th>
                                <th style="width: 10%; text-align: center;">Lot <span class="sort-icon"></span></th> -->

                                <th style="width: 15%; text-align: center;">Total Quantity<span class="sort-icon"></span></th>
                                <th style="width: 15%; text-align: center;">Good <span class="sort-icon"></span></th>
                                <th style="width: 15%; text-align: center;">No Good <span class="sort-icon"></span></th>
                                <th style="width: 10%; text-align: center;">Direct OK <span class="sort-icon"></span></th>
                                <!-- <th style="width: 12%; text-align: center;">Target Cycle Time <span class="sort-icon"></span></th>
                <th style="width: 12%; text-align: center;">Total Cycle Time <span class="sort-icon"></span></th>
                <th style="width: 12%; text-align: center;">Total Working Time <span class="sort-icon"></span></th>

                <th style="width: 8%; text-align: center;">MPEFF <span class="sort-icon"></span></th> -->
                            </tr>
                        </thead>

                        <tbody id="data-body"></tbody>
                    </table>
                    <div id="pagination" class="mt-3 d-flex justify-content-center"></div>
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
        function formatHoursMinutes(decimalHours) {
            const hours = Math.floor(decimalHours);
            const minutes = Math.round((decimalHours - hours) * 60);
            return `${hours} hrs${minutes > 0 ? ' ' + minutes + ' mins' : ''}`;
        }

        let sectionMonthDailyAvgCache = {};
        const filterColumn = document.getElementById('filter-column');
        const filterInput = document.getElementById('filter-input');
        const tbody = document.getElementById('data-body');

        let mergedDataArray = [];
        let filteredData = [];
        let paginator;
        let model = null;

        function extractDateOnly(datetimeStr) {
            return datetimeStr ? datetimeStr.slice(0, 10) : '';
        }

        function renderPageCallback(groupedEntries) {
            const tbody = document.getElementById('data-body');
            tbody.innerHTML = '';
            let qc_targetMpeff = localStorage.getItem('QC_TARGETMPEFF');
            let targetMPEFF = parseFloat(qc_targetMpeff) || 0;
            const currentFilterQuery = document.getElementById('filter-input')?.value.toLowerCase() || '';

            groupedEntries.forEach((group) => {
                const groupKey = group.groupKey;
                const groupEntries = group.entries;

                const [model, month] = groupKey.split('__');

                let totalQty = 0,
                    totalGood = 0,
                    totalNoGood = 0,
                    totalWorkSeconds = 0;

                groupEntries.forEach(entry => {
                    const total = entry.good + entry.no_good;
                    totalQty += total;
                    totalGood += entry.good;
                    totalNoGood += entry.no_good;
                    totalWorkSeconds += entry.totalWorkMinutes * 60;
                });

                const mpeff = (totalGood / totalQty) * 100;
                const efficiencyColor = mpeff >= targetMPEFF ? 'green' : 'red';

                const headerRow = document.createElement('tr');
                headerRow.classList.add('group-header');
                headerRow.style.background = '#ffffff';
                headerRow.innerHTML = `
            <td style="text-align: center;"><b>${highlightText(month, currentFilterQuery)}</b></td>
            <td style="text-align: center;"><b>${highlightText(model, currentFilterQuery)}</b></td>
            <td style="text-align: center;">${highlightText(totalQty, currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(totalGood, currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(totalNoGood, currentFilterQuery)}</td>
            <td style="text-align: center;color:${efficiencyColor};">${highlightText((mpeff.toFixed(2)), currentFilterQuery)}%</td>
        `;
                tbody.appendChild(headerRow);
            });

            document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
        }


        function toggleGroup(groupId) {
            const rows = document.querySelectorAll(`.${groupId}`);
            const visible = Array.from(rows).some(row => row.style.display !== 'none');
            rows.forEach(row => row.style.display = visible ? 'none' : '');
            const header = document.querySelector(`.group-header td[onclick*="${groupId}"]`);
            if (header) {
                header.innerHTML = header.innerHTML.replace(visible ? '▼' : '▲', visible ? '▲' : '▼');
            }
        }

        function getData(model) {
            return Promise.all([
                    fetch(`api/qc/getAllQCData?model=${encodeURIComponent(model)}`).then(res => res.json())
                ])
                .then(([data]) => {
                    const mergedData = {};
                    qcData = data.qc;
                    reworkData = data.rework;

                    function addEntry(person, date, reference, timeIn, timeOut, finishedQty, source = 'qc', material_no = '', material_description = '', good = 0, no_good = 0, lot = '', model = '', created_at = '', assembly_section = '') {
                        const key = `${person}_${date}_${material_description}_${lot}_${source}`;

                        if (!mergedData[key]) {
                            mergedData[key] = {
                                person,
                                date,
                                totalFinished: 0,
                                good: 0,
                                no_good: 0,
                                timeIns: [],
                                timeOuts: [],
                                totalWorkMinutes: 0,
                                material_no,
                                material_description,
                                lot,
                                model,
                                created_at,
                                assembly_section
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
                            group.good += good;
                            group.no_good += no_good;
                        }
                    }

                    qcData.forEach(item => {
                        if (!item.time_in || !item.time_out || !item.person_incharge || !item.reference_no || !item.created_at) return;
                        addEntry(
                            item.person_incharge,
                            extractDateOnly(item.created_at),
                            item.reference_no,
                            item.time_in,
                            item.time_out,
                            parseInt(item.done_quantity) || 0,
                            'qc',
                            item.material_no,
                            item.material_description,
                            parseInt(item.good) || 0,
                            parseInt(item.no_good) || 0,
                            item.lot_no,
                            item.model,
                            item.created_at,
                            item.assembly_section
                        );
                    });

                    reworkData.forEach(item => {
                        if (!item.qc_timein || !item.qc_timeout || !item.qc_person_incharge || !item.reference_no || !item.created_at) return;
                        addEntry(
                            item.qc_person_incharge,
                            extractDateOnly(item.created_at),
                            item.reference_no,
                            item.qc_timein,
                            item.qc_timeout,
                            parseInt(item.good) || 0,
                            'rework',
                            item.material_no,
                            item.material_description,
                            parseInt(item.good) || 0,
                            parseInt(item.no_good) || 0,
                            item.lot_no,
                            item.model,
                            item.created_at,
                            item.assembly_section
                        );
                    });

                    const mergedArray = Object.values(mergedData);
                    mergedDataArray = mergedArray;
                    filteredData = [...mergedDataArray];

                    // ✅ Group by Section + Month
                    const groupedData = {};
                    sectionMonthDailyAvgCache = {};
                    mergedArray.forEach(entry => {
                        const section = (entry.model || 'NO SECTION').toUpperCase();
                        const latestTimeOut = entry.timeOuts.length ? new Date(Math.max(...entry.timeOuts.map(d => d.getTime()))) : new Date();
                        const dateOnly = latestTimeOut.toISOString().slice(0, 10); // YYYY-MM-DD

                        const monthKey = dateOnly.slice(0, 7); // YYYY-MM
                        const groupKey = `${section}__${monthKey}`;

                        if (!groupedData[groupKey]) {
                            groupedData[groupKey] = [];
                        }
                        groupedData[groupKey].push(entry);

                        // Populate sectionMonthDailyAvgCache once
                        sectionMonthDailyAvgCache[groupKey] ??= {};
                        sectionMonthDailyAvgCache[groupKey][dateOnly] ??= {};
                        const matKey = `${entry.material_description || ''}`;
                        sectionMonthDailyAvgCache[groupKey][dateOnly][matKey] ??= {
                            good: 0,
                            no_good: 0,
                            totalWorkMinutes: 0,
                            material_no: entry.material_no,
                            description: entry.material_description,
                            model: entry.model,
                            lot: entry.lot,
                            personSet: new Set()
                        };
                        const cache = sectionMonthDailyAvgCache[groupKey][dateOnly][matKey];
                        cache.good += entry.good;
                        cache.no_good += entry.no_good;
                        cache.totalWorkMinutes += entry.totalWorkMinutes;
                        cache.personSet.add(entry.person);
                    });

                    const groupedArray = Object.entries(groupedData).map(([groupKey, entries]) => ({
                        groupKey,
                        entries
                    }));

                    paginator = createPaginator({
                        data: groupedArray,
                        rowsPerPage: 10,
                        renderPageCallback: renderPageCallback,
                        paginationContainerId: 'pagination'
                    });
                    paginator.render();

                    document.getElementById('open-filter-modal').addEventListener('click', () => {
                        openSectionMonthModal(sectionMonthDailyAvgCache, ({
                            section,
                            year,
                            month
                        }) => {
                            const paddedMonth = String(month).padStart(2, '0');
                            const key = `${section.toUpperCase()}__${year}-${paddedMonth}`;
                            const filtered = sectionMonthDailyAvgCache[key] || {};

                            console.log('🟢 Filtered Daily Data:', key, filtered);

                            // Optional: update view with filtered data
                        });
                    });

                    // Filters
                    function applyDateRangeFilter(data) {
                        const from = document.querySelector('#from-date')?.value;
                        const to = document.querySelector('#to-date')?.value;
                        return data.filter(row => {
                            const rowDate = new Date(row.date);
                            return (!from || rowDate >= new Date(from)) && (!to || rowDate <= new Date(to));
                        });
                    }

                    [document.querySelector('#from-date'), document.querySelector('#to-date')].forEach(input =>
                        input.addEventListener('change', () => {
                            const dateFiltered = applyDateRangeFilter(filteredData);
                            paginator.setData(dateFiltered);
                        })
                    );

                    setupSearchFilter({
                        filterColumnSelector: '#filter-column',
                        filterInputSelector: '#filter-input',
                        data: mergedDataArray,
                        onFilter: filtered => {
                            currentFilterQuery = document.getElementById('filter-input')?.value.toLowerCase() || '';
                            filteredData = filtered;
                            const dateFiltered = applyDateRangeFilter(filtered);
                            paginator.setData(dateFiltered);
                        },
                        customColumnHandler: {
                            month: group => {
                                const groupKey = group.groupKey;
                                return groupKey.split('__')[1] || '';
                            },
                            section: group => {
                                const groupKey = group.groupKey;
                                return groupKey.split('__')[0] || '';
                            },
                            totalQty: group => group.entries.reduce((sum, e) => sum + (e.good + e.no_good), 0),
                            totalGood: group => group.entries.reduce((sum, e) => sum + e.good, 0),
                            totalNoGood: group => group.entries.reduce((sum, e) => sum + e.no_good, 0),
                            efficiency: group => {
                                const totalQty = group.entries.reduce((sum, e) => sum + (e.good + e.no_good), 0);
                                const totalWorkSeconds = group.entries.reduce((sum, e) => sum + (e.totalWorkMinutes * 60), 0);
                                return totalWorkSeconds > 0 && totalQty > 0 ? ((totalQty * 100) / totalWorkSeconds).toFixed(2) : '0';
                            }
                        }
                    });

                })
                .catch(console.error);
        }
        enableTableSorting(".table");
    </script>