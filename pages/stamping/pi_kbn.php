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

          <div class="table-responsive-wrapper">
            <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
              <thead>
                <tr>
                  <th style="width: 3%; text-align: center;"></th>

                  <th style="width: 7%; text-align: center;">Material No</th>

                  <th style="width: 10%; text-align: center;">Material Description</th>

                  <th style="width: 5%; text-align: center;">Process</th>
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
  <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="qrModalLabel">Scan QR Code</h5>
        </div>
        <div class="modal-body">
          <div id="qr-reader" style="width: 100%"></div>
          <div id="qr-result" class="mt-3 text-center fw-bold text-success"></div>
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
            <input type="number" class="form-control" id="timeoutQuantity" min="1" value="1">
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
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;

    let model = null;

    // if (role === "administrator") {
    //   section = "stamping";
    // } else {
    //   section = production;
    // }

    function filterRows(data) {
      const hide = ['l300 assy', 'finishing', 'mig welding'];
      return data.filter(item =>
        item.status !== 'done' &&
        !hide.includes((item.section || '').toLowerCase())
      );
    }



    function getData(model) {
      fetch(`api/stamping/getAllData_assigned?model=${encodeURIComponent(model)}`)
        .then(response => response.json())
        .then(data => {

          fullData = preprocessData(data); // clean dataset

          paginator = createPaginator({
            data: fullData,
            rowsPerPage: 20,
            renderPageCallback: renderTable,
            paginationContainerId: 'pagination'
          });
          paginator.render(); // Initial render

          const searchableFields = [
            'material_no',
            'components_name',
            'stage_name',
            'section',
            'total_quantity',
            'person_incharge',
            'time_in',
            'time_out'
          ];

          setupSearchFilter({
            filterInputSelector: '#filter-input',
            data: fullData,
            searchableFields,
            onFilter: (filtered, query) => {
              currentFilterQuery = query; // store query globally
              paginator.setData(filtered);
            },
            customColumnHandler: {
              material_no: row => row.material_no ?? '',
              components_name: row => row.components_name ?? '',
              stage_name: row => row.stage_name ?? '',
              section: row => row.section ?? '',
              total_quantity: row => String(row.total_quantity ?? ''),
              person_incharge: row => row.person_incharge ?? '',
              time_in: row => row.time_in ?? '',
              time_out: row => row.time_out ?? ''
            }
          });
        })
        .catch(console.error);

      fetch(`api/stamping/getMachines`)
        .then(response => response.json())
        .then(data => {
          machine = data
          console.log(machine)
        })

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

    function renderTable(data, page = 1) {

      const filtered = filterRows(data);
      const isAdmin = userRole.toLowerCase() === 'administrator';
      const normalizedProdLoc = Array.isArray(userProductionLocation) ?
        userProductionLocation.map(loc => loc.replace(/\s|-/g, '').toLowerCase()) : [String(userProductionLocation).replace(/\s|-/g, '').toLowerCase()];

      const filteredByRole = filtered.filter(item => {
        const sectionNorm = (item.section || '').replace(/\s|-/g, '').toLowerCase();
        const disallowed = ['l300 assy', 'finishing', 'mig welding']; // now these are NOT allowed
        return !disallowed.includes(sectionNorm) &&
          (isAdmin || normalizedProdLoc.includes(sectionNorm));
      });

      const dataBody = document.getElementById('data-body');
      dataBody.innerHTML = '';

      // Render all rows directly (no group headers)
      filteredByRole
        .sort((a, b) => {
          // optional: keep your previous sorting logic
          const aNeedsTimeout = a.time_in && !a.time_out;
          const bNeedsTimeout = b.time_in && !b.time_out;

          if (aNeedsTimeout && !bNeedsTimeout) return -1;
          if (!aNeedsTimeout && bNeedsTimeout) return 1;

          return new Date(a.created_at) - new Date(b.created_at);
        })
        .forEach(item => renderRow(item));

      document.getElementById('last-updated').textContent =
        `Last updated: ${new Date().toLocaleString()}`;
    }

    function renderRow(item) {
      if (item.status === 'done') return;

      const itemDataAttr = encodeURIComponent(JSON.stringify(item));
      const hasTimeIn = !!item.time_in;
      const hasTimeOut = !!item.time_out;

      const actionButton = hasTimeIn && hasTimeOut ?
        `<span class="btn btn-sm btn-primary">Done</span>` :
        `<button type="button" 
           class="btn btn-sm btn-${hasTimeIn ? 'warning' : 'success'} time-action-btn"
           data-item="${itemDataAttr}"
           data-mode="${hasTimeIn ? 'time-out' : 'time-in'}">
             ${hasTimeIn ? 'Ongoing' : 'Pending'}
           </button>`;

      const row = document.createElement('tr');
      row.innerHTML = `
          <td style="text-align: center;">
      <span style="cursor: pointer; margin-right: 12px;" onclick="handleRefresh('${item.id}','stamping','${item.section}','stamping')">🔄</span>
    </td>
        <td style="text-align:center;">
            ${highlightText(item.material_no || '<i>Null</i>', currentFilterQuery)}
        </td>
        <td style="text-align:center;white-space: normal; word-wrap: break-word;">
            ${highlightText(item.components_name || '<i>Null</i>', currentFilterQuery)}
        </td>
        <td style="text-align:center;">
            ${highlightText(item.stage_name || '', currentFilterQuery)}
        </td>
        <td style="text-align:center;white-space: normal; word-wrap: break-word;">
            ${highlightText(item.machine_name || '', currentFilterQuery)}
        </td>
        <td style="text-align:center;">
            ${highlightText(item.total_quantity ?? '<i>Null</i>', currentFilterQuery)}
        </td>
        <td style="text-align:center;">
            ${highlightText(item.pending_quantity ?? '<i>0</i>', currentFilterQuery)}
        </td>
        <td style="text-align:center;">
            ${highlightText(
                (item.person_incharge || '<i>Null</i>') +
                (item.by_order ? ` (${item.by_order})` : ''),
                currentFilterQuery
            )}
        </td>
        <td style="text-align:center;">
            ${highlightText(item.time_in || '<i>Null</i>', currentFilterQuery)} /
            ${highlightText(item.time_out || '<i>Null</i>', currentFilterQuery)}
        </td>
        <td style="text-align:center;">${actionButton}</td>
        <td style="text-align:center;">
            <button class="btn btn-sm"
                    onclick="viewStageStatus('${item.material_no}', '${item.components_name}', '${item.batch}')"
                    title="View Stages">🔍</button>
        </td>`;

      document.getElementById('data-body').appendChild(row);
    }



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

    function openQRModal(mode) {
      scanQRCodeForUser({
        section: "STAMPING",
        role: "OPERATOR",
        userProductionLocation: userProductionLocation,
        onSuccess: ({
          user_id,
          full_name
        }) => {
          console.log('QR Scan Success:', full_name, user_id);

          // Filter rows assigned to this person
          let mappedRows = fullData.filter(item =>
            (item.person_incharge || '').toLowerCase() === full_name.toLowerCase() &&
            item.status?.toLowerCase() !== 'done'
          );

          if (!mappedRows.length) {
            showAlert('info', 'No Pending Tasks', `There are no pending rows assigned to ${full_name}.`);
            return;
          }


          // Prioritize by null time_in or time_out + by_order
          mappedRows.sort((a, b) => {
            if (mode === 'time_in') {
              const aNull = a.time_in == null ? 0 : 1;
              const bNull = b.time_in == null ? 0 : 1;
              if (aNull !== bNull) return aNull - bNull;
            } else {
              const aNull = a.time_out == null ? 0 : 1;
              const bNull = b.time_out == null ? 0 : 1;
              if (aNull !== bNull) return aNull - bNull;
            }
            return (a.by_order || 0) - (b.by_order || 0);
          });

          const firstRow = mappedRows[0];
          if (!firstRow) return;

          if (mode === 'time_in') {
            handleTimeIn(firstRow, user_id, full_name);
          } else if (mode === 'time_out') {
            handleTimeOut(firstRow, user_id, full_name);
          }
        }
      });
    }


    // ----- TIME-IN LOGIC -----
    function handleTimeIn(selectedRowData, user_id, full_name) {
      const stage = parseInt(selectedRowData.stage || 0);
      const batch = selectedRowData.batch;
      const relatedItems = fullData.filter(item => item.batch === batch);
      const maxTotalQuantity = Math.max(...relatedItems.map(i => i.total_quantity || 0));

      function proceedToAPI() {
        showMachineSelection(machine, selectedRowData.machine_name, selectedRowData.section).then(result => {
          if (result.isConfirmed) {
            selectedRowData.remarks = result.value.remarks || '';

            // ✅ Send to API directly here
            const {
              material_no,
              components_name,
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
              material_description: components_name,
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

            fetch('api/stamping/timeinOperator', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json'
                },
                body: JSON.stringify(postData)
              })
              .then(res => res.json())
              .then(response => {
                if (response.status === 'success') {
                  showAlert('success', 'Success', response.message || `Time-In recorded.`);
                  setTimeout(() => window.location.reload(), 2000); // reload after success
                } else {
                  showAlert('error', 'Error', response.message || 'Something went wrong.');
                }
              })
              .catch(err => {
                console.error(err);
                showAlert('error', 'Error', 'Something went wrong.');
              });

          }
        });
      }


      if (stage === 1) {
        const stage1Items = relatedItems.filter(item => parseInt(item.stage) === 1);
        const sumStage1Quantity = stage1Items.reduce((sum, item) => sum + (item.quantity || 0), 0);
        if (sumStage1Quantity >= maxTotalQuantity) {
          proceedToAPI();
          return;
        }
      }

      if (stage > 1) {
        const prevStage = stage - 1;
        const prevStageItems = relatedItems.filter(item => parseInt(item.stage) === prevStage);

        const hasOngoing = prevStageItems.some(item => item.status?.toLowerCase() === 'ongoing');
        const allDone = prevStageItems.length > 0 &&
          prevStageItems.every(item => item.status?.toLowerCase() === 'done');
        const quantityCompleted = prevStageItems.reduce((sum, item) => sum + (item.quantity || 0), 0);
        const prevStageCompleted = quantityCompleted >= maxTotalQuantity;
        const hasAnyDone = prevStageItems.some(item => item.status?.toLowerCase() === 'done');

        const isSpecialSection = ['finishing', 'l300 assy']
          .includes((selectedRowData.section || '').toLowerCase());

        if (isSpecialSection) {
          if (!hasOngoing && !hasAnyDone && !allDone) {
            showAlert('warning', 'Cannot Time-In', `The previous process didn't meet its requirements to proceed.`);
            return;
          }
        } else if (!hasOngoing && !allDone && !prevStageCompleted && !hasAnyDone) {
          showAlert('warning', 'Cannot Time-In', `The previous process didn't meet its requirements to proceed.`);
          return;
        }

      }

      proceedToAPI();
    }

    function showMachineSelection(machineList, currentMachineName, currentSection) {
      return Swal.fire({
        icon: 'warning',
        title: 'Transferring Machine?',

        html: `
    <div style="text-align: center; font-size: 0.95rem; margin-bottom: 5px;">
<p>If you're transferring, select the new section and machine. Otherwise, leave both selections empty to remain on your current machine: ${currentSection} _ ${currentMachineName}.</p>
</div>

<div style="display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 5px 10px;">
  <select id="sectionSelect" class="swal2-select" style="
    width: 60%;
    padding: 8px 12px;
    font-size: 0.95rem;
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
    width: 60%;
    padding: 8px 12px;
    font-size: 0.95rem;
    border-radius: 6px;
    border: 1px solid #ccc;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    text-align: center;
  ">
    <option value="">Select Machine</option>
  </select>
</div>

    `,
        width: 500,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Continue',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
          const section = document.getElementById('sectionSelect')?.value || '';
          const name = document.getElementById('machineSelect')?.value || '';
          const remarks = section && name ? `${section} : ${name}` : '';
          console.log('PRECONFIRM:', {
            section,
            name,
            remarks
          });
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


    // ----- TIME-OUT LOGIC -----
    function handleTimeOut(selectedRowData, user_id, full_name) {
      const quantityModal = new bootstrap.Modal(document.getElementById('quantityModal'));
      document.getElementById('timeoutQuantity').value = selectedRowData.pending_quantity || 1;

      const confirmBtn = document.getElementById('confirmQuantityBtn');
      confirmBtn.onclick = () => {
        const inputQuantity = parseInt(document.getElementById('timeoutQuantity').value, 10);

        if (!inputQuantity || inputQuantity <= 0) {
          showAlert('warning', 'Invalid Quantity', 'Please enter a valid, positive quantity greater than 0.');
          return;
        }

        const referenceNo = selectedRowData.reference_no;
        let totalQuantity = parseInt(selectedRowData.total_quantity, 10) || 0;
        let pendingQuantity = parseInt(selectedRowData.pending_quantity, 10) || 0;
        const manpower = selectedRowData.manpower;

        const componentName = selectedRowData.components_name;

        const sumQuantity = fullData
          .filter(row =>
            row.reference_no === referenceNo &&
            row.components_name === componentName
          )
          .reduce((sum, row) => sum + (parseInt(row.quantity, 10) || 0), 0);

        if (manpower > 0) {
          pendingQuantity *= 2;
          totalQuantity *= 2;
        }

        if (inputQuantity > pendingQuantity) {
          showAlert('error', 'Pending Quantity Limit Exceeded', `
        You entered ${inputQuantity} units.
        But only ${pendingQuantity} units are pending for processing.
        Please adjust your quantity accordingly.
    `);
          return;
        }

        if (sumQuantity + inputQuantity > totalQuantity) {
          const remaining = totalQuantity - sumQuantity;
          showAlert('error', 'Total Quantity Limit Exceeded', `
        Reference #: ${referenceNo}
        Already processed: ${sumQuantity} / ${totalQuantity}
        Your input of ${inputQuantity} would exceed the total allowed.
        You can only process up to ${remaining} more units.
    `);
          return;
        }

        // Validate against previous stage done quantity
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
            showAlert(
              'error',
              'Exceeded Previous Process Output',
              `You entered ${inputQuantity} units for Time-Out.\nBut only ${doneQtyFromPrevStage} units were completed in the previous process.\nPlease enter a quantity within that limit.`
            );
            return;
          }

        }

        // ✅ Set the inputQuantity
        selectedRowData.inputQuantity = inputQuantity;
        quantityModal.hide();

        // ----- POST TO TIME-OUT API DIRECTLY -----
        const {
          material_no,
          components_name,
          id,
          pending_quantity,
          quantity,
          inputQuantity: iq,
          total_quantity,
          process_quantity,
          remarks,
          pair,
          stage,
          reference_no,

          duplicated,
          model,
          customer_id
        } = selectedRowData;

        const postData = {
          id,
          material_no,
          material_description: components_name,
          userId: user_id, // replace with logged-in user ID
          name: full_name, // replace with logged-in user name
          quantity,
          inputQuantity: iq,
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
        fetch('api/stamping/timeoutOperator', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(postData)
          })
          .then(res => res.json())
          .then(response => {
            if (response.status === 'success') {
              showAlert('success', 'Success', response.message || 'Time-Out recorded.');
              // optional reload after short delay
              setTimeout(() => window.location.reload(), 1500);
            } else {
              showAlert('error', 'Error', response.message || 'Something went wrong.');
            }
          })
          .catch(err => {
            console.error(err);
            showAlert('error', 'Error', 'Network error occurred.');
          });


        console.log('Time-Out Selected Row:', selectedRowData);
      };

      quantityModal.show();
    }


    function closeInspectionModal() {
      quantityModal.hide()
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

    function viewStageStatus(materialNo, componentName, batch) {
      fetch('api/stamping/getComponentStatus', {
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

            const seen = new Set();
            stages = stages.filter(stage => {
              const key = stage.stage;
              if (seen.has(key)) return false;
              seen.add(key);
              return true;
            });

            let content = '<i>No stages found</i>';

            if (stages.length > 0) {
              content = `
                    <div style="display: flex; flex-wrap: wrap; gap: 16px; justify-content: center; padding: 10px; overflow-x: auto;">
                        ${stages.map(stage => renderStageBox(stage)).join('')}
                    </div>
                `;
            }

            Swal.fire({
              title: 'Component Status',
              html: content,
              icon: 'info',
              width: '80%',
              showConfirmButton: true
            });
          } else {
            showAlert('error', 'Error', data.message || 'Could not fetch stage data.');
          }
        })
        .catch(err => {
          console.error('Fetch error:', err);
          showAlert('error', 'Error', 'Something went wrong.');
        });

      function renderStageBox(stage) {
        return `
            <div style="
                border: 1px solid #ccc; 
                padding: 10px; 
                width: 200px; 
                min-width: 150px; 
                border-radius: 8px; 
                box-shadow: 1px 1px 5px rgba(0,0,0,0.1);
                flex: 1 1 200px;
                text-align: center;
            ">
                <b>Section:</b> ${stage.section}<br>
                <b>Process:</b> ${stage.stage_name}<br>
                <b>Status:</b> <span style="color: ${stage.status === 'done' ? 'green' : 'orange'}">${stage.status}</span><br>
                <small>(${stage.stage})</small>
            </div>
        `;
      }
    }
  </script>
  <style>
    /* Default: no scroll */
    .table-responsive-wrapper {
      width: 100%;
    }

    /* Tablet only: force horizontal scroll */
    @media (min-width: 768px) and (max-width: 991.98px) {
      .table-responsive-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        position: relative;
      }

      .table-responsive-wrapper table {
        min-width: 900px;
        /* ensure table is wide enough to trigger scroll */
        table-layout: fixed;
      }

      .table-responsive-wrapper th,
      .table-responsive-wrapper td {
        white-space: nowrap;
        /* prevent text wrapping so scrolling is smoother */
      }
    }
  </style>