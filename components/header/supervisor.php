<?php
if ($role === 'supervisor' && in_array('qc', $section, true)) {
  echo '
    <!-- QC Section (Collapsible Menu) -->
<!-- QC Accounts -->
<li class="nav-item">
  <a class="nav-link" href="?page_active=qc_accounts" data-page="qc_accounts">
    <i class="link-icon" data-feather="user-check"></i>
    <span class="link-title">QC Accounts</span>
  </a>
</li>


<li class="nav-item">
  <a class="nav-link" href="?page_active=qc_pi_kbn" data-page="qc_pi_kbn">
   <i class="link-icon" data-feather="file-text"></i>
    <span class="link-title">QC PI KBN</span>
  </a>
</li>
<!-- QC NCP -->
<li class="nav-item">
  <a class="nav-link" href="?page_active=qc_ncp" data-page="qc_ncp">
    <i class="link-icon" data-feather="alert-triangle"></i>
    <span class="link-title">QC NCP</span>
  </a>
</li>

<!-- QC Direct OK Group -->
<li class="nav-item">
  <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#qcDirectOKMenu" role="button" aria-expanded="false" aria-controls="qcDirectOKMenu">
    <div>
      <i class="link-icon" data-feather="check-circle"></i>
      <span class="link-title">QC Direct OK</span>
    </div>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>
  <div class="collapse" id="qcDirectOKMenu">
    <ul class="nav flex-column sub-menu" style="padding-left: 20px;">
      <li class="nav-item">
        <a href="?page_active=qc_direct_ok" class="nav-link" data-page="qc_direct_ok">
          Individual
        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=qc_direct_ok_sectional" class="nav-link" data-page="qc_direct_ok_sectional">
          Sectional
        </a>
      </li>
    </ul>
  </div>
</li>

';
}


if ($role === 'supervisor' && in_array('assembly', $section, true)) {
  echo '
  <li class="nav-item">
  <a class="nav-link d-flex justify-between align-center" data-toggle="collapse" href="#assemblyMenu" role="button" aria-expanded="false" aria-controls="assemblyMenu">
    <i class="link-icon" data-feather="layers"></i>
    <span class="link-title flex-grow">Assembly</span>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>

  <div class="collapse" id="assemblyMenu">
    <ul class="nav sub-menu">

      <!-- Accounts -->
      <li class="nav-item">
        <a href="?page_active=assembly_accounts" class="nav-link" data-page="assembly_accounts">
          Accounts
        </a>
      </li>

      <!-- SKU Group -->
      <li class="nav-item">
        <a class="nav-link d-flex justify-between align-center" data-toggle="collapse" href="#assemblyAssignMenu" role="button" aria-expanded="false" aria-controls="assemblyAssignMenu">
          <span>SKU</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse" id="assemblyAssignMenu">
          <ul class="nav sub-menu ps-4">
            <li class="nav-item">
              <a href="?page_active=assembly_assign_pi_sku" class="nav-link" data-page="assembly_assign_pi_sku">
                PI (Assign)
              </a>
            </li>
            <li class="nav-item">
              <a href="?page_active=assembly_pi_kbn_sku" class="nav-link" data-page="assembly_pi_kbn_sku">
                PI KBN
              </a>
            </li>
          </ul>
        </div>
      </li>

      <!-- Components Group -->
      <li class="nav-item">
        <a class="nav-link d-flex justify-between align-center" data-toggle="collapse" href="#assemblyKbnMenu" role="button" aria-expanded="false" aria-controls="assemblyKbnMenu">
          <span>Components</span>
          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse" id="assemblyKbnMenu">
          <ul class="nav sub-menu ps-4">
            <li class="nav-item">
              <a href="?page_active=assembly_assign_pi_components" class="nav-link" data-page="assembly_assign_pi_components">
                PI (Assign)
              </a>
            </li>
            <li class="nav-item">
              <a href="?page_active=assembly_pi_kbn_component" class="nav-link" data-page="assembly_pi_kbn_component">
                PI KBN
              </a>
            </li>
          </ul>
        </div>
      </li>

      <!-- Efficiency -->
      <li class="nav-item">
        <a href="?page_active=assembly_manpower_efficiency" class="nav-link" data-page="assembly_manpower_efficiency">
          Manpower Efficiency
        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=assembly_sectional_efficiency" class="nav-link" data-page="assembly_sectional_efficiency">
          Sectional Efficiency
        </a>
      </li>

    </ul>
  </div>
</li>

';
}



if ($role === 'supervisor' && in_array('stamping', $section, true)) {
  echo '
    <li class="nav-item">
    <a class="nav-link d-flex justify-content-between align-items-center" data-toggle="collapse" href="#stamping_admin" role="button" aria-expanded="false" aria-controls="stamping">
      <i class="link-icon" data-feather="layout"></i>
      <span class="link-title">
        Stamping      </span>
      <i class="link-arrow" data-feather="chevron-down"></i>
    </a>
    <div class="collapse" id="stamping_admin">
      <ul class="nav sub-menu">
        <li class="nav-item">
          <a href="?page_active=stamping_accounts" class="nav-link" data-page="stamping_accounts">
            Accounts
          </a>
        </li>
             <li class="nav-item">
          <a href="?page_active=stamping_assign_pi_kbn" class="nav-link" data-page="stamping_assign_pi_kbn">
            PI (Assign)          </a>
        </li>
        <li class="nav-item">
          <a href="?page_active=stamping_pi_kbn" class="nav-link" data-page="stamping_pi_kbn">
            PI KBN          </a>
        </li>

        <li class="nav-item">
          <a href="?page_active=components_inventory" class="nav-link" data-page="components_inventory">
            Components Inventory
          </a>
        </li>
        <li class="nav-item">
          <a href="?page_active=stamping_manpower_efficiency" class="nav-link" data-page="stamping_manpower_efficiency">
            Manpower Efficiency
          </a>
        </li>
        <li class="nav-item">
          <a href="?page_active=stamping_sectional_efficiency" class="nav-link" data-page="stamping_sectional_efficiency">
            Sectional Efficiency
          </a>
        </li>
      </ul>
    </div>
  </li>';
}


if ($role === 'supervisor' && in_array('finishing', $section, true)) {
  echo '
   <li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#finishingMenu" role="button" aria-expanded="false" aria-controls="finishingMenu">
    <i class="link-icon" data-feather="layout"></i>
    <span class="link-title">Finishing</span>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>

  <div class="collapse" id="finishingMenu">
    <ul class="nav sub-menu">

      <!-- General -->
      <li class="nav-item">
        <a href="?page_active=finishing_accounts" class="nav-link" data-page="finishing_accounts">
          Accounts
        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=finishing_manpower_efficiency" class="nav-link" data-page="finishing_manpower_efficiency">
          Manpower Efficiency
        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=finishing_sectional_efficiency" class="nav-link" data-page="finishing_sectional_efficiency">
          Sectional Efficiency
        </a>
      </li>

      <!-- SKU Group -->
      <li class="nav-item">
        <a class="nav-link" data-toggle="collapse" href="#finishingSkuMenu" role="button" aria-expanded="false" aria-controls="finishingSkuMenu">
          SKU <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse" id="finishingSkuMenu">
          <ul class="nav sub-menu" style="padding-left: 20px;">
            <li class="nav-item">
              <a href="?page_active=finishing_assign_pi_sku" class="nav-link">PI (Assign) </a>
            </li>
            <li class="nav-item">
              <a href="?page_active=finishing_pi_kbn_sku" class="nav-link">PI KBN </a>
            </li>
          </ul>
        </div>
      </li>

      <!-- Component Group -->
      <li class="nav-item">
        <a class="nav-link" data-toggle="collapse" href="#finishingComponentMenu" role="button" aria-expanded="false" aria-controls="finishingComponentMenu">
          Component <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse" id="finishingComponentMenu">
          <ul class="nav sub-menu" style="padding-left: 20px;">
            <li class="nav-item">
              <a href="?page_active=finishing_assign_pi_component" class="nav-link">PI (Assign))</a>
            </li>
            <li class="nav-item">
              <a href="?page_active=finishing_pi_kbn_component" class="nav-link">PI KBN</a>
            </li>
          </ul>
        </div>
      </li>

      <!-- Rework Group -->
      <li class="nav-item">
        <a class="nav-link" data-toggle="collapse" href="#finishingReworkMenu" role="button" aria-expanded="false" aria-controls="finishingReworkMenu">
          Rework <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse" id="finishingReworkMenu">
          <ul class="nav sub-menu" style="padding-left: 20px;">
            <li class="nav-item">
              <a href="?page_active=finishing_assign_rework" class="nav-link">PI (Assign)</a>
            </li>
            <li class="nav-item">
              <a href="?page_active=finishing_for_rework" class="nav-link">For Rework </a>
            </li>
          </ul>
        </div>
      </li>

    </ul>
  </div>
</li>';
}



if ($role === 'supervisor' && in_array('painting', $section, true)) {
  echo '
 <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#painting" role="button" aria-expanded="false" aria-controls="tables">
        <i class="link-icon" data-feather="layout"></i>
        <span class="link-title">
          Painting        </span>
        <i class="link-arrow" data-feather="chevron-down"></i>
      </a>
      <div class="collapse" id="painting">
        <ul class="nav sub-menu">
             <li class="nav-item">
          <a href="?page_active=painting_accounts" class="nav-link" data-page="painting_accounts">
            Accounts
          </a>
        </li>
           <li class="nav-item">
          <a href="?page_active=painting_assign_pi_kbn" class="nav-link" data-page="painting_assign_pi_kbn">
            PI (Assign)          </a>
        </li>
          <li class="nav-item">
            <a href="?page_active=pi_kbn_painting" class="nav-link" data-page="pi_kbn_painting">
              PI KBN            </a>
          </li>
          <li class="nav-item">
            <a href="?page_active=painting_manpower_efficiency" class="nav-link" data-page="painting_manpower_efficiency">Manpower Efficiency</a>
          </li>
          <li class="nav-item">
            <a href="?page_active=painting_sectional_efficiency" class="nav-link" data-page="painting_sectional_efficiency">Sectional Efficiency</a>
          </li>
        </ul>
      </div>
    </li>';
}
