<?php

if ($role === 'line leader' && in_array('assembly', $section, true)) {
  echo '
     <!-- Assembly Accounts -->
<li class="nav-item">
  <a class="nav-link" href="?page_active=assembly_accounts" data-page="assembly_accounts">
    <i class="link-icon" data-feather="user"></i>
    <span class="link-title">Assembly Accounts</span>
  </a>
</li>

<!-- Assembly SKU Group -->
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#assemblySkuMenu" role="button" aria-expanded="false" aria-controls="assemblySkuMenu">
    <i class="link-icon" data-feather="package"></i>
    <span class="link-title">Assembly SKU</span>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>
  <div class="collapse" id="assemblySkuMenu">
    <ul class="nav sub-menu" style="padding-left: 20px;">
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

<!-- Assembly Components Group -->
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#assemblyComponentsMenu" role="button" aria-expanded="false" aria-controls="assemblyComponentsMenu">
    <i class="link-icon" data-feather="cpu"></i>
    <span class="link-title">Assembly Components</span>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>
  <div class="collapse" id="assemblyComponentsMenu">
    <ul class="nav sub-menu" style="padding-left: 20px;">
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

        ';
}
if ($role === 'line leader' && in_array('stamping', $section, true)) {
  echo '
   <!-- Stamping Accounts -->
<li class="nav-item">
  <a class="nav-link" href="?page_active=stamping_accounts" data-page="stamping_accounts">
    <i class="link-icon" data-feather="user"></i>
    <span class="link-title">Stamping Accounts</span>
  </a>
</li>

<!-- Stamping PI Group -->
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#stampingPIMenu" role="button" aria-expanded="false" aria-controls="stampingPIMenu">
    <i class="link-icon" data-feather="file-text"></i>
    <span class="link-title">Stamping PI</span>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>
  <div class="collapse" id="stampingPIMenu">
    <ul class="nav sub-menu" style="padding-left: 20px;">
      <li class="nav-item">
        <a href="?page_active=stamping_assign_pi_kbn" class="nav-link" data-page="stamping_assign_pi_kbn">
          PI (Assign)
        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=stamping_pi_kbn" class="nav-link" data-page="stamping_pi_kbn">
          PI KBN
        </a>
      </li>
    </ul>
  </div>
</li>

<!-- Stamping Components Inventory -->
<li class="nav-item">
  <a class="nav-link" href="?page_active=components_inventory" data-page="components_inventory">
    <i class="link-icon" data-feather="package"></i>
    <span class="link-title">Components Inventory</span>
  </a>
</li>

        ';
}
if ($role === 'line leader' && in_array('finishing', $section, true)) {
  echo '
     <!-- Finishing Accounts -->
<li class="nav-item">
  <a class="nav-link" href="?page_active=finishing_accounts" data-page="finishing_accounts">
    <i class="link-icon" data-feather="user"></i>
    <span class="link-title">Finishing Accounts</span>
  </a>
</li>

<!-- Finishing Assembly Group -->
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#finishingAssemblyMenu" role="button" aria-expanded="false" aria-controls="finishingAssemblyMenu">
    <i class="link-icon" data-feather="tool"></i>
    <span class="link-title">Finishing SKU</span>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>
  <div class="collapse" id="finishingAssemblyMenu">
    <ul class="nav sub-menu" style="padding-left: 20px;">
      <li class="nav-item">
        <a href="?page_active=finishing_assign_pi_sku" class="nav-link" data-page="finishing_assign_pi_sku">
          PI (Assign) (<span id="finishing-pi-kbn-sku-badge" style="color:red;">0</span>)
        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=finishing_pi_kbn_sku" class="nav-link" data-page="finishing_pi_kbn_sku">
          PI KBN (<span id="finishing-pi-kbn-sku-badge" style="color:red;">0</span>)
        </a>
      </li>
    </ul>
  </div>
</li>

<!-- Finishing Stamping Group -->
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#finishingStampingMenu" role="button" aria-expanded="false" aria-controls="finishingStampingMenu">
    <i class="link-icon" data-feather="layers"></i>
    <span class="link-title">Finishing Component</span>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>
  <div class="collapse" id="finishingStampingMenu">
    <ul class="nav sub-menu" style="padding-left: 20px;">
      <li class="nav-item">
        <a href="?page_active=finishing_assign_pi_component" class="nav-link" data-page="finishing_assign_pi_component">
          PI (Assign) (<span id="finishing-pi-kbn-components-badge" style="color:red;">0</span>)
        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=finishing_pi_kbn_component" class="nav-link" data-page="finishing_pi_kbn_component">
          PI KBN (<span id="finishing-pi-kbn-components-badge" style="color:red;">0</span>)
        </a>
      </li>
    </ul>
  </div>
</li>

<!-- Finishing Rework Group -->
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#finishingReworkMenu" role="button" aria-expanded="false" aria-controls="finishingReworkMenu">
    <i class="link-icon" data-feather="refresh-ccw"></i>
    <span class="link-title">Finishing Rework</span>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>
  <div class="collapse" id="finishingReworkMenu">
    <ul class="nav sub-menu" style="padding-left: 20px;">
      <li class="nav-item">
        <a href="?page_active=finishing_assign_rework" class="nav-link" data-page="finishing_assign_rework">
          PI (Assign) (<span id="finishing-pi-kbn-components-badge" style="color:red;">0</span>)
        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=finishing_for_rework" class="nav-link" data-page="finishing_for_rework">
          For Rework (<span id="finishing-for-rework-badge" style="color:red;">0</span>)
        </a>
      </li>
    </ul>
  </div>
</li>

        ';
}
if ($role === 'line leader' && in_array('painting', $section, true)) {
  echo '
      <!-- Painting Accounts -->
<li class="nav-item">
  <a class="nav-link" href="?page_active=painting_accounts" data-page="painting_accounts">
    <i class="link-icon" data-feather="user"></i>
    <span class="link-title">Painting Accounts</span>
  </a>
</li>

<!-- Painting PI Group -->
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#paintingPIMenu" role="button" aria-expanded="false" aria-controls="paintingPIMenu">
    <i class="link-icon" data-feather="droplet"></i>
    <span class="link-title">Painting PI</span>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>
  <div class="collapse" id="paintingPIMenu">
    <ul class="nav sub-menu" style="padding-left: 20px;">
      <li class="nav-item">
        <a href="?page_active=painting_assign_pi_kbn" class="nav-link" data-page="painting_assign_pi_kbn">
          PI (Assign)
        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=pi_kbn_painting" class="nav-link" data-page="pi_kbn_painting">
          PI KBN
        </a>
      </li>
    </ul>
  </div>
</li>

        ';
}
