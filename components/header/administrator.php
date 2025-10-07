<?php
if ($role === 'administrator') {

  echo '

    <!---   ACCOUNT MANAGER  --->

    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#admin" role="button" aria-expanded="false" aria-controls="tables">
        <i class="link-icon" data-feather="calendar"></i>
        <span class="link-title">Accounts</span>
        <i class="link-arrow" data-feather="chevron-down"></i>
      </a>
      <div class="collapse" id="admin">
        <ul class="nav sub-menu">
          <li class="nav-item">
            <a href="?page_active=accounts" class="nav-link" data-page="accounts">Account Management</a>
          </li>
        </ul>
      </div>
    </li> 

    <!---   PLANNER  --->

    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#planner" role="button" aria-expanded="false" aria-controls="tables">
        <i class="link-icon" data-feather="calendar"></i>
        <span class="link-title">Planner</span>
        <i class="link-arrow" data-feather="chevron-down"></i>
      </a>
      <div class="collapse" id="planner">
        <ul class="nav sub-menu">
          <li class="nav-item">
            <a href="?page_active=mef_form" class="nav-link" data-page="mef_form">Form</a>
          </li>
          <li class="nav-item">
            <a href="?page_active=customer_form" class="nav-link" data-page="customer_form">Customer Form</a>
          </li>
          <li class="nav-item">
            <a href="?page_active=form_history" class="nav-link" data-page="form_history">Form History</a>
          </li>
        </ul>
      </div>
    </li> 

    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#delivery" role="button" aria-expanded="false" aria-controls="tables">
        <i class="link-icon" data-feather="calendar"></i>
        <span class="link-title">Delivery (<span id="delivery-badge" style="color: red;" style="color: red;" >0</span>)</span>
        <i class="link-arrow" data-feather="chevron-down"></i>
      </a>
      <div class="collapse" id="delivery">
        <ul class="nav sub-menu">
          <li class="nav-item">
            <a href="?page_active=for_delivery" class="nav-link" data-page="for_delivery">
              For Delivery            </a>
          </li>
           <li class="nav-item">
            <a href="?page_active=delivered_history" class="nav-link" data-page="delivered_history">
             Delivered History
            </a>
          </li>
        </ul>
      </div>
    </li>

    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#wh" role="button" aria-expanded="false" aria-controls="tables">
        <i class="link-icon" data-feather="layout"></i>
        <span class="link-title">
          FG Warehouse        </span>
        <i class="link-arrow" data-feather="chevron-down"></i>
      </a>
      <div class="collapse" id="wh">
        <ul class="nav sub-menu">
      
          <li class="nav-item">
            <a href="?page_active=for_pulling" class="nav-link" data-page="for_pulling">
              For Pulling            </a>
          </li>
          <li class="nav-item">
            <a href="?page_active=pulling_history" class="nav-link" data-page="pulling_history">Pulled Out History</a>
          </li>
              <li class="nav-item">
            <a href="?page_active=materials_inventory" class="nav-link" data-page="materials_inventory">Materials Inventory</a>
          </li>
        </ul>
      </div>
    </li>
    

    <!---   QA/QC  --->
    <li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#qc" role="button" aria-expanded="false" aria-controls="qc">
    <i class="link-icon" data-feather="layout"></i>
    <span class="link-title">
      QA/QC    </span>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>
  <div class="collapse" id="qc">
    <ul class="nav sub-menu">
      <li class="nav-item">
        <a href="?page_active=qc_accounts" class="nav-link" data-page="qc_accounts">
          Accounts
        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=qc_pi_kbn" class="nav-link" data-page="qc_pi_kbn">
          PI KBN        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=qc_ncp" class="nav-link" data-page="qc_ncp">
          NCP        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=qc_direct_ok" class="nav-link" data-page="qc_direct_ok">
          Direct OK (Individual)
        </a>
      </li>
      <li class="nav-item">
        <a href="?page_active=qc_direct_ok_sectional" class="nav-link" data-page="qc_direct_ok_sectional">
          Direct OK (Sectional)
        </a>
      </li>
    </ul>
  </div>
</li>
    
    
    <!---   ASSEMBLY  --->
    <li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#assemblyMenu" role="button" aria-expanded="false" aria-controls="assemblyMenu">
    <i class="link-icon" data-feather="layers"></i>
    <span class="link-title">Assembly</span>
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>

  <div class="collapse" id="assemblyMenu">
    <ul class="nav sub-menu">

      <!-- General -->
      <li class="nav-item">
        <a href="?page_active=assembly_accounts" class="nav-link" data-page="assembly_accounts">
          Accounts
        </a>
      </li>

      <!-- PI Assign Group -->
      <li class="nav-item">
        <a class="nav-link" data-toggle="collapse" href="#assemblyAssignMenu" role="button" aria-expanded="false" aria-controls="assemblyAssignMenu">
          SKU          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse" id="assemblyAssignMenu">
          <ul class="nav sub-menu" style="padding-left: 20px;">
            <li class="nav-item">
              <a href="?page_active=assembly_assign_pi_sku" class="nav-link" data-page="assembly_assign_pi_sku">
                PI (Assign)              </a>
            </li>
            <li class="nav-item">
              <a href="?page_active=assembly_pi_kbn_sku" class="nav-link" data-page="assembly_pi_kbn_sku">
                PI KBN              </a>
            </li>
          
          </ul>
        </div>
      </li>

      <!-- PI KBN Group -->
      <li class="nav-item">
        <a class="nav-link" data-toggle="collapse" href="#assemblyKbnMenu" role="button" aria-expanded="false" aria-controls="assemblyKbnMenu">
          Components          <i class="link-arrow" data-feather="chevron-down"></i>
        </a>
        <div class="collapse" id="assemblyKbnMenu">
          <ul class="nav sub-menu" style="padding-left: 20px;">
         <li class="nav-item">
              <a href="?page_active=assembly_assign_pi_components" class="nav-link" data-page="assembly_assign_pi_components">
                PI (Assign)              </a>
            </li>
            <li class="nav-item">
              <a href="?page_active=assembly_pi_kbn_component" class="nav-link" data-page="assembly_pi_kbn_component">
                PI KBN              </a>
            </li>
          </ul>
        </div>
      </li>

      <!-- General -->
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


    
    <!---   STAMPING  --->
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
  </li>
    
    <!---   RM WAREHOUSE  --->    
      <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#rmw" role="button" aria-expanded="false" aria-controls="tables">
        <i class="link-icon" data-feather="layout"></i>
        <span class="link-title">
          RM Warehouse        </span>
        <i class="link-arrow" data-feather="chevron-down"></i>
      </a>
      <div class="collapse" id="rmw">
        <ul class="nav sub-menu">
          <li class="nav-item">
            <a href="?page_active=issue_rm" class="nav-link" data-page="for_issue">
              For Issue            </a>
          </li>
          <li class="nav-item">
            <a href="?page_active=issued_history" class="nav-link" data-page="issued_history">Issued History</a>
          </li>
        </ul>
      </div>
    </li>';
}


?>

<li class="nav-item nav-category">Sub-Section</li>
<?php
if ($role === 'administrator') {
  echo '
    <!---   FINISHING  --->
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
          Assembly <i class="link-arrow" data-feather="chevron-down"></i>
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
          Stamping <i class="link-arrow" data-feather="chevron-down"></i>
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
</li>

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
    </li>


<li class="nav-item">
  <a class="nav-link d-flex justify-content-between align-items-center" 
     data-toggle="collapse" 
     href="#announcementMenu" 
     role="button" 
     aria-expanded="false" 
     aria-controls="announcementMenu">
    
    <!-- Icon -->
    <i class="link-icon" data-feather="layout"></i>
    
    <!-- Title -->
    <span class="link-title">Announcement</span>
    
    <!-- Chevron -->
    <i class="link-arrow" data-feather="chevron-down"></i>
  </a>

  <div class="collapse" id="announcementMenu">
    <ul class="nav sub-menu">
      <li class="nav-item">
        <a href="?page_active=announcement_list" class="nav-link" data-page="announcement_list">
          Manage Announcements
        </a>
      </li>
    </ul>
  </div>
</li>



    ';
}
?>