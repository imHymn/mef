<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/qrcodeScanner.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
<?php include './components/reusable/reset_timein.php'; ?>
<style>
  .custom-hover tbody tr:hover {
    background-color: #dde0e2ff !important;
    /* light blue */
  }
</style>

<script src="assets/js/bootstrap.bundle.min.js"></script>


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
            <h6 class="card-title">NON CONFORMING PRODUCTS</h6>
            <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
          </div>

          <div class="row mb-3 col-md-4">


            <input
              type="text"
              id="filter-input"
              class="form-control"
              placeholder="Type to filter..." />

          </div>
          <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
            <thead>
              <tr>
                <th style="width: 3%; text-align: center;"><span class="sort-icon"></span></th>
                <th style="width: 10%; text-align: center;">Material No <span class="sort-icon"></span></th>
                <th style="width: 15%; text-align: center;">Material Description <span class="sort-icon"></span></th>
                <th style="width: 5%; text-align: center;">Model <span class="sort-icon"></span></th>
                <th style="width: 8%; text-align: center;">Shift <span class="sort-icon"></span></th>
                <th style="width: 8%; text-align: center;">Lot <span class="sort-icon"></span></th>
                <th style="width: 8%; text-align: center;">Pending Qty <span class="sort-icon"></span></th>
                <th style="width: 8%; text-align: center;">Total Qty <span class="sort-icon"></span></th>
                <th style="width: 15%; text-align: center;">Person Incharge <span class="sort-icon"></span></th>
                <th style="width: 10%; text-align: center;">Date needed <span class="sort-icon"></span></th>
                <th style="width: 10%; text-align: center;">Status<span class="sort-icon"></span></th>
              </tr>
            </thead>

            <tbody id="data-body" style="word-wrap: break-word; white-space: normal;"></tbody>
          </table>
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
            <div class="mb-3">
              <label for="good" class="form-label">Good</label>
              <input type="number" class="form-control" id="good" required>
            </div>
            <div class="mb-3">
              <label for="no_good" class="form-label">No Good</label>
              <input type="number" class="form-control" id="no_good" required>
            </div>

            <div id="followUpSection" style="display: none;">
              <div class="mb-3">
                <label for="rework" class="form-label">Rework</label>
                <input type="number" class="form-control" id="rework" value="0">
              </div>
              <div class="mb-3">
                <label for="replace" class="form-label">Replace</label>
                <input type="number" class="form-control" id="replace" value="0">
              </div>
              <div id="followUpErrorMsg" class="text-danger"></div>
            </div>

            <input type="hidden" id="totalQtyHidden">
            <input type="hidden" id="recordIdHidden">
            <input type="hidden" id="cycletimeHidden">
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
    let globalSection = null;
    const userRole = "<?= $role ?>";
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;

    let fullData = [];
    let paginator = null;
    let model = null;
    document.addEventListener('DOMContentLoaded', function() {

      // Initialize paginator
      paginator = createPaginator({
        data: [],
        rowsPerPage: 10,
        paginationContainerId: 'pagination',
        defaultSortFn: (a, b) => {
          const weight = item => {
            if (item.qc_timein && !item.qc_timeout) return 2;
            if (!item.qc_timein) return 1;
            return 0;
          };
          return weight(b) - weight(a);
        },
        renderPageCallback: renderTable
      });

      // Setup filter inputs
      const filterColumn = document.getElementById('filter-column');
      const filterInput = document.getElementById('filter-input');

      if (filterColumn && filterInput) {
        filterColumn.addEventListener('change', () => {
          filterInput.disabled = !filterColumn.value;
          filterInput.value = '';
          applyFilter();
        });

        filterInput.addEventListener('input', () => {
          applyFilter();
        });
      }

      function applyFilter() {
        const query = filterInput.value.toLowerCase();
        currentFilterQuery = query;

        const filtered = fullData.filter(item => {
          return (
            (item.material_no?.toLowerCase().includes(query)) ||
            (item.material_description?.toLowerCase().includes(query)) ||
            (item.model?.toLowerCase().includes(query)) ||
            (item.shift?.toLowerCase().includes(query)) ||
            (item.lot_no?.toString().toLowerCase().includes(query)) ||
            (item.qc_quantity?.toString().toLowerCase().includes(query)) ||
            (item.quantity?.toString().toLowerCase().includes(query)) ||
            (item.qc_person_incharge?.toLowerCase().includes(query)) ||
            (item.date_needed?.toLowerCase().includes(query))
          );
        });

        paginator.setData(filtered);
        paginator.currentPage = 1;
        paginator.render();
      }
    });

    function getData(model) {
      fetch('api/qc/getRework?model=' + encodeURIComponent(model))
        .then(res => res.json())
        .then(data => {
          fullData = data || [];
          paginator.setData(fullData);

          setupSearchFilter({
            filterColumnSelector: '#filter-column',
            filterInputSelector: '#filter-input',
            data: fullData,
            onFilter: filtered => paginator.setData(filtered),
            customValueResolver: (item, column) => {
              switch (column) {
                case 'material_no':
                  return item.material_no ?? '';
                case 'material_description':
                  return item.material_description ?? '';
                case 'model':
                  return item.model ?? '';
                case 'shift':
                  return item.shift ?? '';
                case 'lot_no':
                  return item.lot_no?.toString() ?? '';
                case 'quantity':
                  return item.qc_quantity ?? '';
                case 'qc_person_incharge':
                  return item.qc_person_incharge ?? '';
                case 'date_needed':
                  return item.date_needed ?? '';
                default:
                  return item[column] ?? '';
              }
            }
          });
        })
        .catch(err => console.error('Fetch error:', err));
    }

    // Render table with highlight
    function renderTable(data) {
      const tbody = document.getElementById('data-body');
      tbody.innerHTML = '';

      data.forEach(item => {
        if (item.qc_timeout != null) return; // skip completed

        let actionHtml = '';
        if (!item.qc_timein) {
          actionHtml = `<button class="btn btn-sm btn-success time-in-btn" data-materialid="${item.material_no}" data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}' data-mode="timeIn" data-id="${item.id}">TIME IN</button>`;
        } else if (!item.qc_timeout) {
          actionHtml = `<button class="btn btn-sm btn-warning time-out-btn" data-materialid="${item.material_no}" data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}' data-mode="timeOut" data-id="${item.id}">TIME OUT</button>`;
        } else {
          actionHtml = `<span class="text-muted">Done</span>`;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td style="text-align: center;">
            <span style="cursor: pointer; margin-right: 12px;" onclick="handleRefresh('${item.id}','qc','${item.section}','qc','reworkqc')">🔄</span>
          </td>
      <td style="text-align:center;">${highlightText(item.material_no, currentFilterQuery) || '<i>NULL</i>'}</td>
      <td style="text-align:center;white-space:normal;word-wrap:break-word;">${highlightText(item.material_description, currentFilterQuery) || '<i>NULL</i>'}</td>
      <td style="text-align:center;">${highlightText(item.model, currentFilterQuery) || '<i>NULL</i>'}</td>
      <td style="text-align:center;">${highlightText(item.shift, currentFilterQuery) || '<i>NULL</i>'}</td>
      <td style="text-align:center;">${highlightText(item.lot_no?.toString(), currentFilterQuery) || '<i>NULL</i>'}</td>
      <td style="text-align:center;">${highlightText(item.qc_quantity?.toString(), currentFilterQuery)}</td>
      <td style="text-align:center;">${highlightText(item.quantity?.toString(), currentFilterQuery)}</td>
      <td style="text-align:center;">${highlightText(item.qc_person_incharge, currentFilterQuery) || '<i>NULL</i>'}</td>
      <td style="text-align:center;">${highlightText(item.date_needed, currentFilterQuery) || '<i>NULL</i>'}</td>
      <td style="text-align:center;">${actionHtml}</td>
    `;
        tbody.appendChild(tr);
      });

      document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
    }



    // Filter logic
    const filterColumn = document.getElementById('filter-column');
    const filterInput = document.getElementById('filter-input');


    function applyFilter() {
      const column = filterColumn.value;
      const searchTerm = filterInput.value.trim().toLowerCase();

      if (!column || !searchTerm) {
        paginator.setData(fullData);
        return;
      }

      const filtered = fullData.filter(item => {
        let value = item[column];
        if (value === undefined || value === null) return false;
        return String(value).toLowerCase().includes(searchTerm);
      });

      paginator.setData(filtered);
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
          material_description,
          cycle_time
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
              document.getElementById('cycletimeHidden').value = selectedRowData.cycle_time;
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
      const good = parseInt(document.getElementById('good').value, 10) || 0;
      const no_good = parseInt(document.getElementById('no_good').value, 10) || 0;
      const rework = parseInt(document.getElementById('rework')?.value, 10) || 0;
      const replace = parseInt(document.getElementById('replace')?.value, 10) || 0;
      const quantity = good + no_good;
      const cycletime = document.getElementById('cycletimeHidden').value;

      const followUpSection = document.getElementById('followUpSection');
      const followUpError = document.getElementById('followUpErrorMsg');
      followUpError.textContent = '';


      if (good === 0 && no_good === 0) {
        showAlert('warning', 'Missing Data', 'Please enter at least one value (Good, No Good, Rework, or Replace).');
        return;
      }

      // ✅ Quantity limit check
      if (selectedRowData.qc_quantity > 0) {
        if (quantity > selectedRowData.qc_quantity) {
          showAlert('error', 'Invalid Quantity', `Quantity must be less than or equal to ${selectedRowData.qc_quantity}.`);
          return;
        }
      } else {
        if (quantity > selectedRowData.quantity) {
          showAlert('error', 'Invalid Quantity', `Quantity must be less than or equal to ${selectedRowData.quantity}.`);
          return;
        }
      }

      // ✅ Good + No Good consistency
      if ((good + no_good) !== quantity) {
        showAlert('error', 'Mismatch Detected', `Good + No Good must equal ${quantity}.`);
        return;
      }

      // ✅ Rework + Replace consistency with No Good
      if (no_good > 0) {
        if ((rework + replace) !== no_good) {
          followUpSection.style.display = 'block';
          followUpError.textContent = `Rework (${rework}) + Replace (${replace}) must equal No Good (${no_good}).`;
          return;
        }
      }

      // ✅ Confirm dialog
      Swal.fire({
        title: 'Confirm Submission',
        html: `
      <p>Are you sure you want to submit the inspection data?</p>
      <strong>Good:</strong> ${good} <br>
      <strong>No Good:</strong> ${no_good} <br>
      <strong>Rework:</strong> ${rework} <br>
      <strong>Replace:</strong> ${replace} <br>
      <strong>Total Quantity:</strong> ${quantity}
    `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Submit',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          selectedRowData.good = good;
          selectedRowData.no_good = no_good;
          selectedRowData.rework = rework;
          selectedRowData.replace = replace;
          selectedRowData.inputQty = quantity;
          selectedRowData.cycle_time = cycletime;
          globalSection = selectedRowData.assembly_section;
          inspectionModal.hide();
          openQRModal(selectedRowData, 'timeOut', globalSection);
        }
      });
    }


    function openQRModal(selectedRowData, mode, globalSection) {
      console.log(selectedRowData)
      const section = "QC";
      const role = "operator";

      scanQRCodeForUser({
        section,
        role,

        onSuccess: ({
          user_id,
          full_name
        }) => {
          // ✅ Apply mismatch check only on timeOut
          if (mode === 'timeOut') {
            const expectedPersonInCharge = selectedRowData.qc_person_incharge || '';
            if (full_name !== expectedPersonInCharge) {
              showAlert(
                'warning',
                'Person In-Charge Mismatch',
                `Scanned name "${full_name}" does not match assigned person "${expectedPersonInCharge}".`
              );
              return;
            }

          }

          let data = {
            id: selectedRowData.id,
            full_name: full_name,
            inputQty: selectedRowData.inputQty,
            no_good: selectedRowData.no_good,
            good: selectedRowData.good,
            reference_no: selectedRowData.reference_no,
            quantity: selectedRowData.quantity,
            qc_pending_quantity: selectedRowData.qc_pending_quantity,
            assembly_section: selectedRowData.assembly_section,
            cycle_time: selectedRowData.cycle_time
          };

          let url = 'api/qc/timein_reworkOperator';

          if (mode === 'timeOut') {
            data = { // ← overwrite, not redeclare
              id: selectedRowData.id,
              full_name,
              inputQty: selectedRowData.inputQty ?? selectedRowData.qc_quantity ?? 0,
              good: selectedRowData.good,
              no_good: selectedRowData.no_good,
              replace: selectedRowData.replace,
              rework: selectedRowData.rework,
              rework_no: selectedRowData.rework_no,

              quantity: selectedRowData.quantity,
              total_quantity: selectedRowData.total_quantity ?? selectedRowData.quantity,
              qc_pending_quantity: selectedRowData.qc_pending_quantity,
              pending_quantity: selectedRowData.pending_quantity ?? null,

              reference_no: selectedRowData.reference_no,
              model: selectedRowData.model,
              material_no: selectedRowData.material_no,
              material_description: selectedRowData.material_description,

              shift: selectedRowData.shift,
              lot_no: selectedRowData.lot_no,
              date_needed: selectedRowData.date_needed,
              process: selectedRowData.process,
              assembly_section: selectedRowData.assembly_section,
              cycle_time: selectedRowData.cycle_time
            };

            url = 'api/qc/timeout_reworkOperator';
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
                showAlert('success', 'Success', 'Your operation was successful!');
                setTimeout(() => window.location.reload(), 2000); // reload after showing success
              } else {
                showAlert('error', 'Error', response.message || 'Operation failed.');
              }
            })
            .catch(err => {
              console.error('Request failed', err);
              showAlert('error', 'Error', 'Something went wrong.');
            });

        },
        onCancel: () => {

        }
      });
    }


    document.getElementById('no_good').addEventListener('input', function() {
      const nogood = parseInt(this.value) || 0;
      const followUpSection = document.getElementById('followUpSection');
      const reworkInput = document.getElementById('rework');
      const replaceInput = document.getElementById('replace');
      const followUpError = document.getElementById('followUpErrorMsg');

      followUpError.textContent = '';

      if (nogood > 0) {
        followUpSection.style.display = 'block';
        reworkInput.value = 0;
        replaceInput.value = 0;
      } else {
        followUpSection.style.display = 'none';
        reworkInput.value = '';
        replaceInput.value = '';
      }
    });

    enableTableSorting(".table");
  </script>