<?php include './components/reusable/tablepagination.php'; ?>
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
      <li class="breadcrumb-item active" aria-current="page">Warehouse Section</li>
    </ol>
  </nav>


  <div class="row">
    <div class="col-md-12 grid-margin stretch-card">
      <div class="card">
        <div class="card-body">

          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="card-title">Material Components Inventory</h6>
            <small id="last-updated" class="text-muted" style="font-size:13px;"></small>
          </div>
          <div class="row mb-3 col-md-3">


            <input
              type="text"
              id="filter-input"
              class="form-control"
              placeholder="Type to filter..." />

          </div>
          <table class="custom-hover table" style="table-layout: fixed; width: 100%;">
            <thead>
              <tr>
                <th style="width: 10%; text-align: center;">Material No <span class="sort-icon"></span></th>
                <th style="width: 15%; text-align: center;">Material Description <span class="sort-icon"></span></th>

                <th style="width: 7%; text-align: center;">Quantity <span class="sort-icon"></span></th>
              </tr>
            </thead>
            <tbody id="data-body" style="word-wrap: break-word; white-space: normal;"></tbody>
          </table>
          <div id="pagination" class="mt-3 d-flex justify-content-center"></div>


        </div>
      </div>
    </div>
  </div>
</div>

<script>
  let fullData = [];
  let paginator;
  let model;

  function getData(model) {
    fetch('api/fg/getAllComponents?model=' + encodeURIComponent(model))
      .then(response => response.json())
      .then(data => {
        fullData = data.data || data || [];

        // Remove duplicates for CLIP 25 and CLIP 60
        const seenClips = new Set();
        fullData = fullData.filter(item => {
          if (item.material_description === 'CLIP 25' || item.material_description === 'CLIP 60') {
            if (seenClips.has(item.material_description)) return false;
            seenClips.add(item.material_description);
          }
          return true;
        });

        paginator = createPaginator({
          data: fullData,
          rowsPerPage: 10,
          paginationContainerId: 'pagination',
          renderPageCallback: renderTable
        });

        paginator.render();

        // ✅ Search filter only on visible table columns
        const searchableFields = ['material_no', 'material_description', 'model', 'quantity'];

        setupSearchFilter({
          filterColumnSelector: '#filter-column',
          filterInputSelector: '#filter-input',
          data: fullData,
          searchableFields,
          customValueResolver: (item, column) => {
            if (column === 'quantity') return (parseInt(item.quantity, 10) || 0).toString();
            return item[column] ?? '';
          },
          onFilter: (filtered, query) => {
            currentFilterQuery = query || '';
            paginator.setData(filtered);
          }
        });

      })
      .catch(error => console.error('Error loading data:', error));
  }

  function renderTable(data) {
    const tbody = document.getElementById('data-body');
    tbody.innerHTML = '';

    data.forEach(item => {
      const quantity = parseInt(item.quantity, 10) || 0;
      const row = document.createElement('tr');

      row.innerHTML = `
            <td class="text-center">${highlightText(item.material_no, currentFilterQuery)}</td>
            <td class="text-center text-truncate" style="max-width: 200px;">
                ${highlightText(item.material_description, currentFilterQuery)}
            </td>
         
            <td class="text-center">${highlightText(quantity.toString(), currentFilterQuery)}</td>
        `;

      tbody.appendChild(row);
    });

    const now = new Date();
    document.getElementById('last-updated').textContent = `Last updated: ${now.toLocaleString()}`;
  }




  enableTableSorting(".table");
</script>