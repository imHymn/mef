<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
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
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="card-title mb-0">Direct OK (Individual)</h6>
            <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
          </div>

          <div class="row mb-3 align-items-end ">

            <div class="col-md-3 d-flex ">

              <input type="text" id="filter-input" class="form-control" placeholder="Type to filter..." />
            </div>
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

          <div class="table-responsive-wrapper">
            <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
              <thead>
                <tr>

                  <th style="width: 7%; text-align: center;">Date <span class="sort-icon"></span></th>
                  <th style="width: 15%; text-align: center;">Material Description <span class="sort-icon"></span></th>
                  <th style="width: 10%; text-align: center;">Person Incharge <span class="sort-icon"></span></th>
                  <th style="width: 10%; text-align: center;">Lot <span class="sort-icon"></span></th>
                  <th style="width: 7%; text-align: center;">Total Quantity<span class="sort-icon"></span></th>
                  <th style="width: 7%; text-align: center;">Good <span class="sort-icon"></span></th>
                  <th style="width: 7%; text-align: center;">No Good <span class="sort-icon"></span></th>
                  <th style="width: 10%; text-align: center;">Direct OK <span class="sort-icon"></span></th>
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
  <div class="modal fade" id="inspectionModal" tabindex="-1" aria-labelledby="inspectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="inspectionModalLabel">Inspection Input</h5>

        </div>
        <div class="modal-body">
          <form id="inspectionForm">
            <input type="hidden" id="inspectionModeHidden" />

            <div class="mb-3">
              <label for="goodQty" class="form-label">Good</label>
              <input type="number" class="form-control" id="goodQty" required>
            </div>
            <div class="mb-3">
              <label for="notGoodQty" class="form-label">No Good</label>
              <input type="number" class="form-control" id="notGoodQty" required>
            </div>

            <!-- Hidden inputs -->
            <input type="hidden" id="recordIdHidden">
            <input type="hidden" id="totalQtyHidden">

            <div id="errorMsg" class="text-danger"></div>

            <!-- Rework and Replace section (hidden by default) -->
            <div id="followUpSection" style="display:none; margin-top:1rem;">
              <hr>
              <h6>Rework / Replace Input</h6>
              <div class="mb-3">
                <label for="rework" class="form-label">Rework</label>
                <input type="number" class="form-control" id="rework" value="0" min="0">
              </div>
              <div class="mb-3">
                <label for="replace" class="form-label">Replace</label>
                <input type="number" class="form-control" id="replace" value="0" min="0">
              </div>
              <div id="followUpErrorMsg" class="text-danger"></div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeInspectionModal()">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="submitInspection()">Submit</button>
        </div>


      </div>
    </div>
  </div>
  <script src="assets/js/sweetalert2@11.js"></script>


  <script>
    let inspectionModalInstance = null;
    const userRole = "<?= $role ?>";
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;

    console.log(userRole, userProduction, userProductionLocation);
    let model = null;


    function formatHoursMinutes(decimalHours) {
      const hours = Math.floor(decimalHours);
      const minutes = Math.round((decimalHours - hours) * 60);
      return `${hours} hrs${minutes > 0 ? ' ' + minutes + ' mins' : ''}`;
    }

    function extractDateOnly(datetimeStr) {
      return datetimeStr ? datetimeStr.slice(0, 10) : '';
    }
    const filterColumn = document.getElementById('filter-column');
    const filterInput = document.getElementById('filter-input');
    const tbody = document.getElementById('data-body');
    const fromDateInput = document.querySelector('#from-date');
    const toDateInput = document.querySelector('#to-date');

    let mergedDataArray = [];
    let filteredData = [];
    let paginator;

    function extractDateOnly(datetimeStr) {
      return datetimeStr ? datetimeStr.slice(0, 10) : '';
    }

    function submitInspection() {
      const id = document.getElementById('recordIdHidden').value;
      const totalQty = parseInt(document.getElementById('totalQtyHidden').value) || 0;
      const good = parseInt(document.getElementById('goodQty').value) || 0;
      const noGood = parseInt(document.getElementById('notGoodQty').value) || 0;
      const rework = parseInt(document.getElementById('rework').value) || 0;
      const replace = parseInt(document.getElementById('replace').value) || 0;

      const errorMsg = document.getElementById('errorMsg');
      const followUpErrorMsg = document.getElementById('followUpErrorMsg');

      // Validate total
      if (good + noGood > totalQty) {
        errorMsg.textContent = `Total must not exceed ${totalQty}`;
        return;
      } else {
        errorMsg.textContent = '';
      }

      // Validate rework + replace = noGood (if needed)
      if (noGood > 0 && (rework + replace !== noGood)) {
        followUpErrorMsg.textContent = `Rework + Replace must equal No Good (${noGood})`;
        return;
      } else {
        followUpErrorMsg.textContent = '';
      }

      // Hide modal
      inspectionModalInstance.hide();

      // Call reset with values
      handleRefresh(id, good, noGood, rework, replace);
    }

    function handleRefresh(id, good = 0, noGood = 0, rework = 0, replace = 0) {
      Swal.fire({
        title: 'Supervisor Authorization Required',
        html: `
      <p>This will reset data for Material No: <strong>${id}</strong></p>
      <input type="password" id="supervisor-code" class="swal2-input" placeholder="Enter Supervisor Authorization Code">
    `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, reset it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
          popup: 'swal-sm' // add your custom CSS class
        },
        preConfirm: () => {
          const code = document.getElementById('supervisor-code').value.trim();
          if (!code) {
            Swal.showValidationMessage('Authorization code is required');
            return false;
          }
          return {
            code
          };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const supervisorCode = result.value.code;

          fetch('api/qc/reset_manpower', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                id,
                auth_code: supervisorCode,
                production_location: userProductionLocation,
                production: userProduction,
                role: userRole,
                good,
                no_good: noGood,
                rework,
                replace
              })
            })
            .then(res => res.json())
            .then(json => {
              if (json.success) {
                showAlert('success', 'Reset!', 'Data has been reset.');
              } else {
                showAlert('error', 'Error', json.message || 'Authorization failed.');
              }
            })
            .catch(err => {
              console.error(err);
              showAlert('error', 'Error', 'Something went wrong.');
            });

        }
      });
    }


    function openInspectionModal(id, totalQty) {

      console.log(id, totalQty)
      document.getElementById('recordIdHidden').value = id;
      document.getElementById('totalQtyHidden').value = totalQty;

      document.getElementById('inspectionForm').reset();
      document.getElementById('errorMsg').textContent = '';
      document.getElementById('followUpErrorMsg').textContent = '';
      document.getElementById('followUpSection').style.display = 'none';

      const modalEl = document.getElementById('inspectionModal');
      inspectionModalInstance = new bootstrap.Modal(modalEl);
      inspectionModalInstance.show();

      const goodInput = document.getElementById('goodQty');
      const noGoodInput = document.getElementById('notGoodQty');

      function validateInputs() {
        const good = parseInt(goodInput.value) || 0;
        const noGood = parseInt(noGoodInput.value) || 0;
        const total = parseInt(totalQty) || 0;

        if (good + noGood > total) {
          document.getElementById('errorMsg').textContent = `Total must not exceed ${total}`;
        } else {
          document.getElementById('errorMsg').textContent = '';
        }

        document.getElementById('followUpSection').style.display = noGood > 0 ? 'block' : 'none';
      }

      goodInput.removeEventListener('input', validateInputs);
      noGoodInput.removeEventListener('input', validateInputs);
      goodInput.addEventListener('input', validateInputs);
      noGoodInput.addEventListener('input', validateInputs);
    }


    function closeInspectionModal() {
      if (inspectionModalInstance) {
        inspectionModalInstance.hide();
      }
    }

    function renderPageCallback(pageData, cycleTimes) {
      tbody.innerHTML = '';
      let qc_targetMpeff = localStorage.getItem('QC_TARGETMPEFF');
      let targetMPEFF = parseFloat(qc_targetMpeff) || 0;
      pageData.forEach(entry => {
        const firstIn = new Date(Math.min(...entry.timeIns.map(t => t.getTime())));
        const lastOut = new Date(Math.max(...entry.timeOuts.map(t => t.getTime())));

        const spanSeconds = (lastOut - firstIn) / 1000;
        const workSeconds = entry.totalWorkMinutes * 60;
        const standbySeconds = spanSeconds - workSeconds;

        const totalQty = entry.totalFinished;
        const timePerUnit = totalQty > 0 ? (workSeconds / totalQty) : 0;

        const materialNo = entry.material_no?.toString?.().trim() || '';
        const targetCycleTime = parseFloat(cycleTimes?.[materialNo] || 0);

        const mpeff = targetCycleTime && workSeconds > 0 ?
          ((targetCycleTime * totalQty) / workSeconds) * 100 :
          0;


        const efficiencyColor = mpeff >= targetMPEFF ? 'green' : 'red';

        const row = document.createElement('tr');
        row.innerHTML = `
      <!--<td style="text-align: center;">
        <span style="cursor: pointer; margin-left: 8px;" onclick="openInspectionModal('${entry.id}','${entry.good + entry.no_good}')">🔄</span>
      </td>-->
      <td style="text-align: center;">${highlightText(extractDateOnly(entry.created_at), currentFilterQuery)}</td>
      <td style="text-align: center; white-space: normal; word-wrap: break-word;">${highlightText(entry.material_description, currentFilterQuery) || '-'}</td>
      <td style="text-align: center;white-space: normal; word-wrap: break-word;">${highlightText(entry.person, currentFilterQuery)}</td>
      <td style="text-align: center;">${highlightText(entry.variant, currentFilterQuery) || '-'} - ${highlightText(entry.lot, currentFilterQuery) || '-'}</td>
      <td style="text-align: center;">${highlightText((entry.good + entry.no_good).toString(), currentFilterQuery)}</td>
      <td style="text-align: center;">${highlightText(entry.good.toString(), currentFilterQuery)}</td>
      <td style="text-align: center;">${highlightText(entry.no_good.toString(), currentFilterQuery)}</td>
      <td style="text-align: center; color:${efficiencyColor};">${((entry.good / (entry.good + entry.no_good)) * 100).toFixed(2)}%</td>
    `;

        tbody.appendChild(row);
      });

      document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
    }

    function getData(model) {
      return Promise.all([
          fetch(`api/qc/getAllQCData?model=${encodeURIComponent(model)}`).then(res => res.json())
        ])
        .then(([data]) => {
          const mergedData = {};
          qcData = data.qc;
          reworkData = data.rework;

          function addEntry(id, person, date, reference, timeIn, timeOut, finishedQty, source = 'qc', material_no = '', material_description = '', good = 0, no_good = 0, lot = '', model = '', created_at = '', variant = '') {
            const key = `${person}_${date}_${material_description}_${lot}_${source}`;

            if (!mergedData[key]) {
              mergedData[key] = {
                id,
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
                variant
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

          // Process QC data
          qcData.forEach(item => {
            if (!item.time_in || !item.time_out || !item.person_incharge || !item.reference_no || !item.created_at) return;

            const day = extractDateOnly(item.time_in || item.created_at);
            const finishedQty = parseInt(item.done_quantity) || 0;
            const material_no = item.material_no || '';
            const material_description = item.material_description || '';
            const good = parseInt(item.good) || 0;
            const no_good = parseInt(item.no_good) || 0;
            const lot = item.lot_no || '';
            const model = item.model || '';
            const variant = item.variant || ''; // ✅ ADD THIS

            addEntry(item.id, item.person_incharge, day, item.reference_no, item.time_in, item.time_out, finishedQty, 'qc', material_no, material_description, good, no_good, lot, model, item.created_at, variant);
          });

          // Process Rework data
          reworkData.forEach(item => {
            if (!item.qc_timein || !item.qc_timeout || !item.qc_person_incharge || !item.reference_no || !item.created_at) return;

            const day = extractDateOnly(item.time_in || item.created_at);
            const finishedQty = parseInt(item.good) || 0;
            const material_no = item.material_no || '';
            const material_description = item.material_description || '';
            const good = parseInt(item.good) || 0;
            const no_good = parseInt(item.no_good) || 0;
            const lot = item.lot_no || '';
            const model = item.model || '';
            const variant = item.variant || ''; // ✅ ADD THIS

            addEntry(item.id, item.qc_person_incharge, day, item.reference_no, item.qc_timein, item.qc_timeout, finishedQty, 'rework', material_no, material_description, good, no_good, lot, model, item.created_at, variant);
          });

          // Finalize merged data
          mergedDataArray = Object.values(mergedData);
          filteredData = [...mergedDataArray]; // For future filters

          paginator = createPaginator({
            data: filteredData,
            rowsPerPage: 10,
            renderPageCallback: (page) => renderPageCallback(page),
            paginationContainerId: 'pagination'
          });

          paginator.render();

          // Date filter logic
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
              currentFilterQuery = document.getElementById('filter-input')?.value.toLowerCase() || '';
              filteredData = filtered; // save for date filter to use
              const dateFiltered = applyDateRangeFilter(filtered);
              paginator.setData(dateFiltered);
            },
            customColumnHandler: {
              created_at: item => extractDateOnly(item.created_at),
              material_description: item => item.material_description || '-',
              person: item => item.person || '-',
              variant: item => item.variant || '-',
              lot: item => item.lot || '-',
              total: item => (item.good + item.no_good).toString(),
              good: item => item.good.toString(),
              no_good: item => item.no_good.toString(),

            }
          });


        })
        .catch(console.error);
    }
    // Optional: initialize sorting
    enableTableSorting(".table");
  </script>
  <style>
    /* Default (desktop & mobile): table fits normally */
    .table-responsive-wrapper {
      width: 100%;
    }

    /* Enable horizontal scroll + custom widths only on tablet */
    @media (min-width: 768px) and (max-width: 991.98px) {
      .table-responsive-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        position: relative;
      }

      .table-responsive-wrapper table {
        min-width: 1000px;
        /* force scroll if too wide */
        table-layout: fixed;
      }

      /* Adjust column widths */
      .table-responsive-wrapper th:nth-child(1),
      .table-responsive-wrapper td:nth-child(1) {
        width: 50px !important;
        /* Date */
      }

      .table-responsive-wrapper th:nth-child(2),
      .table-responsive-wrapper td:nth-child(2) {
        width: 100px !important;
        /* Date */
      }

      .table-responsive-wrapper th:nth-child(3),
      .table-responsive-wrapper td:nth-child(3) {
        width: 250px !important;
        /* Material Description (wider) */
      }

      .table-responsive-wrapper th:nth-child(4),
      .table-responsive-wrapper td:nth-child(4) {
        width: 180px !important;
        /* Person Incharge */
      }

      .table-responsive-wrapper th:nth-child(5),
      .table-responsive-wrapper td:nth-child(5) {
        width: 100px !important;
        /* Lot (kept smaller) */
      }

      .table-responsive-wrapper th:nth-child(6),
      .table-responsive-wrapper td:nth-child(6),
      .table-responsive-wrapper th:nth-child(7),
      .table-responsive-wrapper td:nth-child(7),
      .table-responsive-wrapper th:nth-child(8),
      .table-responsive-wrapper td:nth-child(8) {
        width: 120px !important;
        /* Quantities */
      }

      .table-responsive-wrapper th:nth-child(9),
      .table-responsive-wrapper td:nth-child(9) {
        width: 180px !important;
        /* Direct OK */
      }
    }
  </style>