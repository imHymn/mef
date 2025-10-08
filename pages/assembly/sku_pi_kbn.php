<?php include './components/reusable/tablesorting.php'; ?>

<?php include './components/reusable/qrcodeScanner.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<?php include './components/reusable/reset_timein.php'; ?>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
<!-- <style>
  /* CSS for hover effect on specific rows */
  tr.hoverable-row:hover {
    background-color: #ddd9d9ff;
    cursor: pointer;
  }
</style> -->
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
      <li class="breadcrumb-item" aria-current="page">Assembly Section</li>
    </ol>
  </nav>

  <div class="row">

    <div class="col-md-12 grid-margin stretch-card">

      <div class="card">

        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="card-title mb-0">Production Instruction Kanban Board (SKU)</h6>
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
            <div class="table-responsive">
              <table class="custom-hover table" style="table-layout: fixed; min-width: 1200px; width: 100%;">
                <thead>
                  <tr>
                    <th class="sticky-col col-1" style="width: 5%; text-align: center;"><span class="sort-icon"></span></th>
                    <th class="sticky-col col-2" style="width: 10%; text-align: center;">Material No <span class="sort-icon"></span></th>
                    <th class="sticky-col col-3" style="width: 10%; text-align: center; white-space: normal; word-wrap: break-word;">Material Description <span class="sort-icon"></span></th>
                    <th style="width: 10%; text-align: center; white-space: normal; word-wrap: break-word;">Sub Component <span class="sort-icon"></span></th>
                    <th style="width: 7%; text-align: center;">Process <span class="sort-icon"></span></th>
                    <th style="width: 5%; text-align: center;">Lot <span class="sort-icon"></span></th>
                    <th style="width: 8%; text-align: center;">Pending Qty <span class="sort-icon"></span></th>
                    <th style="width: 8%; text-align: center;">Total Qty <span class="sort-icon"></span></th>
                    <th style="width: 10%; text-align: center; white-space: normal; word-wrap: break-word;">Person Incharge <span class="sort-icon"></span></th>
                    <th style="width: 10%; text-align: center; white-space: normal; word-wrap: break-word;">Date needed <span class="sort-icon"></span></th>
                    <th style="width: 8%; text-align: center;">Shift <span class="sort-icon"></span></th>
                    <th style="width: 10%; text-align: center; white-space: normal; word-wrap: break-word;">Time In / Time out <span class="sort-icon"></span></th>
                  </tr>
                </thead>
                <tbody id="data-body"></tbody>
              </table>
            </div>
          </div>


          <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center" id="pagination"></ul>
          </nav>


        </div>
      </div>
    </div>
    <!-- Drawer -->


  </div>



  <div class="modal fade" id="quantityModal" tabindex="-1" aria-labelledby="quantityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="quantityForm" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="quantityModalLabel">Enter Quantity</h5>

        </div>
        <div class="modal-body">
          <input
            type="number"
            class="form-control"
            id="quantityInput"
            name="quantity"
            min="1"
            placeholder="Enter quantity"
            required />
          <div class="invalid-feedback">
            Please enter a valid quantity (1 or more).
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="cancelQuantityBtn">Cancel</button>

          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
      </form>
    </div>
  </div>


  <script src="assets/js/sweetalert2@11.js"></script>
  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/jquery.min.js"></script>
  <link rel="stylesheet" href="assets/css/choices.min.css" />
  <script src="assets/js/choices.min.js"></script>

  <script>
    let assemblyData = [];
    let currentMaterialId = null;
    let currentItem = null;
    let currentMode = null;
    let timeout_id = null;
    let quantityModal;
    let currentPage = 1;
    const rowsPerPage = 10;
    let originalPageData = null;
    let paginatedData = [];
    let paginator = null;
    const userRole = "<?= $role ?>";
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;

    quantityModal = new bootstrap.Modal(document.getElementById('quantityModal'));
    let globalSection = null;
    let pageDataGlobal = []; // <-- global reference to the current page data

    let model = null;

    function getData(model) {

      fetch('api/assembly/getAllData_assigned?model=' + encodeURIComponent(model || 'L300 DIRECT') + '&_=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
          deliveryData = data.delivery;
          assemblyData = data.assembly;
          let filteredDeliveryData = deliveryData

            .filter(item => item.process !== 'import');

          filteredDeliveryData = filteredDeliveryData.filter(item => {
            const pending = parseInt(item.assembly_pending ?? item.total_quantity) || 0;
            if (item.status === "done") return false;
            const itemSection = (item.assembly_section ?? '')
              .toLowerCase()
              .replace(/[- ]/g, '');

            let rawUserLoc = userProductionLocation;
            if (typeof rawUserLoc === "string") {
              try {
                rawUserLoc = JSON.parse(rawUserLoc);
              } catch (e) {
                rawUserLoc = [rawUserLoc]; // fallback to single-item array
              }
            }
            const userLoc = (Array.isArray(rawUserLoc) ? rawUserLoc : [rawUserLoc]).map(loc =>
              (loc ?? '').toLowerCase().replace(/[- ]/g, '')
            );

            if (userRole === 'administrator') return true;
            if (userRole === 'supervisor' || userRole === 'line leader') {
              return userLoc.includes(itemSection);
            }

            return true;
          });

          const sortFn = (a, b) => {
            const aAssembly = assemblyData.find(x => String(x.itemID) === String(a.id));
            const bAssembly = assemblyData.find(x => String(x.itemID) === String(b.id));

            const aCanTimeout = aAssembly && aAssembly.time_in && !aAssembly.time_out ? 1 : 0;
            const bCanTimeout = bAssembly && bAssembly.time_in && !bAssembly.time_out ? 1 : 0;
            if (aCanTimeout !== bCanTimeout) return bCanTimeout - aCanTimeout;

            const aInProgress = aAssembly && !!aAssembly.time_in ? 1 : 0;
            const bInProgress = bAssembly && !!bAssembly.time_in ? 1 : 0;
            if (aInProgress !== bInProgress) return bInProgress - aInProgress;

            const aContinue = a.status?.toLowerCase() === 'continue' ? 1 : 0;
            const bContinue = b.status?.toLowerCase() === 'continue' ? 1 : 0;
            if (aContinue !== bContinue) return bContinue - aContinue;

            const dateA = new Date(a.date_needed);
            const dateB = new Date(b.date_needed);
            if (dateA.getTime() !== dateB.getTime()) return dateA - dateB;

            const lotA = parseInt(a.lot_no) || 0;
            const lotB = parseInt(b.lot_no) || 0;
            return lotA - lotB;
          };

          filteredDeliveryData.sort(sortFn);

          lotGroups = {};
          filteredDeliveryData.forEach(item => {
            let lotKey = item.lot_no;

            if (!lotKey) {
              if (item.created_at) {
                lotKey = new Date(item.created_at).toISOString(); // Use full datetime for grouping
              } else {
                lotKey = 'NO_LOT';
              }
            }

            if (!lotGroups[lotKey]) lotGroups[lotKey] = [];
            lotGroups[lotKey].push(item);
          });

          lotPages = Object.values(lotGroups);
          currentPage = 0;

          renderPaginationControls();
          renderPaginatedTable(lotPages[currentPage]);

          const searchableFields = [
            'material_no',
            'material_description',
            'model',
            'assembly_section',
            'lot_no',
            'person_incharge',
            'date_needed',
            'shift',
            'assembly_process',
            'sub_component',

          ];

          setupSearchFilter({
            filterInputSelector: '#filter-input',
            data: filteredDeliveryData.flat(), // or keep as a single array
            searchableFields,
            onFilter: (filtered, query) => {
              currentFilterQuery = query; // update global query for highlighting
              renderPaginatedTable(filtered);
            }
          });


        });

    }

    function renderPaginatedTable(pageData) {
      const tbody = document.getElementById('data-body');
      tbody.innerHTML = '';
      pageDataGlobal = [...pageData];
      originalPageData = [...pageData];

      pageData.forEach(item => {
        if (item.assembly_section === 'PAINTING' || item.assembly_section === 'FINISHING') return; // only show assembly section
        const assemblyRecord = assemblyData.find(a => String(a.itemID) === String(item.id));
        const currentRef = item.reference_no;
        const currentSection = item.assembly_section?.trim() || 'NONE';

        let timeStatus = '';
        if (!assemblyRecord) {
          timeStatus = `<button class="btn btn-sm btn-success time-in-btn"
                data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'
                data-materialid="${item.material_no}"
                data-itemid="${item.id}"
                data-mode="timeIn">PENDING</button>`;
        } else if (!assemblyRecord.time_in) {
          timeStatus = `<button class="btn btn-sm btn-success time-in-btn"
                data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'
                data-materialid="${item.material_no}"
                data-itemid="${assemblyRecord.itemID}"
                data-id="${assemblyRecord.id}"
                data-mode="timeIn">PENDING</button>`;

        } else if (assemblyRecord.time_in && !assemblyRecord.time_out) {
          const relatedAssemblyData = assemblyData.filter(a => a.reference_no === currentRef);
          timeStatus = `<button class="btn btn-sm btn-warning time-out-btn"
                data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'
                data-materialid="${item.material_no}"
                data-itemid="${assemblyRecord.itemID}"
                data-id="${assemblyRecord.id}"
                data-mode="timeOut"
                data-assemblyItem='${JSON.stringify(relatedAssemblyData).replace(/'/g, "&apos;")}'>ONGOING</button>`;
        } else {
          timeStatus = `<span class="btn btn-sm bg-success">DONE</span>`;
        }

        const row = document.createElement('tr');
        row.classList.add('hoverable-row');

        row.innerHTML = `
            <td style="text-align: center;" class="sticky-col col-1">
                <span style="cursor: pointer; margin-right: 12px;" onclick="handleRefresh('${item.id}','assembly','${item.assembly_section}','assembly','assembly')">🔄</span>
            </td>
            <td style="text-align: center;"class="sticky-col col-2">
                ${highlightText(item.material_no + (item.fuel_type ? ` (${item.fuel_type})` : ""), currentFilterQuery)}
            </td>
            <td style="text-align: center; white-space: normal; word-wrap: break-word;"class="sticky-col col-3">
                ${highlightText(item.material_description, currentFilterQuery)}
            </td>
            <td style="text-align: center; white-space: normal; word-wrap: break-word;">
                ${highlightText(item.sub_component && item.sub_component.replace(/"/g, '') !== item.material_description
                    ? item.sub_component.replace(/"/g, '')
                    : '<i>NONE</i>', currentFilterQuery)}
            </td>
            <td style="text-align: center; white-space: normal; word-wrap: break-word;">
                ${highlightText(item.assembly_process ? item.assembly_process.replace(/"/g, '') : '<i>NONE</i>', currentFilterQuery)}
            </td>
            <td style="text-align: center;">
                ${highlightText(item.lot_no ? `${item.variant} - ${item.lot_no}` : '<i>NONE</i>', currentFilterQuery)}
            </td>
            <td style="text-align: center;">${highlightText(item.assembly_pending ?? item.total_quantity, currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.total_quantity, currentFilterQuery)}</td>
     <td style="text-align: center; white-space: normal; word-wrap: break-word;">
  ${highlightText(
      item.person_incharge + (item.by_order ? ` (${item.by_order})` : ''),
      currentFilterQuery
  )}
</td>


            <td style="text-align: center;">${highlightText(item.date_needed, currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.shift, currentFilterQuery)}</td>
            <td style="text-align: center;" disabled>${timeStatus}</td>
        `;
        tbody.appendChild(row);
      });

      document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
    }



    document.getElementById('cancelQuantityBtn').addEventListener('click', () => {
      quantityModal.hide();
    });

    const filterColumnSelect = document.getElementById('filter-column');
    const filterInput = document.getElementById('filter-input');
    const tbody = document.getElementById('data-body');

    function openQRModal(mode) {
      console.log('User production location:', userProductionLocation);
      return new Promise((resolve) => {


        scanQRCodeForUser({
          section: 'ASSEMBLY',
          role: 'operator',
          userProductionLocation: userProductionLocation,
          onSuccess: async ({
            user_id,
            full_name
          }) => {


            // Filter items assigned to this user and sort by by_order ascending
            const matchedItems = pageDataGlobal
              .filter(item => item.person_incharge === full_name)
              .sort((a, b) => (a.by_order ?? 0) - (b.by_order ?? 0));

            if (matchedItems.length === 0) {
              showAlert('warning', 'Not Found', `No item found assigned to "${full_name}".`);
              return resolve(false);
            }

            // Find the item to process
            const itemToProcess = matchedItems.find(item => {
              const assemblyRecord = assemblyData.find(a => String(a.itemID) === String(item.id));

              if (mode === 'time_in') {
                // If no record yet, it means not timed in yet → allow
                return !assemblyRecord || !assemblyRecord.time_in;
              }

              // For time_out: must have a record
              if (!assemblyRecord) return false;

              const allPrevDone = matchedItems
                .filter(x => (x.by_order ?? 0) < (item.by_order ?? 0))
                .every(x => {
                  const rec = assemblyData.find(a => String(a.itemID) === String(x.id));
                  return rec?.time_in && rec?.time_out;
                });

              const hasTimeIn = !!assemblyRecord.time_in;
              const notTimedOutYet = !assemblyRecord.time_out;

              return hasTimeIn && notTimedOutYet && allPrevDone;
            });

            if (!itemToProcess) {
              showAlert(
                'info',
                mode === 'time_in' ? 'All Timed In' : 'All Timed Out',
                `All tasks for this user are already ${mode === 'time_in' ? 'timed in' : 'timed out'}, or previous tasks need to be completed first.`
              );

              return resolve(false);
            }

            if (mode === 'time_in') {
              const canProceed = await processTimeIn(itemToProcess);
              if (canProceed) {
                timeIn(itemToProcess, full_name);
                resolve(true);
              } else {
                resolve(false);
              }
            } else if (mode === 'time_out') {

              const completed = await processTimeOut(itemToProcess, full_name);
              if (completed) resolve(true);
              else resolve(false);
            }
          },
          onCancel: () => resolve(false)
        });
      });
    }



    function timeIn(item, full_name) {
      const assemblyData = {
        id: item.id,
        itemID: item.id,
        model: item.model,
        shift: item.shift,
        lot_no: item.lot_no,
        date_needed: item.date_needed,
        reference_no: item.reference_no,
        material_no: item.material_no,
        material_description: item.material_description,
        pending_quantity: item.assembly_pending ?? item.total_quantity,
        total_qty: item.total_quantity,
        full_name: full_name,
        process: item.process,
        manpower: item.manpower,
        status: 'pending',
        section: 'assembly',
        assembly_section: item.assembly_section,
        assembly_section_no: item.assembly_section_no,
        assembly_process: item.assembly_process,
        process_no: item.process_no,
        total_process: item.total_process,
        sub_component: item.sub_component,
        variant: item.variant,
        duplicated: item.duplicated,
        fuel_type: item.fuel_type,
        cycle_time: item.cycle_time
      };

      fetch('api/assembly/timeinOperator', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(assemblyData)
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showAlert('success', 'Success', 'Assembly record assigned successfully.');
            setTimeout(() => window.location.reload(), 2000); // reload after alert closes
          } else {
            showAlert('error', 'Error', data.message || 'Failed to assign assembly record.');
          }
        })
        .catch(err => {
          console.error(err);
          showAlert('error', 'Error', 'Something went wrong.');
        });

    }

    function timeOut(item, full_name, qty) {
      const assemblyData = {
        id: item.id,
        itemID: item.id,
        model: item.model,
        shift: item.shift,
        lot_no: item.lot_no,
        date_needed: item.date_needed,
        reference_no: item.reference_no,
        material_no: item.material_no,
        material_description: item.material_description,
        done_quantity: qty, // <-- from modal
        inputQty: qty, // <-- from modal
        pending_quantity: item.assembly_pending ?? item.total_quantity,
        total_qty: item.total_quantity,
        full_name: full_name, // <-- scanned user
        process: item.process,
        manpower: item.manpower,
        status: 'pending',
        section: 'assembly',
        assembly_section: item.assembly_section,
        assembly_section_no: item.assembly_section_no,
        assembly_process: item.assembly_process,
        process_no: item.process_no,
        total_process: item.total_process,
        sub_component: item.sub_component,
        variant: item.variant,
        duplicated: item.duplicated,
        fuel_type: item.fuel_type,
        cycle_time: item.cycle_time,
        customer_id: item.customer_id ?? null
      };



      fetch('api/assembly/timeoutOperator', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(assemblyData)
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showAlert('success', 'Success', 'Assembly record unassigned successfully.');
            setTimeout(() => window.location.reload(), 2000); // wait for alert before reload
          } else {
            showAlert('error', 'Error', data.message || 'Failed to unassign assembly record.');
          }
        })
        .catch(err => {
          console.error(err);
          showAlert('error', 'Error', 'Something went wrong.');
        });

    }





    // -------------------- TIME OUT --------------------
    // -------------------- TIME OUT --------------------
    function processTimeOut(item, full_name) {
      return new Promise((resolve) => {
        currentMaterialId = item.material_no;
        currentItem = item;
        currentMode = 'timeOut';

        const quantityModal = new bootstrap.Modal(document.getElementById('quantityModal'));
        const quantityForm = document.getElementById('quantityForm');
        const quantityInput = document.getElementById('quantityInput');

        quantityInput.value = '';
        quantityInput.classList.remove('is-invalid');

        // Show modal
        quantityModal.show();

        // Listen for form submission
        const submitHandler = (e) => {
          e.preventDefault(); // prevent actual form submission
          const qty = parseFloat(quantityInput.value);
          if (isNaN(qty) || qty <= 0) {
            quantityInput.classList.add('is-invalid');
            return;
          }

          // Call your actual time-out logic
          timeOut(currentItem, full_name, qty);

          // Cleanup listener
          quantityForm.removeEventListener('submit', submitHandler);

          // Hide modal
          quantityModal.hide();

          // Resolve promise
          resolve(true);
        };

        quantityForm.addEventListener('submit', submitHandler);

        // Optional: handle cancel button
        const cancelBtn = document.getElementById('cancelQuantityBtn');
        const cancelHandler = () => {
          quantityForm.removeEventListener('submit', submitHandler);
          cancelBtn.removeEventListener('click', cancelHandler);
          quantityModal.hide();
          resolve(false);
        };
        cancelBtn.addEventListener('click', cancelHandler);
      });
    }


    document.getElementById('quantityForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const quantityInput = document.getElementById('quantityInput');
      const quantity = parseInt(quantityInput.value, 10);
      if (!quantity || quantity < 1) {
        quantityInput.classList.add('is-invalid');
        quantityInput.focus();
        return;
      }
      quantityInput.classList.remove('is-invalid');

      const currentRef = currentItem.reference_no;
      const currentSection = currentItem.assembly_section?.trim();
      const currentProcessNo = parseInt(currentItem.process_no ?? 1);

      const relatedAssemblies = Array.isArray(assemblyData) ?
        assemblyData.filter(item =>
          item.reference_no === currentRef &&
          item.assembly_section?.trim() === currentSection &&
          parseInt(item.process_no ?? 1) === currentProcessNo
        ) : [];

      const totalDone = relatedAssemblies.reduce((sum, record) => {
        const done = parseInt(record.done_quantity, 10);
        return sum + (isNaN(done) ? 0 : done);
      }, 0);

      const maxQuantity = parseInt(currentItem.total_quantity, 10) || 0;

      const totalIfSubmitted = totalDone + quantity;

      if (totalIfSubmitted > maxQuantity) {
        showAlert(
          'warning',
          'Exceeded Quantity',
          `The total quantity being assembled for <b>Reference No:</b> ${currentRef}, section <b>${currentSection}</b>, and process <b>#${currentProcessNo}</b> exceeds the allowed maximum.<br><br>
    <b>Total Already Done:</b> ${totalDone}<br>
    <b>Input:</b> ${quantity}<br>
    <b>Maximum Allowed:</b> ${maxQuantity}`
        );

        quantityInput.classList.add('is-invalid');
        quantityInput.focus();
        return;
      }

      quantityInput.classList.remove('is-invalid');
      quantityModal.hide();


    });



    function processTimeIn(item) {
      const totalQty = item.total_quantity;
      const material_no = item.material_no;

      return fetch('api/assembly/getMaterialComponent', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            material_no
          })
        })
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok');
          return response.json();
        })
        .then(data => {
          let blockDueToStock = false;
          let criticalItems = [];
          let warningItems = [];
          let insufficientItems = [];
          let normalItems = [];

          data.forEach(component => {
            const {
              actual_inventory,
              critical,
              minimum,
              reorder,
              normal
            } = component;

            if (actual_inventory < totalQty) {
              insufficientItems.push(component);
              blockDueToStock = true;
            } else if (actual_inventory >= normal || actual_inventory >= minimum) {
              normalItems.push(component);
            } else if (actual_inventory <= critical) {
              criticalItems.push(component);
            } else if (actual_inventory <= minimum || actual_inventory <= reorder) {
              warningItems.push(component);
            }
          });

          if (insufficientItems.length > 0) {
            const list = insufficientItems
              .map(i => `<li>${i.components_name}: ${i.actual_inventory} in stock</li>`)
              .join('');

            showAlert(
              'error',
              'Cannot Proceed',
              `The following components don't have enough stock:<br><ul style="text-align: left;">${list}</ul>`
            );

            return false; // cannot proceed
          }


          let swalOptions;
          if (normalItems.length > 0) {
            swalOptions = {
              icon: 'success',
              title: 'Material Stocks',
              html: `The following components are sufficiently stocked for (${item.material_no}) ${item.material_description}.<br>Proceed?`,
              showCancelButton: true,
              confirmButtonText: 'Yes, Proceed',
              cancelButtonText: 'Cancel'
            };
          } else {
            let htmlContent = '';
            if (criticalItems.length > 0) {
              htmlContent += `<strong style="color: red;">Critical Level:</strong><ul style="text-align: left;">${
            criticalItems.map(i => `<li>${i.components_name}: ${i.actual_inventory} in stock</li>`).join('')
          }</ul>`;
            }
            if (warningItems.length > 0) {
              htmlContent += `<strong style="color: orange;">Low Stock Warning:</strong><ul style="text-align: left;">${
            warningItems.map(i => `<li>${i.components_name}: ${i.actual_inventory} in stock</li>`).join('')
          }</ul>`;
            }

            swalOptions = {
              icon: 'warning',
              title: 'Stock Level Alert',
              html: htmlContent + `<br>Proceed anyway?`,
              showCancelButton: true,
              confirmButtonText: 'Yes, Proceed',
              cancelButtonText: 'Cancel'
            };
          }

          return Swal.fire(swalOptions).then(result => {
            if (result.isConfirmed) {
              return true; // user confirmed
            }
            return false; // user cancelled
          });
        })
        .catch(err => {
          console.error(err);
          return false; // in case of errors
        });
    }


    function renderPaginationControls() {
      const container = document.getElementById('pagination');
      container.innerHTML = '';

      const totalPages = lotPages.length;
      const visibleCount = 3;
      let start = Math.max(0, currentPage - Math.floor(visibleCount / 2));
      let end = start + visibleCount;

      if (end > totalPages) {
        end = totalPages;
        start = Math.max(0, end - visibleCount);
      }

      const prevBtn = document.createElement('button');
      prevBtn.textContent = 'Previous';
      prevBtn.className = 'btn btn-sm btn-secondary mx-1';
      prevBtn.disabled = currentPage === 0;
      prevBtn.onclick = () => {
        if (currentPage > 0) {
          currentPage--;
          renderPaginatedTable(lotPages[currentPage]);
          renderPaginationControls();
        }
      };
      container.appendChild(prevBtn);

      for (let i = start; i < end; i++) {
        const lotNo = Object.keys(lotGroups)[i];
        const btn = document.createElement('button');
        const lotKey = Object.keys(lotGroups)[i];

        let displayLot = lotKey;
        if (!lotGroups[lotKey][0].lot_no && !isNaN(Date.parse(lotKey))) {
          displayLot = new Date(lotKey).toISOString().split('T')[0]; // YYYY-MM-DD
        }

        btn.textContent = `${displayLot}`;

        btn.className = 'btn btn-sm mx-1 ' + (i === currentPage ? 'btn-primary' : 'btn-outline-primary');
        btn.onclick = () => {
          currentPage = i;
          renderPaginatedTable(lotPages[currentPage]);
          renderPaginationControls();
        };
        container.appendChild(btn);
      }

      const nextBtn = document.createElement('button');
      nextBtn.textContent = 'Next';
      nextBtn.className = 'btn btn-sm btn-secondary mx-1';
      nextBtn.disabled = currentPage >= totalPages - 1;
      nextBtn.onclick = () => {
        if (currentPage < totalPages - 1) {
          currentPage++;
          renderPaginatedTable(lotPages[currentPage]);
          renderPaginationControls();
        }
      };
      container.appendChild(nextBtn);
    }




    enableTableSorting(".table");
  </script>
  <style>
    /* Table responsiveness wrapper */
    .table-responsive-wrapper {
      overflow-x: auto;
    }

    /* Sticky columns only for tablets (768px - 991px) */
    @media (min-width: 768px) and (max-width: 991.98px) {

      .custom-hover tbody tr:hover .col-1,
      .custom-hover tbody tr:hover .col-2,
      .custom-hover tbody tr:hover .col-3 {
        background-color: #dde0e2 !important;
      }


      /* First column */
      .col-1 {
        position: sticky;
        left: 0;
        background-color: #fff;
        z-index: 10;
        width: 5% !important;

      }

      /* Second column */
      .col-2 {
        position: sticky;
        left: 55px;
        /* width of col-1 */
        background-color: #fff;
        z-index: 4;

      }

      /* Third column */
      .col-3 {
        position: sticky;
        left: 170px;
        /* col-1 + col-2 width */
        background-color: #fff;
        z-index: 3;

      }
    }
  </style>