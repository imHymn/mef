<?php
if ($role === 'account manager') {
  // Account Manager: just the link, no collapse
  echo '
    <li class="nav-item">
      <a class="nav-link" href="?page_active=accounts" data-page="accounts">
        <i class="link-icon" data-feather="users"></i>
        <span class="link-title">Account Management</span>
      </a>
    </li>';
}

if ($role === 'planner') {
  // Planner: just direct links, no collapse
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

if ($role === 'delivery') {
  // Delivery: direct links, no collapse
  echo '
    <li class="nav-item">
      <a class="nav-link" href="?page_active=for_delivery" data-page="for_delivery">
        <i class="link-icon" data-feather="truck"></i>
        <span class="link-title">
          For Delivery (<span id="pulledout-badge" style="color: red;">0</span>)
        </span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page_active=delivered_history" data-page="delivered_history">
        <i class="link-icon" data-feather="archive"></i>
        <span class="link-title">Delivered History</span>
      </a>
    </li>';
}

if ($role === 'fg warehouse') {
  // FG Warehouse: direct links, no collapse
  echo '
    <li class="nav-item">
      <a href="?page_active=for_pulling" class="nav-link" data-page="for_pulling">
        <i class="link-icon" data-feather="clipboard"></i>
        <span class="link-title">
          For Pulling (<span id="forpulling-badge" style="color: red;">0</span>)
        </span>
      </a>
    </li>
    <li class="nav-item">
      <a href="?page_active=pulling_history" class="nav-link" data-page="pulling_history">
        <i class="link-icon" data-feather="clock"></i>
        <span class="link-title">Pulled Out History</span>
      </a>
    </li>
    <li class="nav-item">
      <a href="?page_active=materials_inventory" class="nav-link" data-page="materials_inventory">
        <i class="link-icon" data-feather="layers"></i>
        <span class="link-title">Materials Inventory</span>
      </a>
    </li>';
}

if ($role === 'rm warehouse') {
  // RM Warehouse: direct links, no collapse
  echo '
    <li class="nav-item">
      <a href="?page_active=issue_rm" class="nav-link" data-page="issue_rm">
        <i class="link-icon" data-feather="package"></i>
        <span class="link-title">
          For Issue 
        </span>
      </a>
    </li>
    <li class="nav-item">
      <a href="?page_active=issued_history" class="nav-link" data-page="issued_history">
        <i class="link-icon" data-feather="clock"></i>
        <span class="link-title">Issuance History</span>
      </a>
    </li>';
}
