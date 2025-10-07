<?php include './components/reusable/tablesorting.php'; ?>
<?php include './components/reusable/searchfilter.php'; ?>
<script src="assets/js/bootstrap.bundle.min.js"></script>
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
      <li class="breadcrumb-item" aria-current="page">Warehouse Section</li>
    </ol>
  </nav>

  <div class="row">
    <div class="col-md-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">

          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="card-title">Fg ready for pull out</h6>
            <small id="last-updated" class="text-muted" style="font-size:13px;"></small>

          </div>
          <div class="row mb-3 col-md-3">


            <input type="text" id="search-input" class="form-control" placeholder="Type to filter..." />

          </div>
          <div class="table-responsive">
            <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
              <thead>
                <tr>
                  <th style="text-align: center;">Material No <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Material Description <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Model <span class="sort-icon"></span></th>
                  <th style="text-align: center;">FG <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Total Quantity <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Shift <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Lot No <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Date Needed <span class="sort-icon"></span></th>
                  <th style="text-align: center;">Pull out <span class="sort-icon"></span></th>
                </tr>
              </thead>
              <tbody id="data-body" style="word-wrap: break-word; white-space: normal;">
                <!-- Table rows here -->
              </tbody>
            </table>
          </div>





        </div>
      </div>
    </div>
  </div>
  <!-- Inspection Modal -->
  <div class="modal fade" id="inspectionModal" tabindex="-1" aria-labelledby="inspectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="inspectionModalLabel">Inspection Input</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="inspectionForm">
            <div class="mb-3">
              <label for="goodQty" class="form-label">Good</label>
              <input type="number" class="form-control" id="goodQty" required>
            </div>
            <div class="mb-3">
              <label for="notGoodQty" class="form-label">Not Good</label>
              <input type="number" class="form-control" id="notGoodQty" required>
            </div>
            <input type="hidden" id="totalQtyHidden">
            <div id="errorMsg" class="text-danger"></div>
            <input type="hidden" id="recordIdHidden">

          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" onclick="submitInspection()">Submit</button>
        </div>
      </div>
    </div>
  </div>
  <!-- SweetAlert2 CDN -->
  <script src="assets/js/sweetalert2@11.js"></script>
  <script>
    let allData = [];
    let model = null;


    function getData(model) {
      fetch('api/fg/getReadyforPullOut?model=' + encodeURIComponent(model || '') + '&_=' + new Date().getTime())
        .then(res => res.json())
        .then(data => {
          allData = data; // cache
          filteredData = [...allData];

          renderTable(filteredData);

          // ✅ Search filter only on visible table columns
          const searchableFields = [
            'material_no',
            'material_description',
            'model',
            'quantity',
            'total_quantity',
            'shift',
            'lot_no',
            'date_needed',
            'status'
          ];

          setupSearchFilter({
            filterInputSelector: '#search-input',
            data: allData,
            searchableFields,
            customValueResolver: (item, column) => {
              if (column === 'quantity' || column === 'total_quantity') return item[column]?.toString() ?? '';
              if (column === 'shift' || column === 'lot_no' || column === 'date_needed') return item[column] ?? '<i>NULL</i>';
              return item[column] ?? '';
            },
            onFilter: (filtered, query) => {
              currentFilterQuery = query || '';
              filteredData = filtered;
              renderTable(filteredData);
            }
          });
        })
        .catch(error => {
          console.error('Error loading data:', error);
        });
    }

    function renderTable(data) {
      const tbody = document.getElementById('data-body');
      tbody.innerHTML = '';

      data.forEach(item => {
        if ((item.status || '').toLowerCase() === 'done') return;

        const row = document.createElement('tr');

        row.innerHTML = `
            <td style="text-align: center;">${highlightText(item.material_no, currentFilterQuery)}</td>
            <td class="text-center text-truncate" style="max-width: 200px;white-space: normal; word-wrap: break-word;">
                ${highlightText(item.material_description, currentFilterQuery)}
            </td>
            <td style="text-align: center;">${highlightText(item.model, currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.quantity?.toString(), currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.total_quantity?.toString(), currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.shift ?? '<i>NULL</i>', currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.lot_no ?? '<i>NULL</i>', currentFilterQuery)}</td>
            <td style="text-align: center;">${highlightText(item.date_needed ?? '<i>NULL</i>', currentFilterQuery)}</td>
            <td style="text-align: center;">
                <button
                    class="btn btn-sm ${((item.status || '').toUpperCase() === 'DONE') ? 'btn-primary' : 'btn-warning'} pull-btn"
                    data-id="${item.id}"
                    data-quantity="${item.quantity || 0}"
                    data-total_quantity="${item.total_quantity || 0}"
                    data-material_no="${item.material_no || ''}"
                    data-description="${item.material_description || ''}"
                    data-reference_no="${item.reference_no || ''}"
                    data-model="${item.model || ''}"
                    data-part_type="${item.part_type || ''}"
                >
                    ${highlightText((item.status || '').toUpperCase(), currentFilterQuery)}
                </button>
            </td>
        `;
        tbody.appendChild(row);
      });

      const now = new Date();
      document.getElementById('last-updated').textContent = `Last updated: ${now.toLocaleString()}`;
      // Attach event listeners only to non-DONE buttons
      document.querySelectorAll('.pull-btn').forEach(button => {
        const quantity = parseInt(button.getAttribute('data-quantity'));
        const total_quantity = parseInt(button.getAttribute('data-total_quantity'));
        const id = button.getAttribute('data-id');
        const material_no = button.getAttribute('data-material_no');
        const material_description = button.getAttribute('data-description');
        const reference_no = button.getAttribute('data-reference_no');
        const model = button.getAttribute('data-model');
        const part_type = button.getAttribute('data-part_type');
        console.log('Button data:', {
          id,
          quantity,
          total_quantity,
          material_no,
          material_description,
          reference_no,
          model,
          part_type
        });
        if (!button.classList.contains('btn-primary') && quantity === total_quantity) {
          button.addEventListener('click', () => {
            Swal.fire({
              title: 'Confirm Pull Out',
              html: `
          <p><strong>Material No:</strong> ${material_no}</p>
          <p><strong>Component:</strong> ${material_description}</p>
          <p>Do you want to mark this item as pulled out?</p>
        `,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Yes, pull out!',
              cancelButtonText: 'Cancel'
            }).then((result) => {
              if (result.isConfirmed) {
                fetch('api/fg/PullOut', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                      id,
                      material_no,
                      material_description,
                      total_quantity,
                      reference_no,
                      model,
                      part_type
                    })
                  })
                  .then(res => res.json())
                  .then(response => {
                    if (response.success) {
                      Swal.fire('Pulled out!', response.message || 'Item marked as pulled out.', 'success');
                      getData(); // reload full data
                    } else {
                      Swal.fire('Error', response.message || 'Failed to pull out item.', 'error');
                    }
                  })
                  .catch(err => {
                    console.error('Pull out error:', err);
                    Swal.fire('Error', 'An unexpected error occurred.', 'error');
                  });
              }
            });
          });
        }
      });


    }


    enableTableSorting(".table");
  </script>
  <style>
    /* Hover effect for all rows */
    .custom-hover tbody tr:hover td,
    .custom-hover tbody tr:hover th {
      background-color: #dde0e2 !important;
    }

    /* Tablet only: horizontal scroll and sticky columns */
    @media (max-width: 991.98px) {
      .table-responsive {
        overflow-x: auto;
        width: 100%;
      }

      .custom-hover {
        min-width: 900px;
        /* adjust according to total columns */
        table-layout: fixed;
      }

      .custom-hover th,
      .custom-hover td {
        white-space: nowrap;
        font-size: 0.85rem;
        padding: 6px 8px;
      }

      /* Sticky first column (Material No) */
      .custom-hover th:nth-child(1),
      .custom-hover td:nth-child(1) {
        position: sticky;
        left: 0;
        background: #f9f9f9;
        z-index: 10;
        width: 100px;
      }

      /* Sticky second column (Material Description) */
      .custom-hover th:nth-child(2),
      .custom-hover td:nth-child(2) {
        position: sticky;
        left: 100px;
        /* width of first column */
        background: #f9f9f9;
        z-index: 10;
        width: 200px;
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