<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/qrcodeScanner.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<?php include './components/reusable/reset_timein.php'; ?>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2@11.js"></script>
<script src="assets/js/html5.qrcode.js"></script>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
<style>

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
            <h6 class="card-title mb-0">Production Instruction Kanban Board</h6>
            <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3" style="gap: 10px;">
            <!-- Search filter (left side) -->
            <div class="col-md-3 p-0">
              <input
                type="text"
                id="filter-input"
                class="form-control"
                placeholder="Type to filter..." />
            </div>

            <!-- Time In / Time Out buttons (right side) -->
            <div class="d-flex" style="gap: 10px;">
              <button id="time-in-btn" class="btn btn-primary btn-sm" onclick='openQRModal("time_in")'>Time In</button>
              <button id="time-out-btn" class="btn btn-primary btn-sm" onclick='openQRModal("time_out")'>Time Out</button>
            </div>
          </div>


          <div class="table-responsive">
            <table class="custom-hover table" style="table-layout: fixed; min-width: 1200px; width: 100%;">
              <thead>
                <tr>
                  <th style="width: 5%; text-align: center;"></th>
                  <th style="width: 10%; text-align: center;">Material Description</th>
                  <th style="width: 5%; text-align: center;">Process</th>
                  <th style="width: 5%; text-align: center;">Section</th>

                  <th style="width: 5%; text-align: center;">Machine</th>
                  <th style="width: 5%; text-align: center;">Total Qty</th>
                  <th style="width: 5%; text-align: center;">Pending Qty</th>
                  <th style="width: 10%; text-align: center;">Person Incharge</th>
                  <th style="width: 7%; text-align: center;">Time</th>
                  <th style="width: 7%; text-align: center;">Action</th>
                  <th style="width: 5%; text-align: center;">View</th>
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

  <div class="modal fade" id="quantityModal" tabindex="-1" aria-labelledby="quantityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="quantityModalLabel">Enter Quantity</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="timeoutQuantity" class="form-label">Quantity to Process</label>
            <input type="number" class="form-control" id="timeoutQuantity">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeInspectionModal()">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmQuantityBtn">Confirm</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    let mode = null;
    let selectedRowData = null;
    let fullData = null;
    let paginator = null;
    let section = '';
    let quantityModal = null;
    let machine = {};
    const filterColumnSelect = document.getElementById('filter-column');
    const filterInput = document.getElementById('filter-input');
    const dataBody = document.getElementById('data-body');

    const userRole = "<?= $role ?>";
    const userProduction = <?= json_encode($production) ?>;
    const userProductionLocation = <?= json_encode($production_location) ?>;

    let model = null;


    function filterRows(data) {
      const hide = ['l300 assy', 'finishing', 'mig welding'];
      return data.filter(item =>
        item.status !== 'done' &&
        !hide.includes((item.section || '').toLowerCase())
      );
    }
    fetch(`api/stamping/getMachines.php`)
      .then(response => response.json())
      .then(data => {
        machine = data

      })
      .catch(console.error);

    function getData(model) {
      return fetch(`api/stamping/getTodoList.php?model=${encodeURIComponent(model)}`)
        .then(response => response.json())
        .then(data => {
          console.log(data)
          fullData = preprocessData(data); // ⬅️ Clean it up before paginating
          paginator = createPaginator({
            data: fullData,
            rowsPerPage: 20,
            renderPageCallback: renderTable,
            paginationContainerId: 'pagination'
          });
          paginator.render(); // Initial render
          // 👉 put this immediately after paginator.render();
          setupSearchFilter({
            filterColumnSelector: '#filter-column',
            filterInputSelector: '#filter-input',
            data: fullData, // the full dataset you already prepared
            onFilter: filtered => paginator.setData(filtered),

            // ── handlers for columns that need custom logic ──
            // (keys MUST match the values in your <select>)
            customColumnHandler: {
              material_no: row => row.material_no ?? '',
              components_name: row => row.components_name ?? '',
              stage_name: row => row.stage_name ?? '',
              section: row => row.section ?? '',
              total_quantity: row => String(row.total_quantity ?? ''),
              person_incharge: row => row.person_incharge ?? '',
              time_in: row => row.time_in ?? '',
              time_out: row => row.time_out ?? ''
              // add more if you later add columns
            }
          });

        })
        .catch(console.error);
    }

    function preprocessData(data) {
      const grouped = {};

      data.forEach(item => {
        if (!grouped[item.reference_no]) grouped[item.reference_no] = [];
        grouped[item.reference_no].push(item);
      });

      const sorted = Object.values(grouped)
        .flatMap(group =>
          group.sort((a, b) => (parseInt(a.stage || 0) - parseInt(b.stage || 0)))
        );

      return sorted;
    }

    // Render the table without group headers
    function renderTable(data, page = 1) {
      console.log(data);

      const filtered = filterRows(data);
      const isAdmin = userRole.toLowerCase() === 'administrator';

      // 🔹 Handle production_location as array OR string
      let rawUserLoc = userProductionLocation;
      if (typeof rawUserLoc === "string") {
        try {
          rawUserLoc = JSON.parse(rawUserLoc); // if JSON string like '["L300 ASSY","MIG WELDING"]'
        } catch (e) {
          rawUserLoc = [rawUserLoc]; // fallback
        }
      }
      const normalizedProdLoc = (Array.isArray(rawUserLoc) ? rawUserLoc : [rawUserLoc])
        .map(loc => (loc ?? '').toLowerCase().replace(/[-\s]/g, ''));

      const filteredByRole = filtered.filter(item => {
        const sectionNorm = (item.section || '').toLowerCase().replace(/[-\s]/g, '');
        const isL300Assy = (item.section || '').trim().toUpperCase() === 'L300 ASSY';

        return (
          !isL300Assy &&
          (isAdmin || normalizedProdLoc.includes(sectionNorm))
        );
      });

      const dataBody = document.getElementById('data-body');
      dataBody.innerHTML = '';

      // ❌ Render rows directly without grouping
      filteredByRole.forEach(item => renderRow(item));

      document.getElementById('last-updated').textContent =
        `Last updated: ${new Date().toLocaleString()}`;
    }

    // Render a single row
    function renderRow(item) {
      if (item.status === 'done') return;

      const currentFilterQuery = document.getElementById('filter-input')?.value.toLowerCase() || '';
      const itemDataAttr = encodeURIComponent(JSON.stringify(item));
      const hasTimeIn = !!item.time_in;
      const hasTimeOut = !!item.time_out;

      const actionButton = hasTimeIn && hasTimeOut ?
        `<span class="btn btn-sm btn-primary">Done</span>` :
        `<button type="button"
               class="btn btn-sm btn-${hasTimeIn ? 'success' : 'primary'} time-action-btn"
               data-item="${itemDataAttr}"
               data-mode="${hasTimeIn ? 'time-out' : 'time-in'}">
         ${hasTimeIn ? 'Time Out' : 'Time In'}
       </button>`;

      const row = document.createElement('tr');

      row.innerHTML = `
    <td style="text-align: center;">
      <span style="cursor: pointer; margin-right: 12px;" onclick="handleRefresh('${item.id}','stamping','${item.section}','reset')">🔄</span>
    </td>
    <td style="text-align:center;white-space: normal; word-wrap: break-word;">
      ${highlightText(item.components_name || '<i>Null</i>', currentFilterQuery)}
      ${item.fuel_type ? ` (${highlightText(item.fuel_type, currentFilterQuery)})` : ''}
    </td>
    <td style="text-align:center;white-space: normal; word-wrap: break-word;">
      ${highlightText(item.stage_name || '', currentFilterQuery)}
    </td>
    <td style="text-align:center;white-space: normal; word-wrap: break-word;">
      ${highlightText(item.section || '<i>Null</i>', currentFilterQuery)}
    </td>
    <td style="text-align:center;">
      ${highlightText(item.machine_name || '', currentFilterQuery)}
    </td>
    <td style="text-align:center;">
      ${highlightText(item.total_quantity ?? '<i>Null</i>', currentFilterQuery)}
    </td>
    <td style="text-align:center;">
      ${highlightText(item.pending_quantity ?? '<i>0</i>', currentFilterQuery)}
    </td>
    <td style="text-align:center;">
      ${highlightText(item.person_incharge || '<i>Null</i>', currentFilterQuery)}
    </td>
    <td style="text-align:center;">
      ${highlightText(item.time_in || '<i>Null</i>', currentFilterQuery)} / ${highlightText(item.time_out || '<i>Null</i>', currentFilterQuery)}
    </td>
    <td style="text-align:center;">
      ${actionButton}
    </td>
    <td style="text-align:center;">
      <button class="btn btn-sm"
              onclick="viewStageStatus('${item.material_no}', '${item.components_name}', '${item.batch}')"
              title="View Stages">🔍</button>
    </td>`;

      dataBody.appendChild(row);
    }


    // Enable/disable filter input based on dropdown


    filterInput.addEventListener('input', applyFilter);

    function applyFilter() {
      const column = filterColumnSelect.value;
      const filterValue = filterInput.value.trim().toLowerCase();

      if (!column) {
        paginator.setData(fullData);
        return;
      }

      const filtered = fullData.filter(item => {
        let val = item[column];
        if (val === null || val === undefined) return false;
        return String(val).toLowerCase().includes(filterValue);
      });

      paginator.setData(filtered);
    }


    document.getElementById('data-body').addEventListener('click', (event) => {
      const btn = event.target.closest('.time-action-btn');
      if (!btn) return;

      const encodedItem = btn.getAttribute('data-item');
      const mode = btn.getAttribute('data-mode');

      selectedRowData = JSON.parse(decodeURIComponent(encodedItem));

      const {
        material_no,
        components_name // ← updated this line
      } = selectedRowData;

      if (mode === 'time-in') {
        const stage = parseInt(selectedRowData.stage || 0);
        const batch = selectedRowData.batch;
        const relatedItems = fullData.filter(item => item.batch === batch);
        console.log('🧾 Related Items for Batch:', batch, relatedItems);

        const maxTotalQuantity = Math.max(...relatedItems.map(i => i.total_quantity || 0));

        if (stage === 1) {
          const stage1Items = relatedItems.filter(item => parseInt(item.stage) === 1);
          const sumStage1Quantity = stage1Items.reduce((sum, item) => sum + (item.quantity || 0), 0);

          if (sumStage1Quantity >= maxTotalQuantity) {
            showMachineSelection(machine, selectedRowData.machine_name, selectedRowData.section).then(result => {
              if (result.isConfirmed) {
                const remarks = result.value.remarks || '';
                console.log('wee', remarks);

                // ✅ Push the remarks directly into selectedRowData
                selectedRowData.remarks = remarks;

                // ✅ Now call openQRModal with mode only
                openQRModal(mode);
              }
            });

            return;
          }
        }

        if (stage > 1) {
          const prevStage = stage - 1;
          const prevStageItems = relatedItems.filter(item =>
            parseInt(item.stage) === prevStage
          );

          const hasOngoing = prevStageItems.some(item => item.status?.toLowerCase() === 'ongoing');
          const allDone = prevStageItems.length > 0 &&
            prevStageItems.every(item => item.status?.toLowerCase() === 'done');
          const quantityCompleted = prevStageItems.reduce((sum, item) => sum + (item.quantity || 0), 0);
          const maxTotalQuantity = Math.max(...relatedItems.map(i => i.total_quantity || 0));
          const prevStageCompleted = quantityCompleted >= maxTotalQuantity;

          /* ✚ NEW — at least one *previous* entry already DONE (for duplicate rows) */
          const hasAnyDone = prevStageItems.some(item => item.status?.toLowerCase() === 'done');

          const isSpecialSection = ['finishing', 'l300 assy']
            .includes((selectedRowData.section || '').toLowerCase());

          if (isSpecialSection) {
            const hasOngoing = prevStageItems.some(item => item.status?.toLowerCase() === 'ongoing');
            const hasAnyDone = prevStageItems.some(item => item.status?.toLowerCase() === 'done');
            const allDone = prevStageItems.length > 0 &&
              prevStageItems.every(item => item.status?.toLowerCase() === 'done');

            // 🚫 If none of the allowed conditions are true, block time-in
            if (!hasOngoing && !hasAnyDone && !allDone) {
              Swal.fire({
                icon: 'warning',
                title: `Cannot Time-In`,
                text: `The previous process didn't meet its requirements to proceed.`,
                customClass: {
                  popup: 'swal-sm' // apply your small popup style
                }
              });
              return;
            }

          }
          if (!hasOngoing && !allDone && !prevStageCompleted && !hasAnyDone && !isSpecialSection) {
            Swal.fire({
              icon: 'warning',
              title: `Cannot Time-In`,
              text: `The previous process didn't meet its requirements to proceed.`,
              customClass: {
                popup: 'swal-sm' // apply your small popup style
              }
            });
            return;
          }

        }

        showMachineSelection(machine, selectedRowData.machine_name, selectedRowData.section).then(result => {
          if (result.isConfirmed) {
            const remarks = result.value.remarks || '';
            console.log('wee', remarks);

            // ✅ Push the remarks directly into selectedRowData
            selectedRowData.remarks = remarks;

            // ✅ Now call openQRModal with mode only
            openQRModal(mode);
          }
        });








        // showConfirmation(mode, material_no, components_name).then(result => {
        //   if (result.isConfirmed) openQRModal(mode);
        // });

      } else if (mode === 'time-out') {
        quantityModal = new bootstrap.Modal(document.getElementById('quantityModal'));
        // document.getElementById('timeoutQuantity').value = selectedRowData.pending_quantity || 1;

        const confirmBtn = document.getElementById('confirmQuantityBtn');
        confirmBtn.onclick = () => {
          const inputQuantity = parseInt(document.getElementById('timeoutQuantity').value, 10);

          if (!inputQuantity || inputQuantity <= 0) {
            Swal.fire({
              icon: 'warning',
              title: 'Invalid Quantity',
              text: 'Please enter a valid, positive quantity greater than 0.',
              customClass: {
                popup: 'swal-sm' // apply your small popup style
              }
            });
            return;
          }


          const referenceNo = selectedRowData.reference_no;
          let totalQuantity = parseInt(selectedRowData.total_quantity, 10) || 0;
          let pendingQuantity = parseInt(selectedRowData.pending_quantity, 10) || 0;
          const manpower = selectedRowData.manpower;
          const sumQuantity = fullData
            .filter(row => row.reference_no === referenceNo)
            .reduce((sum, row) => sum + (parseInt(row.quantity, 10) || 0), 0);

          if (manpower > 0) {
            pendingQuantity *= 2
            totalQuantity *= 2
          }
          if (inputQuantity > pendingQuantity) {
            Swal.fire({
              icon: 'error',
              title: 'Pending Quantity Limit Exceeded',
              html: `
    <p>You entered <strong>${inputQuantity}</strong> units.</p>
    <p>But only <strong>${pendingQuantity}</strong> units are pending for processing.</p>
    <p>Please adjust your quantity accordingly.</p>
  `,
              customClass: {
                popup: 'swal-sm' // small popup for consistency
              }
            });
            return;

          }

          // 🔒 Limit to totalQuantity
          if (sumQuantity + inputQuantity > totalQuantity) {
            const remaining = totalQuantity - sumQuantity;
            Swal.fire({
              icon: 'error',
              title: 'Total Quantity Limit Exceeded',
              html: `
    <p>Reference #: <strong>${referenceNo}</strong></p>
    <p>Already processed: <strong>${sumQuantity}</strong> / ${totalQuantity}</p>
    <p>Your input of <strong>${inputQuantity}</strong> would exceed the total allowed.</p>
    <p>You can only process up to <strong>${remaining}</strong> more units.</p>
  `,
              customClass: {
                popup: 'swal-sm' // apply small popup styling
              }
            });

            return;
          }

          // 🔒 NEW: Check if inputQuantity is less than or equal to the total "done" quantity from previous stage
          const currentStage = parseInt(selectedRowData.stage || 0);
          const batch = selectedRowData.batch;

          if (currentStage > 1) {
            const prevStage = currentStage - 1;

            const doneQtyFromPrevStage = fullData
              .filter(item =>
                item.batch === batch &&
                parseInt(item.stage) === prevStage &&
                item.status?.toLowerCase() === 'done'
              )
              .reduce((sum, item) => sum + (parseInt(item.quantity, 10) || 0), 0);

            if (inputQuantity > doneQtyFromPrevStage) {
              Swal.fire({
                icon: 'error',
                title: 'Exceeded Previous Process Output',
                html: `
            <p>You entered <strong>${inputQuantity}</strong> units for Time-Out.</p>
            <p>But only <strong>${doneQtyFromPrevStage}</strong> units were completed in the previous process.</p>
            <p>Please enter a quantity within that limit.</p>
          `,
                customClass: {
                  popup: 'swal-sm' // apply small popup styling
                }
              });
              return;
            }
          }

          selectedRowData.inputQuantity = inputQuantity;
          quantityModal.hide();
          console.log(selectedRowData)
          showConfirmation(mode, selectedRowData.material_no, selectedRowData.components_name).then(result => {
            if (result.isConfirmed) {

              // Proceed to QR modal
              openQRModal(mode);
            }
          });

        };

        quantityModal.show();
      }

    });

    function showMachineSelection(machineList, currentMachineName, currentSection) {
      // Determine width based on screen size
      const screenWidth = window.innerWidth;
      let swalWidth = 500; // default for desktop
      if (screenWidth < 992 && screenWidth >= 768) swalWidth = 350; // tablet
      if (screenWidth < 768) swalWidth = 300; // mobile

      return Swal.fire({
        icon: 'warning',
        title: 'Transferring Machine?',
        html: `
      <div style="text-align: center; font-size: ${screenWidth < 992 ? '0.85rem' : '0.95rem'}; margin-bottom: 5px;">
        <p>If you're transferring, select the new section and machine. Otherwise, leave both selections empty to remain on your current machine: ${currentSection} _ ${currentMachineName}.</p>
      </div>

      <div style="display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 5px 10px;">
        <select id="sectionSelect" class="swal2-select" style="
          width: ${screenWidth < 992 ? '80%' : '60%'};
          padding: ${screenWidth < 992 ? '6px 10px' : '8px 12px'};
          font-size: ${screenWidth < 992 ? '0.85rem' : '0.95rem'};
          border-radius: 6px;
          border: 1px solid #ccc;
          box-shadow: 0 1px 2px rgba(0,0,0,0.05);
          text-align: center;
        ">
          <option value="">Select Section</option>
          ${[...new Set(machineList.map(m => m.section))]
            .map(section => `<option value="${section}">${section}</option>`)
            .join('')}
        </select>

        <select id="machineSelect" class="swal2-select" style="
          width: ${screenWidth < 992 ? '80%' : '60%'};
          padding: ${screenWidth < 992 ? '6px 10px' : '8px 12px'};
          font-size: ${screenWidth < 992 ? '0.85rem' : '0.95rem'};
          border-radius: 6px;
          border: 1px solid #ccc;
          box-shadow: 0 1px 2px rgba(0,0,0,0.05);
          text-align: center;
        ">
          <option value="">Select Machine</option>
        </select>
      </div>
    `,
        width: swalWidth,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Continue',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
          const section = document.getElementById('sectionSelect')?.value || '';
          const name = document.getElementById('machineSelect')?.value || '';
          const remarks = section && name ? `${section} : ${name}` : '';
          return {
            remarks
          };
        },
        didOpen: () => {
          const sectionSelect = document.getElementById('sectionSelect');
          const machineSelect = document.getElementById('machineSelect');

          sectionSelect.addEventListener('change', () => {
            const selectedSection = sectionSelect.value;
            const filteredMachines = machineList.filter(m => m.section === selectedSection);

            machineSelect.innerHTML = `<option value="">Select Machine</option>` +
              filteredMachines.map(m => `<option value="${m.name}">${m.name}</option>`).join('');
          });
        }
      });
    }

    function closeInspectionModal() {
      quantityModal.hide()
    }

    function showConfirmation(mode, materialNo, componentName) {
      return Swal.fire({
        icon: 'question',
        title: `Confirm ${mode === 'time-in' ? 'Time-In' : 'Time-Out'}`,
        html: `<b>Material No:</b> ${materialNo}<br><b>Component:</b> ${componentName}`,
        showCancelButton: true,
        confirmButtonText: 'Yes, Proceed',
        cancelButtonText: 'Cancel',
        customClass: {
          popup: 'swal-sm' // apply small popup styling
        }
      });
    }

    function viewStageStatus(materialNo, componentName, batch) {
      fetch('api/stamping/fetchStageStatus.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            material_no: materialNo,
            components_name: componentName,
            batch
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            let stages = data.stages || [];

            // remove duplicates
            const seen = new Set();
            stages = stages.filter(stage => {
              const key = stage.stage;
              if (seen.has(key)) return false;
              seen.add(key);
              return true;
            });

            const content = stages.length > 0 ?
              `
            <style>
              .stage-box {
                border: 1px solid #ccc;
                border-radius: 8px;
                box-shadow: 1px 1px 5px rgba(0,0,0,0.1);
                padding: 10px;
                flex: 1 1 180px;
                max-width: 200px;
                min-width: 180px;
              }
              .stage-container {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                justify-content: center;
                padding: 10px;
              }
              @media (max-width: 991px) { /* tablet */
                .stage-box {
                  min-width: 140px;
                  max-width: 160px;
                  padding: 8px;
                  font-size: 0.85rem;
                }
              }
              @media (max-width: 576px) { /* mobile */
                .stage-box {
                  min-width: 120px;
                  max-width: 140px;
                  padding: 6px;
                  font-size: 0.8rem;
                }
              }
            </style>
            <div class="stage-container">
              ${stages.map(stage => renderStageBox(stage)).join('')}
            </div>
          ` :
              '<i>No stages found</i>';

            Swal.fire({
              title: 'Component Status',
              html: content,
              icon: 'info',
              width: '60%', // comfortable width on laptop/PC
              showCloseButton: true,
              showConfirmButton: false,
              customClass: {
                popup: 'swal-sm' // apply small popup styling
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not fetch stage data.',
              customClass: {
                popup: 'swal-sm'
              }
            });
          }
        })
        .catch(err => {
          console.error('Fetch error:', err);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong.',
            customClass: {
              popup: 'swal-sm'
            }
          });
        });


      function renderStageBox(stage) {
        return `
      <div class="stage-box">
        <b>Section:</b> ${stage.section}<br>
        <b>Process:</b> ${stage.stage_name}<br>
        <b>Status:</b> <span style="color: ${stage.status === 'done' ? 'green' : 'orange'}">${stage.status}</span><br>
        <small>(${stage.stage})</small>
      </div>
    `;
      }
    }




    let isProcessingScan = false;

    function openQRModal(mode, remarks) {

      if (!selectedRowData) {
        console.error("No selectedRowData available!");
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No data selected for processing.',
          customClass: {
            popup: 'swal-sm'
          }
        });

        return;
      }
      const section = "STAMPING";
      const role = "operator";

      scanQRCodeForUser({
        section: section, // 🟢 this is the production
        role: role, // 🟢 e.g. "worker"
        userProductionLocation: selectedRowData.section,
        onSuccess: ({
          user_id,
          full_name
        }) => {
          if (mode === 'time-out') {
            const expectedPerson = selectedRowData.person_incharge || '';
            if (full_name !== expectedPerson) {
              Swal.fire({
                icon: 'warning',
                title: 'Person In-Charge Mismatch',
                text: `Scanned name "${full_name}" does not match assigned person "${expectedPerson}".`,
                confirmButtonText: 'OK',
                customClass: {
                  popup: 'swal-sm'
                }
              });
              return; // ⛔ Stop further execution
            }
          }

          const {
            material_no,
            material_description,
            id,
            pending_quantity,
            quantity,
            inputQuantity,
            total_quantity,
            process_quantity,
            remarks,
            pair,
            stage,
            reference_no,
            manpower,
            duplicated,
            model,
            customer_id
          } = selectedRowData;

          const postData = {
            id,
            material_no,
            material_description,
            userId: user_id,
            name: full_name,
            quantity,
            inputQuantity,
            pending_quantity,
            total_quantity,
            remarks,
            pair,
            stage,
            reference_no,
            manpower,
            duplicated,
            model,
            customer_id
          };
          console.log(postData)
          const endpoint = mode === 'time-in' ?
            'api/stamping/postTimeInTask.php' :
            'api/stamping/postTimeOutTask.php';

          fetch(endpoint, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(postData)
            })
            .then(res => res.json())
            .then(response => {
              if (response.status === 'success') {
                Swal.fire({
                  icon: 'success',
                  title: 'Success',
                  text: response.message || `${mode.replace('-', ' ')} recorded.`,
                  customClass: {
                    popup: 'swal-sm'
                  }
                }).then(() => {
                  // Refresh data after success
                  window.location.reload();
                });
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: response.message || 'Something went wrong.',
                  customClass: {
                    popup: 'swal-sm'
                  }
                });
              }
            })
            .catch(err => {
              console.error(err);
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Network error occurred.',
                customClass: {
                  popup: 'swal-sm'
                }
              });
            });

        },
        onCancel: () => {

        }
      });
    }



    function stopQRScanner() {
      if (html5QrcodeScanner) {
        html5QrcodeScanner.stop().then(() => {
          html5QrcodeScanner.clear();
        }).catch(err => {
          console.warn("QR scanner stop failed:", err);
        });
      }
    }
  </script>
  <style>
    .custom-hover tbody tr:hover {
      background-color: #dde0e2ff !important;
      /* light blue */
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
      .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      /* Row hover */

      .table-responsive th,
      .table-responsive td {
        background: #fff;
        z-index: 1;
        box-sizing: border-box;
        /* include padding in width */
        padding: 8px 12px;
        /* adjust as needed */
      }

      /* 1st column */
      .table-responsive th:nth-child(1),
      .table-responsive td:nth-child(1) {
        position: sticky;
        left: 0;
        z-index: 3;

        width: 80px;
      }

      /* 2nd column */
      .table-responsive th:nth-child(2),
      .table-responsive td:nth-child(2) {
        position: sticky;
        left: 37px;
        /* exactly width of col1 */
        z-index: 3;

        width: 320px;
      }

      /* 3rd column */
      .table-responsive th:nth-child(3),
      .table-responsive td:nth-child(3) {
        position: sticky;
        left: 218px;
        /* col1 + col2 */
        z-index: 3;

        width: 150px;
      }

      .custom-hover tbody tr:hover {
        background-color: #dde0e2ff !important;
      }

    }
  </style>