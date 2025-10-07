<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<script src="assets/js/html5.qrcode.js"></script>
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
<div class="page-content">
  <nav class="page-breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="#">Pages</a></li>
      <li class="breadcrumb-item" aria-current="page">Stamping Section</li>
    </ol>
  </nav>

  <div class="row">
    <div class="col-md-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="card-title mb-0">Sectional Efficiency Data</h6>
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
            <table class="custom-hover table" style="table-layout: fixed; min-width: 1200px; width: 100%;">
              <thead>
                <tr style="text-align:center;">
                  <th>Date</th>
                  <th>Section</th>
                  <th>Quantity</th>
                  <th>Target CT(Piece)</th>
                  <th>Actual CT(Piece)</th>
                  <th>Total Target CT</th>
                  <th>Total Actual CT</th>
                  <th>MPEFF</th>
                </tr>
              </thead>
              <tbody id="data-body">
                <!-- Table rows -->
              </tbody>
            </table>
          </div>

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
    let fullData = [];
    let paginator;
    let cycleTimes = {};

    let targetMpeff = '';
    let globalSection = null;
    const userRole = "<?= $role ?>";
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;

    const dataBody = document.getElementById('data-body');
    let sectionMonthDailyAvgCache = {}; // ⬅️ Global cache
    let mergedData = []; // Optional: If you want to filter and re-render later
    let model = null;

    function renderTable(data, page = 1) {
      const normalize = str =>
        typeof str === 'string' ? str.toLowerCase().replace(/[\s_-]/g, '') : '';

      const merged = {};
      targetMpeff = localStorage.getItem('STAMPING_TARGETMPEFF');

      // 🔄 Normalize userProduction and userProductionLocation
      const userProductionNormalized = Array.isArray(userProduction) ?
        userProduction.map(p => normalize(p)) : [normalize(userProduction)];

      const userProductionLocationNormalized = Array.isArray(userProductionLocation) ?
        userProductionLocation.map(loc => normalize(loc)) : [normalize(userProductionLocation)];

      data.forEach(item => {
        const person = item.person_incharge ?? item.stamping_person_incharge ?? '';
        const section = item.section;
        const rawTimeout = item.time_out;
        const date = rawTimeout?.split(' ')[0] || '';

        const stage = item.stage_name ?? 'REWORK';
        const component = item.components_name ?? item.material_description ?? '';
        const materialNo = item.material_no;
        const qty = parseInt(item.quantity) || 0;
        const timeIn = item.time_in ?? item.stamping_timein ?? null;
        const timeOut = item.time_out ?? item.stamping_timeout ?? null;
        const itemCycleTime = parseFloat(item.cycle_time) || 0;

        if (!section || !date || !person) return;

        const sectionKeyNormalized = normalize(section);

        // ✅ Access check
        const canAccess =
          userRole === 'administrator' ||
          (userProductionNormalized.includes('stamping') &&
            userProductionLocationNormalized.includes(sectionKeyNormalized));

        if (!canAccess) return;

        const key = `${section}_${date}`;
        if (!merged[key]) {
          merged[key] = {
            section,
            date,
            totalQty: 0,
            totalCycleSecs: 0,
            totalWorkSecs: 0,
            debug: []
          };
        }

        let workSecs = 0;
        if (timeIn && timeOut) {
          const timeInDate = new Date(timeIn);
          const timeOutDate = new Date(timeOut);
          if (timeOutDate > timeInDate) {
            workSecs = (timeOutDate - timeInDate) / 1000;
          }
        }

        const group = merged[key];

        const actualCycleTime = qty > 0 ? workSecs / qty : 0;
        const mpeff = (workSecs > 0 && itemCycleTime > 0) ?
          ((qty * itemCycleTime) / workSecs) * 100 :
          0;

        // Accumulate values
        group.totalQty += qty;
        group.totalCycleSecs += qty * itemCycleTime;
        group.totalWorkSecs += workSecs;

        group.debug.push({
          person,
          component,
          stage,
          qty,
          targetCT: itemCycleTime,
          actualCycleTime,
          workSecs,
          mpeff
        });
      });

      // Convert merged to array with calculated averages
      mergedData = Object.values(merged).map(group => {
        const actualCycleTime = group.totalQty > 0 ? group.totalWorkSecs / group.totalQty : 0;
        const avgTargetCT = group.totalQty > 0 ? group.totalCycleSecs / group.totalQty : 0;
        const mpeff = group.totalWorkSecs > 0 ?
          (group.totalCycleSecs / group.totalWorkSecs) * 100 :
          0;

        return {
          ...group,
          targetCT: avgTargetCT,
          actualCycleTime,
          mpeff
        };
      });

      // 🔄 Group by Month + Section
      const groupedByMonthSection = {};
      sectionMonthDailyAvgCache = {}; // Reset cache

      mergedData.forEach(group => {
        const date = new Date(group.date);
        const dateKey = group.date;
        const month = date.getMonth() + 1;
        const year = date.getFullYear();
        const monthKey = `${year}-${String(month).padStart(2, '0')}`;
        const sectionKey = `${group.section}__${monthKey}`;

        // Table grouping label
        const monthLabel = date.toLocaleString('default', {
          month: 'long',
          year: 'numeric'
        });
        const groupKey = `${monthLabel}__${group.section}`;

        if (!groupedByMonthSection[groupKey]) {
          groupedByMonthSection[groupKey] = {
            month: monthLabel,
            section: group.section,
            groups: []
          };
        }
        groupedByMonthSection[groupKey].groups.push(group);

        if (!sectionMonthDailyAvgCache[sectionKey]) {
          sectionMonthDailyAvgCache[sectionKey] = {
            section: group.section,
            year,
            month,
            data: {}
          };
        }
        sectionMonthDailyAvgCache[sectionKey].data[dateKey] = {
          totals: {
            totalQty: group.totalQty,
            totalCycleTime: group.totalCycleSecs,
            totalWorkingTime: group.totalWorkSecs,
            entryCount: group.debug.length,
            avgCycleTime: group.actualCycleTime
          }
        };
      });

      renderMonthlySectionTable(groupedByMonthSection);

      document.getElementById('last-updated').textContent =
        `Last updated: ${new Date().toLocaleString()}`;
    }

    function renderMonthlySectionTable(grouped) {
      dataBody.innerHTML = '';

      Object.values(grouped).forEach((wrapper, index) => {
        const groupClass = `group_${index}`;
        const toggleId = `toggle_${index}`;

        // Init totals
        let monthlyTotalQty = 0;
        let monthlyTotalCT = 0;
        let monthlyTotalWT = 0;
        let monthlyTargetCT = 0;
        let monthlyActualCT = 0;
        let monthlyMPEFF = 0;
        let mpeffCount = 0;

        // Compute totals first
        wrapper.groups.forEach(group => {
          monthlyTotalQty += group.totalQty;
          monthlyTotalCT += group.totalCycleSecs;
          monthlyTotalWT += group.totalWorkSecs;
          monthlyTargetCT += group.targetCT;
          monthlyActualCT += group.actualCycleTime;
          if (group.mpeff !== null && !isNaN(group.mpeff)) {
            monthlyMPEFF += group.mpeff;

          }
        });

        let count = wrapper.groups[0].debug.length;
        const avgTargetCT = monthlyTargetCT / count;
        const avgActualCT = monthlyTotalWT / monthlyTotalQty;
        const avgMPEFF = monthlyTotalQty * monthlyTargetCT / monthlyTotalWT * 100;

        const toggleRow = document.createElement('tr');
        toggleRow.classList.add('group-header');
        toggleRow.innerHTML = `
<td style="font-weight:bold; background:#f0f0f0; cursor: pointer;text-align:center;" 
    onclick="toggleWrapper('${groupClass}', '${toggleId}')">
  ${highlightText(wrapper.month, currentFilterQuery)}
</td>
<td style="font-weight:bold; background:#f0f0f0;text-align:center;">
  ${highlightText(wrapper.section, currentFilterQuery)}
</td>
<td style="font-weight:bold; background:#f0f0f0;text-align:center;">
  ${highlightText(monthlyTotalQty, currentFilterQuery)}s
</td>
<td style="font-weight:bold; background:#f0f0f0;text-align:center;">
  ${highlightText(monthlyTargetCT, currentFilterQuery)}s
</td>
<td style="font-weight:bold; background:#f0f0f0;text-align:center;">
  ${highlightText(Math.ceil(avgActualCT), currentFilterQuery)}s
</td>
<td style="font-weight:bold; background:#f0f0f0;text-align:center;">
  ${highlightText(monthlyTotalCT, currentFilterQuery)}s
</td>
<td style="font-weight:bold; background:#f0f0f0;text-align:center;">
  ${highlightText(monthlyTotalWT, currentFilterQuery)}s
</td>
<td style="font-weight:bold; background:#f0f0f0;text-align:center;">
  ${highlightText(avgMPEFF.toFixed(2), currentFilterQuery)}%
</td>`;

        dataBody.appendChild(toggleRow);

        //  &nbsp;&nbsp;|&nbsp; 🔢 Qty: ${monthlyTotalQty.toFixed(0)}
        //   &nbsp;&nbsp;|&nbsp; 🎯 CT: ${avgTargetCT.toFixed(2)}s
        //   &nbsp;&nbsp;|&nbsp; ⚙️ ACT: ${avgActualCT.toFixed(2)}s
        //   &nbsp;&nbsp;|&nbsp; 🕒 WT: ${monthlyTotalWT.toFixed(0)}s
        //   &nbsp;&nbsp;|&nbsp; 📈 MPEFF: ${avgMPEFF.toFixed(1)}%
        // Now render individual daily rows
        wrapper.groups.forEach(group => {
          const row = document.createElement('tr');
          row.classList.add(groupClass);

          row.innerHTML = `
<td style="text-align:center;">${highlightText(group.date, currentFilterQuery)}</td>
<td style="text-align:center;">${highlightText(group.section, currentFilterQuery)}</td>
<td style="text-align:center;">${highlightText(group.totalQty, currentFilterQuery)}</td>
<td style="text-align:center;">${highlightText(group.targetCT, currentFilterQuery)}s</td>
<td style="text-align:center;">${highlightText(Math.ceil(group.actualCycleTime), currentFilterQuery)}s</td>
<td style="text-align:center;">${highlightText(group.totalCycleSecs, currentFilterQuery)}s</td>
<td style="text-align:center;">${highlightText(group.totalWorkSecs, currentFilterQuery)}s</td>
<td style="text-align:center; color: ${
  group.mpeff !== null && targetMpeff !== null
    ? (group.mpeff > targetMpeff ? 'green' : 'red')
    : 'inherit'
};">
  ${
    group.mpeff !== null
      ? `${group.mpeff.toFixed(1)}% ${
          group.mpeff > targetMpeff
            ? '<span style="color: green;">▲</span>'
            : group.mpeff < targetMpeff
              ? '<span style="color: red;">▼</span>'
              : ''
        }`
      : '-'
  }
</td>`;

          dataBody.appendChild(row);
        });


      });
    }


    // Filter modal trigger
    document.getElementById('open-filter-modal').addEventListener('click', () => {
      openSectionMonthModal(sectionMonthDailyAvgCache, ({
        section,
        year,
        month
      }) => {
        const key = `${section}__${year}-${String(month).padStart(2, '0')}`;
        const dailyData = sectionMonthDailyAvgCache[key] || {};

        console.log(dailyData);
      });
    });



    function toggleWrapper(groupClass, toggleId) {
      const rows = document.querySelectorAll(`.${groupClass}`);
      const toggleIcon = document.getElementById(toggleId);

      let isVisible = [...rows].some(row => row.style.display !== 'none');

      rows.forEach(row => {
        row.style.display = isVisible ? 'none' : '';
      });

      toggleIcon.textContent = isVisible ? '►' : '▼';
    }

    function getData(model) {
      return Promise.all([
        fetch(`api/stamping/getAllStampingData?model=${encodeURIComponent(model)}`).then(res => res.json())
      ]).then(([manpowerData]) => {


        const grouped = {};
        manpowerData.forEach(item => {
          const ref = item.reference_no ?? '';
          if (!grouped[ref]) grouped[ref] = [];
          grouped[ref].push(item);
        });

        const sorted = Object.values(grouped)
          .flatMap(group => group.sort((a, b) => (+a.stage || 0) - (+b.stage || 0)));

        paginator = createPaginator({
          data: sorted,
          rowsPerPage: 10,
          paginationContainerId: 'pagination',
          renderPageCallback: renderTable
        });

        paginator.render();

        const fromDateInput = document.querySelector('#from-date');
        const toDateInput = document.querySelector('#to-date');
        let lastFilteredData = sorted;

        const applyDateFilter = () => {
          const from = fromDateInput.value;
          const to = toDateInput.value;
          const result = lastFilteredData.filter(row => {
            const createdDate = (row.created_at ?? '').split(' ')[0];
            if (from && createdDate < from) return false;
            if (to && createdDate > to) return false;
            return true;
          });
          paginator.setData(result);
        };

        fromDateInput.addEventListener('change', applyDateFilter);
        toDateInput.addEventListener('change', applyDateFilter);
        setupSearchFilter({
          filterColumnSelector: '#filter-column',
          filterInputSelector: '#filter-input',
          data: sorted,
          onFilter: filtered => {
            lastFilteredData = filtered;
            currentFilterQuery = document.querySelector('#filter-input')?.value.toLowerCase() || '';
            applyDateFilter(); // re-render table with filtered data
          },
          customColumnHandler: {
            person: row => row.person_incharge ?? row.stamping_person_incharge ?? '',
            totalQuantity: row => String(row.quantity ?? ''),
            date: row => (row.created_at ?? '').split(' ')[0],
            section: row => row.section ?? '',
            stage_name: row => row.stage_name ?? 'REWORK',
            working_time: row => row.working_time ?? '',
            target_cycle: row => row.target_cycle ?? '',
            actual_cycle: row => row.actual_cycle ?? ''
          }
        });

      });
    }
  </script>
  <style>
    .table-wrapper {
      width: 100%;
      overflow-x: auto;
      /* horizontal scroll */
    }

    /* Hover effect */
    .custom-hover tbody tr:hover {
      background-color: #dde0e2;
    }

    /* Tablet only */
    @media (max-width: 991.98px) {

      .custom-hover tbody tr:hover td {
        background-color: #dde0e2 !important;
      }

      /* Sticky header */
      .custom-hover thead th {
        position: sticky;
        top: 0;
        background: #ffffff;
        z-index: 30;
        /* header above sticky columns */
        text-align: center;
        padding: 8px 12px;
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.2);
      }

      /* Sticky first 2 columns */
      .custom-hover th:nth-child(1),
      .custom-hover td:nth-child(1) {
        position: sticky;
        left: 0;
        width: 150px;
        /* match the column width */
        min-width: 150px;
        max-width: 150px;
        background: #ffffff;
        z-index: 100;
      }

      .custom-hover th:nth-child(2),
      .custom-hover td:nth-child(2) {
        position: sticky;
        left: 150px;
        /* sum of widths of first column */
        width: 150px;
        /* match column width */
        min-width: 150px;
        max-width: 150px;
        background: #ffffff;
        z-index: 100;
      }

      /* Add shadow for separation */
      .custom-hover th:nth-child(1),
      .custom-hover td:nth-child(1),
      .custom-hover th:nth-child(2),
      .custom-hover td:nth-child(2) {
        box-shadow: 2px 0 5px -2px rgba(0, 0, 0, 0.2);
      }

      /* Font adjustments */
      .custom-hover th,
      .custom-hover td {
        font-size: 0.85rem;
        padding: 8px 12px;
        white-space: nowrap;
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