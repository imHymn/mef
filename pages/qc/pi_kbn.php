<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/qrcodeScanner.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<?php include './components/reusable/reset_timein.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js" defer></script>

<script src="/mes/components/reusable/applyModelDrawer.js"></script>
<style>

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
            <h6 class="card-title mb-0">Production Instruction Kanban Board</h6>
            <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
          </div>
          <div class="row mb-3 col-md-3">


            <input
              type="text"
              id="filter-input"
              class="form-control"
              placeholder="Type to filter..." />

          </div>

          <div class="table-responsive-wrapper">
            <table class="custom-hover table">
              <thead>
                <tr>
                  <th style="width: 5%; text-align: center;"><span class="sort-icon"></span></th>
                  <th style="width: 10%; text-align: center;">Material No <span class="sort-icon"></span></th>
                  <th style="width: 20%; text-align: center;">Material Description <span class="sort-icon"></span></th>
                  <th style="width: 8%; text-align: center;">Shift <span class="sort-icon"></span></th>
                  <th style="width: 6%; text-align: center;">Lot No <span class="sort-icon"></span></th>
                  <th style="width: 8%; text-align: center;">Pending QTY <span class="sort-icon"></span></th>
                  <th style="width: 8%; text-align: center;">Total QTY <span class="sort-icon"></span></th>
                  <th style="width: 15%; text-align: center;">Person Incharge <span class="sort-icon"></span></th>
                  <th style="width: 15%; text-align: center;">Date needed <span class="sort-icon"></span></th>
                  <th style="width: 15%; text-align: center;">Time In | Time out <span class="sort-icon"></span></th>
                </tr>
              </thead>

              <tbody id="data-body" style="word-wrap: break-word; white-space: normal;"></tbody>
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
            <input type="hidden" id="totalQtyHidden">
            <input type="hidden" id="recordIdHidden">

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



  <script>
    let inspectionModalInstance = null;
    let fullDataSet = [];
    let selectedRowData = null;
    let mode = '';
    let globalSection = null;
    let paginator;
    let model = null;
    const userRole = "<?= $role ?>";
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;

    document.addEventListener('DOMContentLoaded', () => {
      const tbody = document.getElementById('data-body');
      const filterInput = document.getElementById('filter-input');
      const lastUpdatedEl = document.getElementById('last-updated');

      if (!tbody || !filterInput || !lastUpdatedEl) {
        console.error("Missing required DOM elements for QC page.");
        return;
      }

      const quantityModalEl = document.getElementById('quantityModal');

      // Initialize paginator
      paginator = createPaginator({
        data: [],
        rowsPerPage: 10,
        paginationContainerId: 'pagination',
        defaultSortFn: (a, b) => {
          const isTimeOut = item => item.person_incharge?.trim() !== '' && item.time_in && item.done_quantity == null;
          const isContinue = item => item.status?.toLowerCase() === 'continue';
          const weight = item => isContinue(item) ? 2 : isTimeOut(item) ? 1 : 0;

          const aDate = new Date(a.date_needed || '9999-12-31');
          const bDate = new Date(b.date_needed || '9999-12-31');
          if (aDate - bDate !== 0) return aDate - bDate;

          const aLot = parseInt(a.lot_no) || a.lot_no || '';
          const bLot = parseInt(b.lot_no) || b.lot_no || '';
          if (aLot !== bLot) return aLot > bLot ? 1 : -1;

          if (a.reference_no === b.reference_no) return weight(b) - weight(a);
          return a.reference_no.localeCompare(b.reference_no);
        },
        renderPageCallback: (pageData) => {
          tbody.innerHTML = '';

          const sortedData = [...pageData].sort((a, b) => {
            const aTimeIn = !!a.time_in;
            const bTimeIn = !!b.time_in;
            if (aTimeIn !== bTimeIn) return bTimeIn - aTimeIn;

            const aPending = parseInt(a.pending_quantity ?? a.total_quantity ?? 0);
            const bPending = parseInt(b.pending_quantity ?? b.total_quantity ?? 0);
            const aTotal = parseInt(a.total_quantity ?? 0);
            const bTotal = parseInt(b.total_quantity ?? 0);
            const aIncomplete = aPending !== aTotal;
            const bIncomplete = bPending !== bTotal;
            if (aIncomplete !== bIncomplete) return bIncomplete - aIncomplete;

            const aDate = new Date(a.date_needed || '9999-12-31');
            const bDate = new Date(b.date_needed || '9999-12-31');
            if (aDate - bDate !== 0) return aDate - bDate;

            const aLot = parseInt(a.lot_no) || a.lot_no || '';
            const bLot = parseInt(b.lot_no) || b.lot_no || '';
            return aLot > bLot ? 1 : aLot < bLot ? -1 : 0;
          });

          sortedData.forEach(item => {
            if (item.time_in && item.time_out) return;

            const createdAt = new Date(item.created_at);
            const now = new Date();
            const TIME_DIVISOR = <?= json_encode($timeDivisor) ?>;
            const diffMinutes = (now - createdAt) / TIME_DIVISOR;
            const isTooEarly = diffMinutes < 5;
            if (isTooEarly) return;

            let actionHtml = '';
            const hasIncharge = item.person_incharge?.trim() !== '';

            if (hasIncharge) {
              if (item.done_quantity !== null) {
                actionHtml = `<span class="btn btn-sm bg-success">Done</span>`;
              } else if (item.time_in) {
                actionHtml = `<button class="btn btn-sm btn-warning time-out-btn" 
              data-materialid="${item.material_no}" 
              data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}' 
              data-mode="timeOut"
              data-itemid="${item.itemID}"
              data-id="${item.id || ''}">TIME OUT</button>`;
              } else {
                actionHtml = `<button class="btn btn-sm btn-primary time-in-btn" 
              data-materialid="${item.material_no}" 
              data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}' 
              data-mode="timeIn"
              data-itemid="${item.itemID}"
              data-id="${item.id || ''}"
              ${isTooEarly ? 'disabled title="Too early to TIME IN (wait 5 mins)"' : ''}>TIME IN</button>`;
              }
            } else {
              actionHtml = `<button class="btn btn-sm btn-primary time-in-btn" 
              data-materialid="${item.material_no}" 
              data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}' 
              data-mode="timeIn"
              ${isTooEarly ? 'disabled title="Too early to TIME IN (wait 5 mins)"' : ''}>TIME IN</button>`;
            }

            const row = document.createElement('tr');
            row.innerHTML = `
          <td style="text-align: center;">
            <span style="cursor: pointer; margin-right: 12px;" onclick="handleRefresh('${item.id}','qc','${item.section}','reset','qc')">🔄</span>

            
          </td>

          
          <td style="text-align: center;">${highlightText(item.material_no, currentFilterQuery)}</td>
          <td style="text-align: center;white-space: normal; word-wrap: break-word;">${highlightText(item.material_description, currentFilterQuery)}</td>
      
          <td style="text-align: center;">${highlightText(item.shift, currentFilterQuery) || 'NULL'}</td>
          <td style="text-align: center;">
            ${item.total_quantity !== 30 ? 'S' : (item.lot_no ? `${item.variant}-${item.lot_no}` : 'NULL')}
          </td>
          <td style="text-align: center;">${item.pending_quantity != null ? `${item.pending_quantity}` : `${item.total_quantity}`}</td>
          <td style="text-align: center;">${item.total_quantity}</td>
          <td style="text-align: center;">${highlightText(item.person_incharge, currentFilterQuery) || '<i>NONE</i>'}</td>
          <td style="text-align: center;">${highlightText(item.date_needed, currentFilterQuery) || '<i>NONE</i>'}</td>
          <td style="text-align: center;">${actionHtml}</td>
        `;
            tbody.appendChild(row);
          });

          lastUpdatedEl.textContent = `Last updated: ${new Date().toLocaleString()}`;
        }
      });

      // Setup search input for filtering
      filterInput.addEventListener('input', () => {
        const query = filterInput.value.toLowerCase();
        currentFilterQuery = query;

        const filtered = fullDataSet.filter(item => {
          return (
            (item.material_no?.toLowerCase().includes(query)) ||
            (item.material_description?.toLowerCase().includes(query)) ||
            (item.model?.toLowerCase().includes(query)) ||
            (item.shift?.toLowerCase().includes(query)) ||
            (item.person_incharge?.toLowerCase().includes(query)) ||
            (item.date_needed?.toLowerCase().includes(query))
          );
        });

        paginator.setData(filtered);
        paginator.currentPage = 1;
        paginator.render();
      });
    });

    function getData(model) {
      fetch(`api/qc/getTodoList?model=${encodeURIComponent(model)}&_=${new Date().getTime()}`)
        .then(response => response.json())
        .then(data => {
          fullDataSet = data;
          paginator.setData(fullDataSet);

          // Global search filter
          setupSearchFilter({
            filterInputSelector: '#filter-input',
            data: fullDataSet,
            onFilter: (filtered) => {
              currentFilterQuery = document.querySelector('#filter-input').value || '';
              paginator.setData(filtered);
            },
            customValueResolver: (item) => {
              // Concatenate all searchable fields
              return [
                item.material_no,
                item.material_description,
                item.model,
                item.shift,
                item.lot_no,
                item.total_quantity,
                item.person_incharge,
                item.date_needed
              ].join(' ') || '';
            }
          });
        })
        .catch(err => console.error('Fetch error:', err));
    }
    document.addEventListener('click', function(event) {
      const itemData = event.target.getAttribute('data-item');
      mode = event.target.getAttribute('data-mode');
      if (itemData) {
        selectedRowData = JSON.parse(itemData.replace(/&apos;/g, "'")); // assuming selectedRowData is global
      }

      if (event.target.matches('.time-in-btn')) {
        const {
          material_no,
          material_description,
          section
        } = selectedRowData;

        Swal.fire({
          icon: 'question',
          title: 'Confirm Time-In',
          html: `<b>Material No:</b> ${material_no}<br><b>Component:</b> ${material_description}`,
          showCancelButton: true,
          confirmButtonText: 'Yes, Proceed',
          cancelButtonText: 'Cancel'
        }).then(result => {
          if (result.isConfirmed) {
            openQRModal(selectedRowData, mode);
          }
        });
      } else if (event.target.matches('.time-out-btn')) {
        const itemData = event.target.getAttribute('data-item');
        if (itemData) {
          const parsedData = JSON.parse(itemData.replace(/&apos;/g, "'"));

          const {
            material_no,
            material_description
          } = parsedData;

          Swal.fire({
            icon: 'question',
            title: 'Confirm Time-Out',
            html: `<b>Material No:</b> ${material_no}<br><b>Component:</b> ${material_description}`,
            showCancelButton: true,
            confirmButtonText: 'Yes, Proceed',
            cancelButtonText: 'Cancel'
          }).then(result => {
            if (result.isConfirmed) {
              // Store mode in hidden input
              document.getElementById('inspectionModeHidden').value = mode;

              // Set hidden fields
              document.getElementById('totalQtyHidden').value = parsedData.total_quantity;
              document.getElementById('recordIdHidden').value = event.target.getAttribute('data-id');

              // Reset form
              document.getElementById('inspectionForm').reset();
              document.getElementById('followUpSection').style.display = 'none';
              document.getElementById('errorMsg').textContent = '';
              document.getElementById('followUpErrorMsg').textContent = '';

              // Show modal
              inspectionModalInstance = new bootstrap.Modal(document.getElementById('inspectionModal'));
              inspectionModalInstance.show();
            }
          });
        }
      }



    });



    function closeInspectionModal() {
      inspectionModalInstance.hide();
    }

    document.getElementById('notGoodQty').addEventListener('input', function() {
      const nogood = parseInt(this.value) || 0;
      const followUpSection = document.getElementById('followUpSection');
      const reworkInput = document.getElementById('rework');
      const replaceInput = document.getElementById('replace');
      const followUpError = document.getElementById('followUpErrorMsg');

      followUpError.textContent = '';

      if (nogood > 0) {
        followUpSection.style.display = 'block';

        // Optional: Reset rework/replace to 0
        reworkInput.value = 0;
        replaceInput.value = 0;
      } else {
        followUpSection.style.display = 'none';
        document.getElementById('rework').value = '';
        document.getElementById('replace').value = '';
      }

    });

    function submitInspection() {
      const goodInput = document.getElementById('goodQty');
      const nogoodInput = document.getElementById('notGoodQty');
      const good = parseInt(goodInput.value);
      const nogood = parseInt(nogoodInput.value);

      const inputQty = (isNaN(good) ? 0 : good) + (isNaN(nogood) ? 0 : nogood);
      const mode = document.getElementById('inspectionModeHidden').value;
      const followUpSection = document.getElementById('followUpSection');
      const followUpError = document.getElementById('followUpErrorMsg');
      followUpError.textContent = '';

      // ❌ Check if required fields are filled
      if (goodInput.value === '' || nogoodInput.value === '') {
        Swal.fire({
          icon: 'warning',
          title: 'Missing Input',
          text: 'Please fill in both Good and No Good quantities.'
        });
        return;
      }

      if (!selectedRowData) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No selected item data.'
        });
        return;
      }

      if (inputQty > selectedRowData.total_quantity) {
        Swal.fire({
          icon: 'error',
          title: 'Invalid Quantity',
          text: `Total (Good + No Good = ${inputQty}) must not exceed total quantity (${selectedRowData.total_quantity}).`
        });
        return;
      }

      if (nogood > 0) {
        followUpSection.style.display = 'block';

        const reworkInput = document.getElementById('rework');
        const replaceInput = document.getElementById('replace');
        const rework = parseInt(reworkInput.value);
        const replace = parseInt(replaceInput.value);

        if (reworkInput.value === '' || replaceInput.value === '') {
          followUpError.textContent = 'Please enter both Rework and Replace values.';
          return;
        }

        if ((rework + replace) !== nogood) {
          followUpError.textContent = `Rework + Replace must equal No Good (${nogood}).`;
          return;
        }
      } else {
        followUpSection.style.display = 'none';
      }
      const sameReferenceItems = fullDataSet.filter(
        item => item.reference_no === selectedRowData.reference_no
      );

      // Sum done_quantity — if selectedRowData.done_quantity is null, default to 0
      const sumDoneQuantity = Number(selectedRowData.done_quantity) || 0;

      // Total quantity should just come from selectedRowData
      const maxTotalQuantity = Number(selectedRowData.total_quantity) || 0;

      console.log("sumDoneQuantity", sumDoneQuantity);
      console.log("maxTotalQuantity", maxTotalQuantity);

      if (sumDoneQuantity + inputQty > maxTotalQuantity) {
        Swal.fire({
          icon: 'error',
          title: 'Quantity Exceeded',
          text: `Total done quantity (${sumDoneQuantity}) plus entered quantity (${inputQty}) exceeds maximum allowed total quantity (${maxTotalQuantity}).`
        });
        return;
      }




      const timeoutData = {
        recordId: document.getElementById('recordIdHidden').value,
        quantity: inputQty,
        good,
        nogood,
        rework: parseInt(document.getElementById('rework').value) || 0,
        replace: parseInt(document.getElementById('replace').value) || 0
      };

      openQRModal(selectedRowData, mode, timeoutData);

      inspectionModalInstance.hide();
    }



    function openQRModal(selectedRowData, mode, timeoutData) {
      const role = "operator";

      const section = "QC";
      console.log('try', selectedRowData)
      scanQRCodeForUser({
        section,
        role,
        userProductionLocation: selectedRowData.section,
        onSuccess: ({
          user_id,
          full_name
        }) => {

          if (mode === 'timeOut') {
            const expectedPersonInCharge = selectedRowData.person_incharge || '';
            if (full_name !== expectedPersonInCharge) {
              Swal.fire({
                icon: 'warning',
                title: 'Person In-Charge Mismatch',
                text: `Scanned name "${full_name}" does not match assigned person "${expectedPersonInCharge}".`,
                confirmButtonText: 'OK'
              });
              return;
            }
          }

          let data = {
            name: full_name,
            id: selectedRowData.id,
            total_quantity: selectedRowData.total_quantity,
            model: selectedRowData.model,
            shift: selectedRowData.shift,
            lot_no: selectedRowData.lot_no,
            date_needed: selectedRowData.date_needed,
            reference_no: selectedRowData.reference_no,
            material_no: selectedRowData.material_no,
            material_description: selectedRowData.material_description,
            assembly_section: selectedRowData.assembly_section,
            cycle_time: selectedRowData.cycle_time,
            fuel_type: selectedRowData.fuel_type,
            part_type: selectedRowData.part_type,
            customer_id: selectedRowData.customer_id
          };

          let url = '';
          console.log(mode)
          if (mode === 'timeIn') {
            url = 'api/qc/timeinOperator';
          } else {
            data.quantity = timeoutData.good + timeoutData.nogood;

            data.good = timeoutData.good;
            data.nogood = timeoutData.nogood;
            data.replace = timeoutData.replace;
            data.rework = timeoutData.rework;
            data.pending_quantity = selectedRowData.pending_quantity;
            data.process = selectedRowData.process
            url = 'api/qc/timeoutOperator';
          }
          console.log(data)
          fetch(url, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
              if (result.success === true) {
                Swal.fire({
                  icon: 'success',
                  title: 'Success',
                  text: 'Operation completed successfully!',
                  confirmButtonColor: '#3085d6'
                }).then(() => {
                  window.location.reload();
                });
              } else {
                Swal.fire('Error', 'Submission failed.', 'error');
              }
            })
            .catch(error => {
              console.error('Submission error:', error);
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
    /* Default: no horizontal scroll */
    .table-responsive-wrapper {
      width: 100%;
    }

    .custom-hover tbody tr:hover {
      background-color: #dde0e2ff !important;

    }

    /* Apply scroll only on tablets */
    @media (min-width: 768px) and (max-width: 991.98px) {
      .table-responsive-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        position: relative;
      }

      .table-responsive-wrapper tbody tr:hover td {
        background-color: #dde0e2ff !important;
      }

      .table-responsive-wrapper table {
        min-width: 1000px;
        table-layout: fixed;
      }

      .table-responsive-wrapper th,
      .table-responsive-wrapper td {
        background: #fff;
        z-index: 1;
      }

      /* Col 1 */
      .table-responsive-wrapper th:nth-child(1),
      .table-responsive-wrapper td:nth-child(1) {
        width: 50px !important;
        position: sticky;
        left: 0;
        z-index: 3;
      }

      /* Col 2 */
      .table-responsive-wrapper th:nth-child(2),
      .table-responsive-wrapper td:nth-child(2) {
        width: 120px !important;
        position: sticky;
        left: 50px;
        /* width of col1 */
        z-index: 3;
      }

      /* Col 3 */
      .table-responsive-wrapper th:nth-child(3),
      .table-responsive-wrapper td:nth-child(3) {
        width: 200px !important;
        position: sticky;
        left: 170px;
        /* col1 + col2 */
        z-index: 3;
      }

      .table-responsive-wrapper th:nth-child(4),
      /* Person Incharge */
      .table-responsive-wrapper td:nth-child(4) {
        width: 15% !important;
      }

      .table-responsive-wrapper th:nth-child(5),
      /* Person Incharge */
      .table-responsive-wrapper td:nth-child(5) {
        width: 10% !important;
      }

      .table-responsive-wrapper th:nth-child(6),
      /* Person Incharge */
      .table-responsive-wrapper td:nth-child(6) {
        width: 15% !important;
      }

      .table-responsive-wrapper th:nth-child(7),
      /* Person Incharge */
      .table-responsive-wrapper td:nth-child(7) {
        width: 15% !important;
      }

      .table-responsive-wrapper th:nth-child(8),
      /* Person Incharge */
      .table-responsive-wrapper td:nth-child(8) {
        width: 25% !important;
      }


      .table-responsive-wrapper th:nth-child(9),
      /* Person Incharge */
      .table-responsive-wrapper td:nth-child(9) {
        width: 25% !important;
      }


    }
  </style>