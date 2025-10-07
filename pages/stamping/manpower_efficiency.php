<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<script src="assets/js/html5.qrcode.js"></script>
<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>



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
            <h6 class="card-title mb-0">Manpower Efficiency Data</h6>
            <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
          </div>
          <div class="row mb-3 align-items-end g-2">
            <!-- Left Section: Column Select + Search -->
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

          <div class="table-wrapper">
            <table class="custom-hover table">
              <thead>
                <tr>
                  <th style="text-align: center;">Date</th>
                  <th style="text-align: center;">Component Name</th>
                  <th style="text-align: center;">Person Incharge</th>
                  <th style="text-align: center;">Process</th>
                  <th>Quantity</th>
                  <th>Target CT(Piece)</th>
                  <th>Actual CT(Piece)</th>
                  <th>Total Target CT</th>
                  <th>Total Actual CT</th>
                  <th>MPEFF</th>
                </tr>
              </thead>
              <tbody id="data-body">
                <!-- table rows here -->
              </tbody>
            </table>
          </div>


          <div id="pagination" class="mt-3 d-flex justify-content-center"></div>


        </div>
      </div>
    </div>
  </div>
  <script>
    let fullData = [];
    let paginator;
    let stamping_targetmpeff = {};
    let targetMpeff = '';

    const userRole = "<?= $role ?>";
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;

    const dataBody = document.getElementById('data-body');
    let model = null;

    function renderTable(data, page = 1) {
      const merged = {};
      targetMpeff = localStorage.getItem('STAMPING_TARGETMPEFF');

      data.forEach(item => {
        const personInCharge = item.person_incharge ?? item.stamping_person_incharge ?? '';
        const timeInRaw = item.time_in ?? item.stamping_timein ?? null;
        const timeOutRaw = item.time_out ?? item.stamping_timeout ?? null;
        const finishedQty = parseInt(item.process_quantity ?? item.stamping_quantity) || 0;
        const pendingQty = parseInt(item.pending_quantity ?? item.stamping_pending_quantity) || 0;
        const totalQty = parseInt(item.quantity) || 0;
        const stageName = item.stage_name ?? 'REWORK';
        const componentsName = item.components_name ?? item.material_description ?? '';
        const cycle_time = item.cycle_time ?? item.cycle_time ?? '';
        const createdDate = item.created_at?.split(' ')[0] ?? '';
        let groupingDate = '';
        if (timeOutRaw) {
          groupingDate = timeOutRaw.split(' ')[0]; // use time_out first if available
        } else if (timeInRaw) {
          groupingDate = timeInRaw.split(' ')[0]; // otherwise fallback to time_in
        } else {
          groupingDate = item.created_at?.split(' ')[0] ?? ''; // last fallback
        }

        const key = `${item.section}_${stageName}_${personInCharge}_${groupingDate}_${componentsName}`;

        if (!personInCharge || !groupingDate) return;

        if (!merged[key]) {
          merged[key] = {
            person: personInCharge,
            section: item.section,
            stage_name: stageName,
            date: groupingDate,
            material_no: item.material_no,
            components_name: componentsName,
            totalFinished: 0,
            totalQuantity: 0,
            pendingQuantity: 0,
            timeIns: [],
            timeOuts: [],
            totalWorkMinutes: 0,
            references: new Set(),
            stage_names: new Set(),
            cycle_time
          };
        }

        const group = merged[key];
        group.totalFinished += finishedQty;
        group.totalQuantity += totalQty;
        group.pendingQuantity += pendingQty;

        const timeIn = timeInRaw ? new Date(timeInRaw) : null;
        const timeOut = timeOutRaw ? new Date(timeOutRaw) : null;

        if (stageName) group.stage_names.add(stageName);
        if (timeIn && timeOut && timeOut > timeIn && finishedQty > 0) {
          group.totalWorkMinutes += (timeOut - timeIn) / (1000 * 60);
          group.timeIns.push(timeIn);
          group.timeOuts.push(timeOut);
        }

        group.references.add(item.reference_no);
      });

      dataBody.innerHTML = '';
      const groupedBySection = {};
      const normalize = str => String(str ?? '').toLowerCase().replace(/[\s-]/g, '');

      // Normalize userProduction and userProductionLocation (array or string)
      const userProductionNormalized = Array.isArray(userProduction) ?
        userProduction.map(p => normalize(p)) : [normalize(userProduction)];

      const userProductionLocationNormalized = Array.isArray(userProductionLocation) ?
        userProductionLocation.map(loc => normalize(loc)) : [normalize(userProductionLocation)];

      // Loop through merged data
      Object.values(merged).forEach(group => {
        const sectionNormalized = normalize(group.section);

        const canAccess =
          userRole === 'administrator' ||
          (userProductionNormalized.includes('stamping') &&
            userProductionLocationNormalized.includes(sectionNormalized));

        if (!canAccess) return;

        if (!groupedBySection[group.section]) {
          groupedBySection[group.section] = [];
        }
        groupedBySection[group.section].push(group);
      });


      Object.entries(groupedBySection).forEach(([section, groups]) => {
        const normalizedSection = section.toUpperCase();
        if (normalizedSection === 'L300 ASSY' || normalizedSection === 'FINISHING') return;
        const displaySectionName = section.toUpperCase() === 'STAMPING' ? 'FINISHING' : section.toUpperCase();

        const sectionRow = document.createElement('tr');
        sectionRow.innerHTML = `
  <td colspan="10" style="background: #f0f0f0; font-weight: bold; text-align: left; padding: 8px;">
    Section: ${displaySectionName}
  </td>`;

        dataBody.appendChild(sectionRow);
        console.log(groups)
        groups.forEach(group => {
          if (group.timeIns.length === 0 || group.timeOuts.length === 0) return;
          if (group.section === 'L300 ASSY' || group.section === 'FINISHING') return;

          const firstIn = new Date(Math.min(...group.timeIns.map(d => d.getTime())));
          const lastOut = new Date(Math.max(...group.timeOuts.map(d => d.getTime())));
          const spanMinutes = (lastOut - firstIn) / (1000 * 60);
          const standbyMinutes = spanMinutes - group.totalWorkMinutes;

          const totalWorkSeconds = group.totalWorkMinutes * 60;
          const standbySeconds = standbyMinutes * 60;

          const targetCycleTime = parseFloat(group.cycle_time) || 0;



          const actualCycleTime = group.totalQuantity > 0 ?
            (totalWorkSeconds / group.totalQuantity) :
            0;

          const mpeff = (targetCycleTime > 0 && group.totalQuantity > 0 && totalWorkSeconds > 0) ?
            ((targetCycleTime * group.totalQuantity) / totalWorkSeconds) * 100 :
            null;

          const row = document.createElement('tr');
          row.innerHTML = `
<td style="text-align:center;">${highlightText(group.date, currentFilterQuery)}</td>
<td style="text-align:center;white-space:normal;">${highlightText(group.components_name, currentFilterQuery)}</td>
<td style="text-align:center;">${highlightText(group.person, currentFilterQuery)}</td>
<td style="text-align:center;white-space:normal;">${highlightText(group.stage_name, currentFilterQuery)}</td>
<td style="text-align:center;">${highlightText(group.totalQuantity, currentFilterQuery)}</td>
<td style="text-align:center;">${highlightText(targetCycleTime, currentFilterQuery)}s</td>
<td style="text-align:center;">${highlightText(Math.ceil(actualCycleTime), currentFilterQuery)}s</td>
<td style="text-align:center;">${highlightText(group.totalQuantity * targetCycleTime, currentFilterQuery)}s</td>
<td style="text-align:center;">${highlightText(Math.ceil(totalWorkSeconds), currentFilterQuery)}s</td>


<td style="text-align: center; color: ${mpeff !== null && targetMpeff !== null ? (mpeff > targetMpeff ? 'green' : 'red') : 'inherit'};">
  ${mpeff !== null
    ? `${mpeff.toFixed(1)}% 
        ${
          mpeff > targetMpeff
            ? '<span style="color: green;">▲</span>'
            : mpeff < targetMpeff
              ? '<span style="color: red;">▼</span>'
              : ''
        }`
    : '-'}
</td>
`;

          if (targetCycleTime === 0) {
            row.style.backgroundColor = '#ffe6e6';
            row.title = '⚠️ Missing or unmatched cycle time';
          }

          dataBody.appendChild(row);
        });
      });

      const now = new Date();
      document.getElementById('last-updated').textContent = `Last updated: ${now.toLocaleString()}`;
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
          rowsPerPage: 20,
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
            applyDateFilter();
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
    /* Optional hover effect (always active) */
    .custom-hover tbody tr:hover {
      background-color: #dde0e2ff !important;
      /* light blue */
    }

    /* Apply sticky header & frozen columns ONLY on tablets and below */
    @media (max-width: 991.98px) {
      .custom-hover tbody tr:hover td {
        background-color: #dde0e2 !important;
      }

      .table-wrapper {
        overflow-x: auto;
        /* horizontal scroll */
      }

      /* Sticky header */
      .custom-hover thead th {
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 100;
        /* header on top */
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.2);
        text-align: center;
        padding: 8px;
      }

      /* Sticky first 4 columns */
      .custom-hover th:nth-child(1),
      .custom-hover td:nth-child(1) {
        position: sticky;
        left: 0;
        width: 92px;
        /* match your left offset */
        background: #f9f9f9;
        z-index: 101;
      }

      .custom-hover th:nth-child(2),
      .custom-hover td:nth-child(2) {
        position: sticky;
        left: 92px;
        /* exactly previous column's width */
        width: 126px;
        /* set fixed width */
        background: #f9f9f9;
        z-index: 101;
      }

      .custom-hover th:nth-child(3),
      .custom-hover td:nth-child(3) {
        position: sticky;
        left: 218px;
        /* sum of first + second column widths */
        width: 154px;
        /* fixed width */
        background: #f9f9f9;
        z-index: 101;
      }

      .custom-hover th:nth-child(4),
      .custom-hover td:nth-child(4) {
        position: sticky;
        left: 372px;
        /* sum of first + second + third */
        width: 140px;
        background: #f9f9f9;
        z-index: 101;
      }


      /* Font adjustments for tablet */
      .custom-hover th,
      .custom-hover td {
        font-size: 0.85rem;
        padding: 6px 8px;
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