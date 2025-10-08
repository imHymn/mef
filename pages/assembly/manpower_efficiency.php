<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/tablepagination.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="assets/js/sweetalert2@11.js"></script>
<script src="/mes/components/reusable/data_modelbased.js"></script>
<script src="/mes/components/reusable/applyModelDrawer.js"></script>
<style>
  .custom-hover tbody tr:hover {
    background-color: #dde0e2ff !important;

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
            <table class="custom-hover table" style="table-layout: fixed; min-width: 1200px; width: 100%;">
              <thead>
                <tr>
                  <!-- <th style="width: 3%; text-align: center;"><span class="sort-icon"></span></th> -->
                  <th style="width: 10%; text-align: center;">Date <span class="sort-icon"></span></th>
                  <th style="width: 15%; text-align: center;">Material Description <span class="sort-icon"></span></th>
                  <th style="width: 15%; text-align: center;">Sub Component <span class="sort-icon"></span></th>
                  <th style="width: 15%; text-align: center;">Assembly Process <span class="sort-icon"></span></th>
                  <th style="width: 10%; text-align: center;">Person Incharge <span class="sort-icon"></span></th>
                  <th style="width: 10%; text-align: center;">Quantity <span class="sort-icon"></span></th>
                  <th style="width: 13%; text-align: center;">Target CT(Piece) <span class="sort-icon"></span></th>
                  <th style="width: 13%; text-align: center;">Actual CT(Piece) <span class="sort-icon"></span></th>
                  <th style="width: 13%; text-align: center;">Total Target CT<span class="sort-icon"></span></th>
                  <th style="width: 13%; text-align: center;">Total Actual CT <span class="sort-icon"></span></th>
                  <th style="width: 10%; text-align: center;">MPEFF<span class="sort-icon"></span></th>
                </tr>
              </thead>
              <tbody id="data-body"></tbody>
            </table>
          </div>


          <nav>
            <ul id="pagination" class="pagination justify-content-center"></ul>
          </nav>


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
    const userProduction = <?= json_encode($section) ?>;
    const userProductionLocation = <?= json_encode($specific_section) ?>;

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

    function handleRefresh(id, itemID, materialNo, materialDescription, lotNo, referenceNo, totalQuantity, duplicated, process_no) {
      Swal.fire({
        title: 'Supervisor Authorization Required',
        html: `
      <p>This will reset data for Material No: <strong>${id}</strong></p>
      <input type="password" id="supervisor-code" class="swal2-input" placeholder="Enter Supervisor Authorization Code">
      <input type="number" id="input-quantity" class="swal2-input" placeholder="Enter New Quantity (max: ${totalQuantity})" min="0" max="${totalQuantity}">`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, reset it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        preConfirm: () => {
          const code = document.getElementById('supervisor-code').value.trim();
          const quantity = document.getElementById('input-quantity').value.trim();

          if (!code) {
            Swal.showValidationMessage('Authorization code is required');
            return false;
          }

          const qtyNum = parseInt(quantity, 10);
          if (isNaN(qtyNum) || qtyNum < 0) {
            Swal.showValidationMessage('Please enter a valid quantity');
            return false;
          }

          if (qtyNum > totalQuantity) {
            Swal.showValidationMessage(`Quantity must not exceed ${totalQuantity}`);
            return false;
          }

          return {
            code,
            quantity: qtyNum
          };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const {
            code: supervisorCode,
            quantity
          } = result.value;

          fetch('api/assembly/reset_manpower.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                id,
                itemID,
                auth_code: supervisorCode,
                quantity,
                production_location: userProductionLocation,
                production: userProduction,
                role: userRole,
                material_no: materialNo,
                material_description: materialDescription,
                lot_no: lotNo,
                reference_no: referenceNo,
                total_quantity: totalQuantity,
                duplicated,
                process_no
              })
            })
            .then(async res => {
              const text = await res.text();
              try {
                const json = JSON.parse(text);

                if (json.success) {
                  showAlert(
                    'success',
                    'Reset Successful!',
                    `Data has been reset and quantity updated for Reference No: ${referenceNo}.`
                  );
                } else {
                  showAlert(
                    'error',
                    'Authorization Failed',
                    json.message || 'Invalid supervisor code or missing permission.'
                  );
                }
              } catch (err) {
                console.error('❌ JSON parse error:', err);
                showAlert(
                  'error',
                  'Invalid Server Response',
                  'Received invalid JSON response from the server.'
                );
              }
            })
            .catch(err => {
              console.error(err);
              showAlert(
                'error',
                'Request Failed',
                'Something went wrong. Please check your network or try again later.'
              );
            });
        }

      });
    }

    function renderPageCallback(pageData, ) {
      tbody.innerHTML = '';
      let assembly_targetmpeff = localStorage.getItem('ASSEMBLY_TARGETMPEFF');
      const targetMpeff = parseFloat(assembly_targetmpeff) || 0;
      console.log('Target MPEFF:', targetMpeff);
      const sectionGrouped = {};
      pageData.forEach(entry => {
        const sectionKey = (entry.section || 'NO SECTION').toUpperCase();
        if (!sectionGrouped[sectionKey]) sectionGrouped[sectionKey] = [];
        sectionGrouped[sectionKey].push(entry);
      });

      Object.entries(sectionGrouped).forEach(([section, entries]) => {
        const sectionKeyNormalized = section.replace(/\s|-/g, '').toLowerCase();
        const userLocationNormalized = (typeof userProductionLocation === 'string' ?
          userProductionLocation : '').replace(/\s|-/g, '').toLowerCase();



        // Group by date + component + person
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
                entry, // Keep ref for cycle lookup
              };
            }

            mergedRows[key].qty += qty;
            mergedRows[key].totalWorkSeconds += workSeconds;
            mergedRows[key].totalStandbySeconds += standbySeconds;
          });
        });

        Object.values(mergedRows).forEach(merged => {
          const {
            date,
            componentName,
            person,
            qty,
            totalWorkSeconds,
            totalStandbySeconds,
            material,
            entry,
            sub_component,
            assembly_process
          } = merged;


          const {
            source,
            materialNo,
            materialDescription,
            lotNo,
            reference
          } = entry;

          let cycle = parseFloat(entry.cycle_time) || 0;

          const actual = (totalWorkSeconds > 0 && qty > 0) ? (totalWorkSeconds / qty) : null;
          const mpeff = (cycle > 0 && totalWorkSeconds > 0 && qty > 0) ?
            ((cycle * qty) / totalWorkSeconds) * 100 :
            null;

          const row = document.createElement('tr');
          const safeSection = section.replace(/\s+/g, '_');
          row.classList.add(`batch-group-${safeSection}`);



          row.innerHTML = `
  <!--<td style="text-align: center;">
    <span style="cursor: pointer; margin-left: 8px;"
      onclick="handleRefresh(
        '${entry.id}',
        '${entry.itemID}',
        '${materialNo}',
        '${materialDescription}',
        '${lotNo}',
        '${entry.reference_no}',
        ${entry.totalQuantity},Wx
        '${entry.duplicated ?? 0}',
        '${entry.processNo}'
      )">🔄</span>
  </td>-->

  <td style="text-align: center;">${highlightText(date, currentFilterQuery)}</td>
  <td style="text-align: center; white-space: normal;">${highlightText(componentName, currentFilterQuery)}</td>
  <td style="text-align: center; white-space: normal;">${highlightText(entry.sub_component || '-', currentFilterQuery)}</td>
  <td style="text-align: center; white-space: normal;">${highlightText(entry.assembly_process || '-', currentFilterQuery)}</td>
  <td style="text-align: center; white-space: normal; word-wrap: break-word;">${highlightText(person, currentFilterQuery)}</td>
  <td style="text-align: center;">${qty}</td>
  <td style="text-align: center;">${Math.ceil(cycle)} sec</td>
  <td style="text-align: center;">${actual !== null ? `${Math.ceil(actual)} sec` : '-'}</td>
  <td style="text-align: center;">${Math.ceil(qty * cycle)} sec</td>
  <td style="text-align: center;">
    ${Math.ceil(totalWorkSeconds)} sec
    ${totalStandbySeconds > 0 ? ` (${Math.ceil(totalStandbySeconds)} sec)` : ''}
  </td>
  <td style="text-align: center; color: ${
    mpeff !== null && targetMpeff !== null ? (mpeff > targetMpeff ? 'green' : 'red') : 'inherit'
  };">
    ${mpeff !== null
      ? `${mpeff.toFixed(1)}% ${mpeff > targetMpeff
        ? '<span style="color: green;">▲</span>'
        : '<span style="color: red;">▼</span>'}`
      : '-'}
  </td>
`;

          if (cycle === 0) {
            row.style.backgroundColor = '#ffe6e6';
            row.title = '⚠️ Missing or unmatched cycle time';
          }

          tbody.appendChild(row);
        });
      });

      document.getElementById('last-updated').textContent =
        `Last updated: ${new Date().toLocaleString()}`;
    }



    function getData(model) {
      return Promise.all([
          fetch(`api/assembly/getAllAssemblyData?model=${encodeURIComponent(model)}`).then(res => res.json()),
        ]).then(([assemblyResp]) => {
          const assemblyData = assemblyResp.assembly || [];
          const stampingData = assemblyResp.stamping || [];
          const mergedData = {};

          function addEntry(id, itemID, materialNo, materialDescription, person, date, reference, timeIn, timeOut, finishedQty, material_no = '', component_name = '', section = '', source = '', process_no = null, duplicated = null, lot_no = null, reference_no = null, sub_component = '', assembly_process = '') {

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
                assembly_process: assembly_process ?? null
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


          const excludedSections = ['PAINTING', 'FINISHING'];
          assemblyData.forEach(item => {
            if (!item.time_out || !item.time_in || !item.person_incharge || !item.created_at) return;
            const section = (item.assembly_section || 'ASSEMBLY').toUpperCase();
            if (excludedSections.includes(section)) return; // ⛔ skip these sections

            const day = extractDateOnly(item.time_out || item.created_at);
            const doneQty = parseInt(item.done_quantity) || 0;
            const totalQty = parseInt(item.total_quantity) || 0;
            const mat = item.material_no || '';
            const desc = item.material_description || '';

            // ➤ Add entry using done_quantity
            addEntry(
              item.id, item.itemID, item.material_no, item.material_description,
              item.person_incharge, day, item.reference_no, item.time_in, item.time_out,
              doneQty, mat, desc, section, 'assembly', item.process_no, item.duplicated,
              item.lot_no, item.reference_no, item.sub_component, item.assembly_process
            );

            // ➤ Construct key
            const key = `${item.person_incharge}_${day}_${section}_${desc}_${item.process_no}_${item.id}`;

            // ➤ Add total quantity
            if (!mergedData[key].totalQuantity) {
              mergedData[key].totalQuantity = 0;
            }
            mergedData[key].totalQuantity += totalQty;

            // ➤ Add cycle_time
            const cycleTime = parseFloat(item.cycle_time);
            if (!isNaN(cycleTime)) {
              mergedData[key].cycle_time = cycleTime;
            }
          });

          // ➤ Add rework records

          stampingData.forEach(item => {
            const section = (item.section || '').toUpperCase();

            if (!item.time_in || !item.time_out || !item.person_incharge || !item.created_at) return;

            const day = extractDateOnly(item.time_out || item.created_at);
            const qty = parseInt(item.quantity) || 0;
            const mat = item.material_no || '';
            const desc = item.components_name || '';

            const cycleTime = parseFloat(item.cycle_time);
            const processNo = item.process_no ?? null;

            addEntry(
              item.id, item.itemID, item.material_no, item.components_name, item.person_incharge,
              day, item.reference_no, item.time_in, item.time_out, qty,
              mat, desc, section, 'stamping', processNo, item.duplicated ?? null,
              item.lot_no, item.reference_no, item.sub_component ?? null, item.stage_name ?? null
            );

            const key = `${item.person_incharge}_${day}_${section}_${item.components_name}_${processNo}_${item.id}`;
            if (!isNaN(cycleTime)) {
              mergedData[key].cycle_time = cycleTime;
            }
          });




          const fromDateInput = document.querySelector('#from-date');
          const toDateInput = document.querySelector('#to-date');

          mergedDataArray = Object.values(mergedData);


          const userLocationNormalized = Array.isArray(userProductionLocation) ?
            userProductionLocation.map(loc =>
              String(loc || '').replace(/\s|-/g, '').toLowerCase()
            ) : [String(userProductionLocation || '').replace(/\s|-/g, '').toLowerCase()];

          const userIsAdmin = userRole === 'administrator';

          filteredData = mergedDataArray.filter(entry => {
            if (userIsAdmin) return true;

            const sectionKeyNormalized = (entry.section || '')
              .replace(/\s|-/g, '')
              .toLowerCase();

            return userLocationNormalized.includes(sectionKeyNormalized);
          });


          paginator = createPaginator({
            data: filteredData,
            rowsPerPage: 10,
            renderPageCallback: (page) => renderPageCallback(page),
            paginationContainerId: 'pagination'
          });

          paginator.render();

          document.getElementById('data-body').addEventListener('click', function(event) {
            const groupRow = event.target.closest('.group-header');
            if (!groupRow) return;

            const batch = groupRow.getAttribute('data-batch');
            const safeBatch = batch.replace(/\s+/g, '_'); // Match the rendered class
            const rows = document.querySelectorAll(`.batch-group-${safeBatch}`);

            if (rows.length === 0) return;

            const isVisible = rows[0].style.display !== 'none';

            rows.forEach(row => {
              row.style.display = isVisible ? 'none' : '';
            });

            const componentName = groupRow.getAttribute('data-component');
            groupRow.querySelector('td').innerHTML =
              `${isVisible ? '▶️' : '🔽'} Section: ${batch} `;
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

          // Hook up date filter listeners
          [fromDateInput, toDateInput].forEach(input =>
            input.addEventListener('change', () => {
              const dateFiltered = applyDateRangeFilter(filteredData); // filteredData = result from text search
              paginator.setData(dateFiltered);
            })
          );
          const searchableFields = [
            'materialNo',
            'materialDescription',
            'componentName',
            'sub_component',
            'assembly_process',
            'section',
            'person',
            'date',
            'lot'
          ];

          // Hook up search filter
          setupSearchFilter({
            filterInputSelector: '#filter-input',
            data: mergedDataArray,
            searchableFields, // use the array defined above
            onFilter: (filtered, query) => {
              currentFilterQuery = query; // store for highlightText
              const dateFiltered = applyDateRangeFilter(filtered);
              paginator.setData(dateFiltered);
            },
            customColumnHandler: {
              person: item => item.person,
              material_no: item => (item.materialNo ?? '').toString(),
              material_description: item => item.materialDescription ?? '',
              model: item => item.model,
              lot: item => item.lot,
              date: item => item.date,
              section: item => item.section
            }
          });


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

    /* Tablet only: horizontal scroll */
    /* Tablet only adjustments */
    @media (max-width: 991.98px) {
      .table-wrapper {
        overflow-x: auto;
        width: 100%;
      }

      .custom-hover {
        min-width: 1200px;
        /* ensures horizontal scroll */
      }

      .custom-hover th,
      .custom-hover td {
        white-space: nowrap;
        font-size: 0.85rem;
        padding: 6px 8px;
      }

      .custom-hover th:nth-child(1),
      .custom-hover td:nth-child(1) {
        width: 50px !important;
        /* your desired width */
        min-width: 50px !important;
        padding: 5px;
      }

      .custom-hover th:nth-child(2),
      .custom-hover td:nth-child(2) {
        width: 80px !important;
        /* your desired width */
        min-width: 80px !important;
        padding: 5px;
      }

      .custom-hover th:nth-child(3),
      .custom-hover td:nth-child(3) {
        width: 200px !important;
        /* your desired width */
        min-width: 200px !important;
        padding: 5px;
      }

      .custom-hover th:nth-child(4),
      .custom-hover td:nth-child(4) {
        width: 130px !important;
        /* your desired width */
        min-width: 130px !important;
        padding: 5px;
      }

      .custom-hover th:nth-child(5),
      .custom-hover td:nth-child(5) {
        width: 130px !important;
        /* your desired width */
        min-width: 130px !important;
        padding: 5px;
      }

      .custom-hover th:nth-child(6),
      .custom-hover td:nth-child(6) {
        width: 180px !important;
        /* your desired width */
        min-width: 180px !important;
        padding: 5px;
      }

      .custom-hover th:nth-child(6),
      .custom-hover td:nth-child(6) {
        width: 100px !important;
        /* your desired width */
        min-width: 100px !important;
        padding: 5px;
      }

      .custom-hover th:nth-child(7),
      .custom-hover td:nth-child(7) {
        width: 125px !important;
        /* your desired width */
        min-width: 125px !important;
        padding: 5px;
      }

      .custom-hover th:nth-child(8),
      .custom-hover td:nth-child(8) {
        width: 125px !important;
        /* your desired width */
        min-width: 125px !important;
        padding: 5px;
      }

      .custom-hover th:nth-child(9),
      .custom-hover td:nth-child(9) {
        width: 125px !important;
        /* your desired width */
        min-width: 125px !important;
        padding: 5px;
      }

      .custom-hover th:nth-child(10),
      .custom-hover td:nth-child(10) {
        width: 125px !important;
        /* your desired width */
        min-width: 125px !important;
        padding: 5px;
      }

      .custom-hover th:nth-child(11),
      .custom-hover td:nth-child(11) {
        width: 125px !important;
        /* your desired width */
        min-width: 125px !important;
        padding: 5px;
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