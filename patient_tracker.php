<?php
/**
 * Patient Tracker (Patient Flow Board)
 *
 * This program displays the information entered in the Calendar program ,
 * allowing the user to change status and view those changed here and in the Calendar
 * Will allow the collection of length of time spent in each status
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 * @author  Terry Hill <terry@lilysystems.com>
 * @author  Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2015-2017 Terry Hill <terry@lillysystems.com>
 * @copyright Copyright (c) 2017 Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2017 Ray Magauran <magauran@medexbank.com>
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once "../globals.php";
require_once "$srcdir/patient.inc";
require_once "$srcdir/options.inc.php";
require_once "$srcdir/patient_tracker.inc.php";
require_once "$srcdir/user.inc";
require_once "$srcdir/MedEx/API.php";

use OpenEMR\Core\Header;
$unit_order_array=array(); //array for current units
// These settings are sticky user preferences linked to a given page.
// mdsupport - user_settings prefix
$uspfx = substr(__FILE__, strlen($webserver_root)) . '.';
$setting_new_window = prevSetting($uspfx, 'setting_new_window', 'setting_new_window', ' ');
// flow board and recall board share bootstrap settings:
$setting_bootstrap_submenu = prevSetting('', 'setting_bootstrap_submenu', 'setting_bootstrap_submenu', ' ');
$setting_selectors = prevSetting($uspfx, 'setting_selectors', 'setting_selectors', 'block');
$form_apptcat = prevSetting($uspfx, 'form_apptcat', 'form_apptcat', '');
$form_apptstatus = prevSetting($uspfx, 'form_apptstatus', 'form_apptstatus', '');
$facility = prevSetting($uspfx, 'form_facility', 'form_facility', '');
$provider = prevSetting($uspfx, 'form_provider', 'form_provider', $_SESSION['authUserID']);
// $provider_cli = prevSetting($uspfx, 'form_provider_cli', 'form_provider_cli', $_SESSION['authUserID']);

if (($_POST['setting_new_window']) ||
    ($_POST['setting_bootstrap_submenu']) ||
    ($_POST['setting_selectors'])) {
    // These are not form elements. We only ever change them via ajax, so exit now.
    exit();
}
if ($_POST['saveCALLback'] == "Save") {
    $sqlINSERT = "INSERT INTO medex_outgoing (msg_pc_eid,msg_pid,campaign_uid,msg_type,msg_reply,msg_extra_text)
                  VALUES
                (?,?,?,'NOTES','CALLED',?)";
    sqlQuery($sqlINSERT, array($_POST['pc_eid'], $_POST['pc_pid'], $_POST['campaign_uid'], $_POST['txtCALLback']));
}

$user_title = acl_get_group_titles($_SESSION['authUser']);               
if(in_array("Physicians", $user_title)) {
	$_SESSION['isPhysicians'] = "true";
 }
//set default start date of flow board to value based on globals
if (!$GLOBALS['ptkr_date_range']) {
    $from_date = date('Y-m-d');
} elseif (!is_null($_REQUEST['form_from_date'])) {
    $from_date = DateToYYYYMMDD($_REQUEST['form_from_date']);
} elseif (($GLOBALS['ptkr_start_date'])=='D0') {
    $from_date = date('Y-m-d');
} elseif (($GLOBALS['ptkr_start_date'])=='B0') {
    if (date(w)==GLOBALS['first_day_week']) {
        //today is the first day of the week
        $from_date = date('Y-m-d');
    } elseif ($GLOBALS['first_day_week']==0) {
        //Sunday
        $from_date = date('Y-m-d', strtotime('previous sunday'));
    } elseif ($GLOBALS['first_day_week']==1) {
        //Monday
        $from_date = date('Y-m-d', strtotime('previous monday'));
    } elseif ($GLOBALS['first_day_week']==6) {
        //Saturday
        $from_date = date('Y-m-d', strtotime('previous saturday'));
    }
} else {
    //shouldnt be able to get here.
    $from_date = date('Y-m-d');
}

//set default end date of flow board to value based on globals
if ($GLOBALS['ptkr_date_range']) {
    if (substr($GLOBALS['ptkr_end_date'], 0, 1) == 'Y') {
        $ptkr_time = substr($GLOBALS['ptkr_end_date'], 1, 1);
        $ptkr_future_time = mktime(0, 0, 0, date('m'), date('d'), date('Y') + $ptkr_time);
    } elseif (substr($GLOBALS['ptkr_end_date'], 0, 1) == 'M') {
        $ptkr_time = substr($GLOBALS['ptkr_end_date'], 1, 1);
        $ptkr_future_time = mktime(0, 0, 0, date('m') + $ptkr_time, date('d'), date('Y'));
    } elseif (substr($GLOBALS['ptkr_end_date'], 0, 1) == 'D') {
        $ptkr_time = substr($GLOBALS['ptkr_end_date'], 1, 1);
        $ptkr_future_time = mktime(0, 0, 0, date('m'), date('d') + $ptkr_time, date('Y'));
    }

    $to_date = date('Y-m-d', $ptkr_future_time);
    $to_date = !is_null($_REQUEST['form_to_date']) ? DateToYYYYMMDD($_REQUEST['form_to_date']) : $to_date;
} else {
    $to_date = date('Y-m-d');
}

$form_patient_name = !is_null(trim($_POST['form_patient_name'])) ? trim($_POST['form_patient_name']) : null;
$form_patient_id = !is_null($_POST['form_patient_id']) ? $_POST['form_patient_id'] : null;

// get all appts for date range and refine view client side.  very fast...
$appointments = array();
$datetime = date("Y-m-d H:i:s");
if(isset( $_REQUEST['form_provider'])){
    $provo =  $_REQUEST['form_provider'];
}
else if(isset($_SESSION['authUserID'])){
    $provo =  $_SESSION['authUserID'];
}
else{
    $provo = '';
}
if(isset($_REQUEST['form_provider_cli'])){ 
    $form_provider_cli =  $_REQUEST['form_provider_cli'];
}
else{
    $form_provider_cli="";
}
if(isset($_REQUEST['form_apptstatus'])){ 
    $pc_appstatus =  $_REQUEST['form_apptstatus'];
}
else{
    $pc_appstatus="";
}

if(isset($_REQUEST['form_apptcat'])){ 
    $pc_visit =  $_REQUEST['form_apptcat'];
}
else{
    $pc_visit="";
}
if(isset($_REQUEST['form_facility'])){ //for all clinics
    $pc_facility=  $_REQUEST['form_facility'];
}
else{
    $pc_facility="";
}
$appointments = fetch_Patient_Tracker_Events($from_date, $to_date,$provo, $form_provider_cli, $pc_appstatus, '', $form_patient_name, $form_patient_id);
//grouping of the count of every status
// echo($provo);
if($provo === '')
$appointments_status_logged_in_provider = getApptStatusForFlowBoard($appointments);
else if($provo === $_SESSION['authUserID'])
$appointments_status_logged_in_provider = getApptStatusForFlowBoardForLoggedInProvider($appointments,$_SESSION['authUserID']);
else
$appointments_status_logged_in_provider = getApptStatus($appointments);

$filter_appointment = array();
$filter_visit  = array();
if($pc_visit!=""){
    foreach($appointments as $new_visit){

        if($new_visit['pc_catid'] == $pc_visit){
        array_push($filter_visit ,$new_visit);
        }
        }
    
    $appointments = $filter_visit;
}

$filter_clinic = array();
if($pc_facility!=""){
        foreach($appointments as $clinic){
        $unit_clinic=unserialize($clinic['pc_additional_info']);
        if($unit_clinic['unit'] == 0)
        {
            $unit_clinic=$unit_clinic['assoc_eid'];
            $unit=sqlstatement("select pc_facility from `openemr_postcalendar_events` where pc_eid='$unit_clinic'");
            $facility_name= sqlFetchArray($unit);
            $facility_name= $facility_name['pc_facility'];
        }
        else
         $facility_name=$clinic['pc_facility'];
           if($pc_facility ==  $facility_name){
            array_push($filter_clinic,$clinic);
            }
        }
    $appointments = $filter_clinic;
}
if($provo!="" && $form_provider_cli!=""){
    foreach($appointments as $new_apt){

         $new_apt['pc_additional_info'] = unserialize($new_apt['pc_additional_info']);
        
        if($new_apt['pc_additional_info'] && $new_apt['pc_additional_info']['assoc_eid']) {
            
            $row_id = $new_apt['pc_additional_info']['assoc_eid'];
            $prov_query = sqlStatement("SELECT pc_aid from openemr_postcalendar_events where pc_eid='$row_id' ");
            $result = array();
            while ($row = sqlFetchArray($prov_query)){
            $result = $row['pc_aid'];
            $new_apt['pc_additional_info'] = serialize($new_apt['pc_additional_info']);

            if($result == $provo){
                array_push($filter_appointment,$new_apt);
            }
            }
        }
    }
    $appointments = $filter_appointment;
}
$appointments = sortAppointments($appointments, 'date', 'time');

$lres = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = ? AND activity=1", array('apptstat'));
while ($lrow = sqlFetchArray($lres)) {
    // if exists, remove the legend character
    if ($lrow['title'][1] == ' ') {
        $splitTitle = explode(' ', $lrow['title']);
        array_shift($splitTitle);
        $title = implode(' ', $splitTitle);
    } else {
        $title = $lrow['title'];
    }
    $statuses_list[$lrow['option_id']] = $title;
}

$provider_array = [];
$query_provider = sqlStatement("SELECT table2.id AS id ,table2.facility AS facname, table2.lname AS lname, table2.fname AS fname,table2.mname AS mname, table2.facility_id AS facility_id FROM openemr_postcalendar_events AS table1 LEFT JOIN users AS table2 ON table1.pc_aid = table2.id WHERE table2.authorized = 1 AND table2.active = 1 AND table2.username > '' AND table1.pc_additional_info NOT LIKE '%\"unit\";i:1%' ORDER BY lname, fname");
while ($row_provider = sqlFetchArray($query_provider)) {
    $provider_array[] = $row_provider['facname'];
}

$chk_prov = array();  // list of providers with appointments
// Scan appointments for additional info
$found_units = array();
foreach ($appointments as $apt) {
    if(in_array($apt['name'], $provider_array)) {
        $chk_prov[$apt['uprovider_id']] = $apt['ulname'] . ', ' . $apt['ufname'] . ' ' . $apt['umname'];
    } else {
        $found_units[$apt['uprovider_id']] = $apt['ufname'] . ' ' . $apt['umname'] . ' ' . $apt['ulname'];
    }


}

if ($GLOBALS['medex_enable'] == '1') {
    $query2 = "SELECT * FROM medex_icons";
    $iconed = sqlStatement($query2);
    while ($icon = sqlFetchArray($iconed)) {
        $icons[$icon['msg_type']][$icon['msg_status']]['html'] = $icon['i_html'];
    }
    $MedEx = new MedExApi\MedEx('MedExBank.com');
    $logged_in = $MedEx->login();
    $sql = "SELECT * FROM medex_prefs LIMIT 1";
    $preferences = sqlStatement($sql);
    $prefs = sqlFetchArray($preferences);
    if ($logged_in) {
        $results = $MedEx->campaign->events($logged_in['token']);
        foreach ($results['events'] as $event) {
            if ($event['M_group'] != 'REMINDER') {
                continue;
            }
            $icon = $icons[$event['M_type']]['SCHEDULED']['html'];
            if ($event['E_timing'] == '1') {
                $action = xl("before");
            }
            if ($event['E_timing'] == '2') {
                $action = xl("before (PM)");
            }
            if ($event['E_timing'] == '3') {
                $action = xl("after");
            }
            if ($event['E_timing'] == '4') {
                $action = xl("after (PM)");
            }
            $days = ($event['E_fire_time'] == '1') ? xl("day") : xl("days");
            $current_events .= $icon . " &nbsp; " . (int)$event['E_fire_time'] . " " . text($days) . " " . text($action) . "<br />";
        }
    } else {
        $current_events = $icons['SMS']['FAILED']['html'] . " " . xlt("Currently off-line");
    }
}

if (!$_REQUEST['flb_table']) {
?>
<html>
<head>
    <title><?php echo xlt('Flow Board'); ?></title>

    <?php Header::setupHeader(['datetime-picker', 'jquery-ui', 'jquery-ui-cupertino', 'opener', 'pure']); ?>

    <script type="text/javascript">
        <?php require_once "$srcdir/restoreSession.php"; ?>
    </script>

    <link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $GLOBALS['web_root']; ?>/library/css/bootstrap_navbar.css?v=<?php echo $v_js_includes; ?>" type="text/css">
    <script type="text/javascript" src="<?php echo $GLOBALS['web_root']; ?>/interface/main/messages/js/reminder_appts.js?v=<?php echo $v_js_includes; ?>"></script>

    <link rel="shortcut icon" href="<?php echo $webroot; ?>/sites/default/favicon.ico" />

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="author" content="OpenEMR: MedExBank">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        label {
            font-weight: 400;
        }

        select {
            width: 170px;
        }

        .btn{
            border: solid black 0.5pt;
            box-shadow: 3px 3px 3px #7b777760;
            color:white;
        }

        .dialogIframe {
            border: none;
        }

        .scheduled {
            background-color: white;
            color: black;
            padding: 5px;
        }

        .divTable {
            display: table;
            font-size: 0.9em;
            background: white;
            box-shadow: 2px 3px 9px #c0c0c0;
            border-radius: 8px;
            padding: 10px;
            margin: 15px auto;
            min-width: 400px;
            overflow: hidden;
        }

        .title {
            font-family: 'Poppins', sans-serif !important;
            font-weight: bold;
            padding: 3px 10px;
            line-height: 1.5em;
            color: black ;
            border-bottom: 1px solid #ccc;
            margin: 0 auto;
            text-align:left;
        }
        .ui-datepicker-year {
            color: #000;
        }
        input[type="text"] {
            text-align:center;
        }
         .ui-widget {
            font-size: 1.0em;
        }
        body_top {
            height:100%;
        }
        a:hover {
            color:black;
            text-decoration:none;
        }
        .facility-filter-wrapper select {
            width: 32%!important;
        }
        
        .visit-filter-wrapper select {
            width: 48.5%!important;
        }

        .header_row td{
            padding:10px 5px !important;
        }
        tbody td{
            padding:7px !important;
            font-size:13px !important;
        }

        .body-top, td, th, input {
          font-family: 'Poppins', sans-serif !important;
          letter-spacing:0.5px;
        }
        .followUp {
        position: relative;
        display: inline-block;
        }
        .followUp .followUpInnerText {
        visibility: hidden;
        width: 150px;
        background-color: white;
        color: #333;
        text-align: left;
        border-radius: 1px;
        top:-6em;
        padding: 5px 5px;
        border :1px solid #ddd;
        /* Position the tooltip */
        position: absolute;
        z-index: 1;
        }
        .followUp:hover .followUpInnerText {
        visibility: visible;
        }
        input[type=text], input[type=number], select, #filter_submit{
            border: 1px solid #ccc !important;
            border-radius: 4px !important; 
            resize: vertical;
            width:100% !important;
            height: 35px;
            padding:0 !important;
            margin:0 !important;
        }   
        
        div.col-md-2{
            width:20% !important;
        }
        div.col-md-1{
            display:none !important;
        }
        select{
            padding: 5px 12px !important;
        }

        input[type=text], input[type=number]{
            text-align: left;
            padding: 12px !important;
        }
        .divTable .row:nth-child(odd) {
            text-align:left;
            padding: 10px 0 10px 0 !important;
            margin:0 !important;
        }

        .row{
            margin:0 !important;
        }
        .ui-selectmenu-button.ui-button{
            background:transparent;
            color:#555;
            font-family: 'Poppins', sans-serif !important;
            letter-spacing:0.5px;
            font-weight:400;
        }

        #status_summary{
            line-height: 2.5em;
            text-align:left;
            max-width: 77%;
            margin-top: 1em;
            float:left;
        }

        .badge, #filter_submit {
            background-color: #2d3691 !important;
        }

        .btn {
            border: none;
        }
        
        .print_btn{
            background:#ff0000 ;
            padding: 9px 25px 9px 35px !important;
        }

        .print_btn:hover{
            background:#c10505 ;
            
        }

        .kiosk_btn{
            background:#2d3691 ;
            padding: 9px 30px !important;
        }

        .kiosk_btn:hover{
            background:#1e256f ;
        }

        i.fa.fa-calendar {
            position: absolute;
            top: 12px;
            right: 29px;
        }
        .fa-stack-2x {
            font-size: 1.8em;
            text-align: left;
        }

        a#refreshme, a#setting_cog {
            color:#2d3691;
            vertical-align: middle;
            font-size: 12px;
        }

        i.fa.fa-refresh.fa-2x.fa-fw, i.fa.fa-cog.fa-2x.fa-fw {
            vertical-align: middle;
        }
        i#print_caret {
            padding-right: 10px;
            color:#2d3691;
        }
        span#flb_caret {
            vertical-align: -webkit-baseline-middle;
            color:#2d3691;
        }
        .xdsoft_datetimepicker .xdsoft_calendar table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed !important;
        }

        .xdsoft_label.xdsoft_year {
            width: 55px;
        }

        .xdsoft_label.xdsoft_month {
            width: 97px;
        }
        .appt_status{cursor: pointer;}
        .providerPopup{cursor: pointer;}

    </style>

</head>

<body class="body-top">
    <?php
    if (($GLOBALS['medex_enable'] == '1') && (empty($_REQUEST['nomenu']))) {
        $MedEx->display->navigation($logged_in);
    }
    ?>
    <div class="container-fluid" style="margin-top: 20px;">
    <div class="row-fluid" id="flb_selectors" style="display:<?php echo attr($setting_selectors); ?>;">
        <div class="col-sm-12">
            <div class="showRFlow" id="show_flows" style="text-align:center;margin:10px auto;" name="kiosk_hide">
                <div class="title" style="padding-bottom:20px;"><?php echo xlt('Flow Board'); ?></div>
                <div name="div_response" id="div_response" class="nodisplay"></div>
                <?php
                if ($GLOBALS['medex_enable'] == '1') {
                    $col_width = "3";
                } else {
                    $col_width = "4";
                    $last_col_width = "nodisplay";
                }
                ?>
                <br/>
                <form name="flb" id="flb" method="post">
                    <div class=" text-center row divTable" style="width: 100%;padding:10px 0;margin: 10px auto;">
										<div class="row">
											<div class="col-md-1 col-sm-1"></div>
											<div class="col-md-2 col-sm-2"><label>Visit Type</label></div>
											<div class="col-md-2 col-sm-2"><label>Visit Status</label></div>
											<div class="col-md-2 col-sm-2"><label>All Clinics</label></div>
											<div class="col-md-2 col-sm-2"><label>All Units</label></div>		
											<div class="col-md-2 col-sm-2"><label>All Providers</label></div>
											<div class="col-md-1 col-sm-1"></div>		
										</div>
										
										<!-- 2nd -->
										<div class="row">
											<div class="col-md-1 col-sm-1"></div>
											<div class="col-md-2 col-sm-2">
												<select id="form_apptcat" name="form_apptcat" class="form-group ui-selectmenu-button ui-button ui-widget ui-selectmenu-button-closed ui-corner-all"
																	onchange="autorefresh();" title="">
															<?php
															$categories = fetchAppointmentCategories();
															echo "<option value=''>" . xlt("All Visit Type") . "</option>";
															while ($cat = sqlFetchArray($categories)) {
																	echo "<option value='" . attr($cat['id']) . "'";
																	if ($cat['id'] == $_POST['form_apptcat']) {
																			echo " selected='true' ";
																	}
																	echo ">" . xlt($cat['category']) . "</option>";
															}
															?>
													</select>
											</div>
											<div class="col-md-2 col-sm-2">
                                                <select id="form_apptstatus" name="form_apptstatus" class="form-group ui-selectmenu-button ui-button ui-widget ui-selectmenu-button-closed ui-corner-all"
                                                        onchange="autorefresh();">
                                                    <option value=""><?php echo xlt("All Visit Status"); ?></option>

                                                    <?php
                                                    $apptstats = sqlStatement("SELECT * FROM list_options WHERE list_id = 'apptstat' AND activity = 1 ORDER BY seq");
                                                    while ($apptstat = sqlFetchArray($apptstats)) {
                                                        echo "<option value='" . attr($apptstat['option_id']) . "'";
                                                        if ($apptstat['option_id'] == $_POST['form_apptstatus']) {
                                                            echo " selected='true' ";
                                                        }
                                                        echo ">" . xlt($apptstat['title']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
										    </div>
											<div class="col-md-2 col-sm-2">
                                            <?php $primary_service_array = array(); ?>
                                                 <select class="form-group ui-selectmenu-button ui-button ui-widget ui-selectmenu-button-closed ui-corner-all" id="form_facility" name="form_facility"
                                                    <?php
                                                    $fac_sql = sqlStatement("SELECT * FROM facility ORDER BY id");
                                                    while ($fac = sqlFetchArray($fac_sql)) {
                                                        if($fac['primary_business_entity'] == 1){
                                                            $primary_service_array[] = $fac['id'];
                                                        }
                                                        $true = ($fac['id'] == $_POST['form_facility']) ? "selected=true" : '';
                                                        $select_facs .= "<option value=" . attr($fac['id']) . " " . $true . ">" . text($fac['name']) . "</option>\n";
                                                        $count_facs++;
                                                    }
                                                    if ($count_facs < '1') {
                                                        echo "disabled";
                                                    }
                                                    ?> onchange="autorefresh();">
                                                    <option value=""><?php echo xlt('All Clinics'); ?></option>
                                                    <?php echo $select_facs; ?>
                                                </select>
                                                 <?php
                            // Build a drop-down list of ACTIVE clinics.
                            $query_cli = "SELECT id, lname, fname, mname, facility_id FROM users WHERE " .
                                "authorized = 1  AND active = 1 AND username > '' AND facility_id NOT IN (". implode("','",$primary_service_array).") ORDER BY fname, mname, lname"; #(CHEMED) facility filter
                            //$query_cli = "SELECT DISTINCT table2.id AS id ,table2.lname AS lname, table2.fname AS fname, table2.mname AS mname, table2.facility_id AS facility_id FROM openemr_postcalendar_events AS table1 LEFT JOIN users AS table2 ON table1.pc_aid = table2.id WHERE table2.authorized = 1 AND table2.active = 1 AND table2.username > '' AND table1.pc_additional_info LIKE 'a:1:{s:4:\"unit\";i:1;}' ORDER BY fname,mname, lname";
                            $ures_cli = sqlStatement($query_cli);
                            while ($urow_cli = sqlFetchArray($ures_cli)) {
                                $provid_cli = $urow_cli['id'];
                                $select_provs_cli .= "    <option value='" . attr($provid_cli) . "'";
                                if (isset($_POST['form_provider_cli']) && $provid_cli == $_POST['form_provider_cli']) {
                                    $select_provs_cli .= " selected";
                                } elseif (!isset($_POST['form_provider_cli']) && $_SESSION['userauthorized'] && $provid_cli == $_SESSION['authUserID']) {
                                    $select_provs_cli .= " selected";
                                }
                                //if(!in_array($urow_cli['facility_id'], $primary_service_array)) {
                                    $select_provs_cli .= ">" . text($urow_cli['fname']) . " " . text($urow_cli['mname']) ." " . text($urow_cli['lname']) . "\n";
                                //}
                                $count_provs_cli++;
                            }
                            ?>
                                            </div>
											<div class="col-md-2 col-sm-2">
                                                <select class="form-group ui-selectmenu-button ui-button ui-widget ui-selectmenu-button-closed ui-corner-all" id="form_provider_cli" name="form_provider_cli"
                                                <?php 
                                                // if ($count_provs_cli < '2') { 
                                                //     echo "disabled"; 
                                                // } 
                                                ?> onchange="autorefresh()">;
                                                <option value="" selected><?php echo xlt('All Units'); ?></option>
                                                <?php echo $select_provs_cli; ?>
                                                </select>
                                            </div>		
											<div class="col-md-2 col-sm-2">
                                            <?php
                            // Build a drop-down list of ACTIVE providers.
                            
                            $query = "SELECT id, lname, fname, mname, facility_id FROM users WHERE " .
                                "authorized = 1  AND active = 1 AND username > '' AND facility_id IN (". implode("','",$primary_service_array).") ORDER BY lname, fname"; #(CHEMED) facility filter
                            //$query = "SELECT DISTINCT table2.id AS id ,table2.lname AS lname, table2.fname AS fname,table2.mname AS mname, table2.facility_id AS facility_id FROM openemr_postcalendar_events AS table1 LEFT JOIN users AS table2 ON table1.pc_aid = table2.id WHERE table2.authorized = 1 AND table2.active = 1 AND table2.username > '' AND table1.pc_additional_info LIKE 'a:1:{s:4:\"unit\";i:0;}' ORDER BY lname, fname";
                            $ures = sqlStatement($query);
                            while ($urow = sqlFetchArray($ures)) {
                                $provid = $urow['id'];
                                $select_provs .= "    <option value='" . attr($provid) . "'";
                                if (!isset($_POST['form_provider']) && $_SESSION['userauthorized'] && $provid == $_SESSION['authUserID']) {
                                    $select_provs .= " selected";
                                    }
                                elseif (isset($_POST['form_provider']) && $provid == $_POST['form_provider']) {
                                    $select_provs .= " selected";
                                }
                                //  elseif (!isset($_POST['form_provider']) && $_SESSION['userauthorized'] && $provid == $_SESSION['authUserID']) {
                                //     $select_provs .= " selected";
                                // }
                                //if(in_array($urow['facility_id'], $primary_service_array)) {
                                $select_provs .= ">" . text($urow['fname']) ." ". text($urow['lname']) . "\n";
                                //}
                                $count_provs++;
                            }
                            ?>
                                                <select class="form-group ui-selectmenu-button ui-button ui-widget ui-selectmenu-button-closed ui-corner-all" id="form_provider" name="form_provider" <?php
                                                // if ($count_provs < '2') {
                                                //     echo "disabled";
                                                // }
                                                ?> onchange="autorefresh();">
                                                    <option value="" selected><?php echo xlt('All Providers'); ?></option>

                                                    <?php echo $select_provs; ?>
                                                </select>
                                                
                                            </div>
											<div class="col-md-1 col-sm-1"></div>		
										</div>

										<div class="row">
											<div class="col-md-1 col-sm-1"></div>
											<div class="col-md-2 col-sm-2">Patient Name</div>
											<div class="col-md-2 col-sm-2">Patient ID</div>
											<div class="col-md-2 col-sm-2">From</div>
											<div class="col-md-2 col-sm-2">To</div>		
											<div class="col-md-2 col-sm-2"></div>
											<div class="col-md-1 col-sm-1"></div>		
										</div>

										<div class="row">
											<div class="col-md-1 col-sm-1"></div>
											<div class="col-md-2 col-sm-2">
                                            <input type="text"
                                                placeholder="<?php echo xla('Patient Name'); ?>"
                                                class="form-control input-sm" id="form_patient_name" name="form_patient_name"
                                                value="<?php echo (!is_null($form_patient_name)) ? attr($form_patient_name) : ""; ?>"
                                                onKeyUp="autorefresh();">
                                            </div>
											<div class="col-md-2 col-sm-2">
                                            <input placeholder="<?php echo xla('Patient ID'); ?>"
                                                class="form-control input-sm" type="text"
                                                id="form_patient_id" name="form_patient_id"
                                                value="<?php echo ($form_patient_id) ? attr($form_patient_id) : ""; ?>"
                                                onKeyUp="autorefresh();">
                                            </div>
											<div class="col-md-2 col-sm-2">
                                            <i class="fa fa-calendar" aria-hidden="true"></i>
                                            <input type="text"
                                                   id="form_from_date" name="form_from_date"
                                                   class="datepicker form-control input-sm text-center"
                                                   value="<?php echo attr(oeFormatShortDate($from_date)); ?>"
                                                   style="">
                                            </div>
											<div class="col-md-2 col-sm-2">
                                            <i class="fa fa-calendar" aria-hidden="true"></i>
                                            <input type="text"
                                                   id="form_to_date" name="form_to_date"
                                                   class="datepicker form-control input-sm text-center"
                                                   value="<?php echo attr(oeFormatShortDate($to_date)); ?>"
                                                   >
                                            </div>		
											<div class="col-md-2 col-sm-2">
                                            <input href="#"
                                                   class="btn btn-primary pull-right"
                                                   type="submit" id="filter_submit"
                                                   value="<?php echo xla('Filter'); ?>">
                                            </div>
											<div class="col-md-1 col-sm-1 col-sm-1"></div>		
										</div>

										<div class="col-sm-<?php echo $col_width; ?> text-center visit-filter-wrapper" style="margin-top:15px;">
														<!-- visit type -->
										
										<!-- visit type -->
										<!-- visit status-->
                            
							<!-- visit status-->
                            
                    </div>
                        
                        <!-- <div class="facility-filter-wrapper col-sm-<?php if ($col_width == 4) { echo $col_width + 1; } else { echo $col_width; } ?> text-center" style="margin-top:15px;"> -->
                           
                            <!-- Code for clinics filter starts here -->
                           
                            
                            <!-- Code for clinics filter ends here -->
                            

                            
                            
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div style="margin: 0 auto;" class="input-append">
                                    <table class="table-hover table-condensed" style="margin:0 auto;">
                                        <?php
                                        if ($GLOBALS['ptkr_date_range'] == '1') {
                                            $type = 'date';
                                            $style = '';
                                        } else {
                                            $type = 'hidden';
                                            $style = 'display:none;';
                                        } ?>
                                            
                                        </tr>
                                        
                                        <tr>
                                            <td class="text-center" colspan="2">
                                                
                                                <input type="hidden" id="kiosk" name="kiosk"
                                                    value="<?php echo attr($_REQUEST['kiosk']); ?>">
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <?php
                                if ($GLOBALS['medex_enable'] == '1') {
                                    ?>

                                    <div class="text-left" style="margin: 0 auto;">
                                        <span class="bold" style="text-decoration:underline;font-size:1.2em;">MedEx <?php echo xlt('Reminders'); ?></span><br/>
                                        <div class="text-left blockquote" style="width: 65%;">
                                            <a href="https://medexbank.com/cart/upload/index.php?route=information/campaigns&amp;g=rem"
                                            target="_medex">
                                                <?php echo $current_events; ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="col-md-8"></div>
                        </div>
                        <div id="message" class="warning"></div>
                    </div>
                </form>
        </div>
    </div>
    <div class="row-fluid col-sm-12">
        <div class="row divTable">
            <div class="col-sm-12 text-center" style='margin:10px 0;'>
                <span class="hidden-xs" id="status_summary">
                    // <?php
                    // $statuses_output = "<span style='margin:0 10px;'><em>" . xlt('Total patients') . ':</em> <span class="badge">' . text($appointments_status_logged_in_provider['count_all']) . "</span></span>";
                    // unset($appointments_status_logged_in_provider['count_all']);
                    // foreach ($appointments_status_logged_in_provider as $status_symbol => $count) {
                    //     $statuses_output .= " | <span style='margin:0 10px;'><em>" . text(xl_list_label($statuses_list[$status_symbol])) . ":</em> <span class='badge'>" . text($count) . "</span></span>";
                    // }
                    // echo $statuses_output;
                    // ?>
                </span>
                <span id="pull_kiosk_right" class="pull-right">
                  <a id='setting_cog'><i class="fa fa-cog fa-2x fa-fw">&nbsp;</i></a>

                  <label for='setting_new_window' id='settings'>
                    <input type='checkbox' name='setting_new_window' id='setting_new_window'
                           value='<?php echo $setting_new_window; ?>' <?php echo $setting_new_window; ?> />
                        <?php echo xlt('Open Patient in New Window'); ?>
                  </label>
                  <a id='refreshme'><i class="fa fa-refresh fa-2x fa-fw">&nbsp;</i></a>
                  <span class="fa-stack fa-lg" id="flb_caret" onclick="toggleSelectors();"
                        title="<?php echo xla('Show/Hide the Selection Area'); ?>"
                        style="color:<?php echo $color = ($setting_selectors == 'none') ? 'red' : '#2d3691'; ?>;">
                    <i class="fa fa-square-o fa-stack-2x"></i>
                    <i id="print_caret"
                       class='fa fa-caret-<?php echo $caret = ($setting_selectors == 'none') ? 'down' : 'up'; ?> fa-stack-1x'></i>
                  </span>

                  <!-- <a class='btn btn-primary print_btn' onclick="print_FLB();"> <?php echo xlt('Print'); ?> </a>
                  <i class="fa fa-print" aria-hidden="true" style="position: absolute; top: 13px; right: 177px; color:white;"></i> -->
                <?php if ($GLOBALS['new_tabs_layout']) { ?>
                  <!-- <a class='btn btn-primary kiosk_btn' onclick="kiosk_FLB();"> <?php echo xlt('Kiosk'); ?> </a> -->
                    <?php } ?>
                </span>
            </div>

            <div class="col-sm-12 textclear" id="flb_table" name="flb_table">
                <?php
}
                //end of if !$_REQUEST['flb_table'] - this is the table we fetch via ajax during a refreshMe() call
                ?>
                <table class="table table-responsive table-condensed table-hover table-bordered">
                    <thead>
                    <tr bgcolor="#ebe8fd" class="small bold header_row text-center">
                        <?php if ($GLOBALS['ptkr_show_pid']) { ?>
                            <td class="dehead hidden-xs text-center" name="kiosk_hide">
                                <?php echo xlt('PID'); ?>
                            </td>
                        <?php } ?>
                        <td class="dehead text-center" style="max-width:150px;">
                            <?php echo xlt('Order No'); ?>
                        </td>
                        <td class="dehead text-center" style="max-width:150px;">
                            <?php echo xlt('Patient'); ?>
                        </td>
                        <td class="dehead text-center" style="max-width:150px;">
                            <?php echo xlt('Actions'); ?>
                        </td>
                        <!-- <?php if ($GLOBALS['ptkr_visit_reason'] == '1') { ?>
                            <td class="dehead hidden-xs text-center" name="kiosk_hide">
                                <?php echo xlt('Reason'); ?>
                            </td>
                        <?php } ?>
                        <?php if ($GLOBALS['ptkr_show_encounter']) { ?>
                            <td class="dehead text-center hidden-xs hidden-sm" name="kiosk_hide">
                                <?php echo xlt('Encounter'); ?>
                            </td>
                        <?php } ?> -->

                        <?php if ($GLOBALS['ptkr_date_range'] == '1') { ?>
                            <td class="dehead hidden-xs text-center" name="kiosk_hide">
                                <?php echo xlt('Appt Date'); ?>
                            </td>
                        <?php } ?>
                        <td class="dehead text-center">
                            <?php echo xlt('Appt Time'); ?>
                        </td>
                        <td class="dehead hidden-xs text-center">
                            <?php echo xlt('Arrive Time'); ?>
                        </td>
                        <td class="dehead visible-xs hidden-sm hidden-md hidden-lg text-center">
                            <?php echo xlt('Arrival'); ?>
                        </td>
                        <td class="dehead hidden-xs text-center">
                            <?php echo xlt('Appt Status'); ?>
                        </td>
                        <td class="dehead hidden-xs text-center">
                            <?php echo xlt('Current Status'); ?>
                        </td>
                        <td class="dehead visible-xs hidden-sm hidden-md hidden-lg text-center">
                            <?php echo xlt('Current'); ?>
                        </td>
                        <td class="dehead hidden-xs text-center" name="kiosk_hide">
                            <?php echo xlt('Visit Type'); ?>
                        </td>
                        <td class="dehead text-center hidden-xs">
                                <?php echo xlt('Unit'); ?>
                            </td>
                        <?php // } ?>
                        <?php // if (count($chk_prov) > 0) { ?>
                            <td class="dehead text-center hidden-xs">
                                <?php echo xlt('Provider'); ?>
                            </td>
                        <?php // } ?>
                        <td class="dehead text-center">
                            <?php echo xlt('Total Time'); ?>
                        </td>
                        <td class="dehead  hidden-xs text-center">
                            <?php echo xlt('Check Out Time'); ?>
                        </td>
                        <td class="dehead  visible-xs hidden-sm hidden-md hidden-lg text-center">
                            <?php echo xlt('Out Time'); ?>
                        </td>
                        <?php
                        if ($GLOBALS['ptkr_show_staff']) { ?>
                            <td class="dehead hidden-xs hidden-sm text-center" name="kiosk_hide">
                                <?php echo xlt('Updated By'); ?>
                            </td>
                            <?php
                        }
                        if ($_REQUEST['kiosk'] != '1') {
                            if ($GLOBALS['drug_screen']) { ?>
                                <td class="dehead center hidden-xs " name="kiosk_hide">
                                    <?php echo xlt('Random Drug Screen'); ?>
                                </td>
                                <td class="dehead center hidden-xs " name="kiosk_hide">
                                    <?php echo xlt('Drug Screen Completed'); ?>
                                </td>
                                <?php
                            }
                        } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $prev_appt_date_time = "";
                    foreach ($appointments as $appointment) {
                        // Collect appt date and set up squashed date for use below
                        // echo "<pre>";
                        // print_r($appointments);
                        // echo "</pre>";
                        // die;
                        $date_appt = $appointment['pc_eventDate'];
                        $date_squash = str_replace("-", "", $date_appt);
                        if (empty($appointment['room']) && ($logged_in)) {
                            //Patient has not arrived yet, display MedEx Reminder info
                            //one icon per type of response.
                            //If there was a SMS dialog, display it as a mouseover/title
                            //Display date received also as mouseover title.
                            $other_title = '';
                            $title = '';
                            $icon2_here = '';
                            $icon_CALL = '';
                            $icon_4_CALL = '';
                            $appt['stage'] = '';
                            $icon_here = array();
                            $prog_text = '';
                            $CALLED = '';
                            $FINAL = '';
                            $icon_CALL = '';

                            $query = "SELECT * FROM medex_outgoing WHERE msg_pc_eid =? ORDER BY msg_date";
                            $myMedEx = sqlStatement($query, array($appointment['eid']));
                            /**
                             * Each row for this pc_eid in the medex_outgoing table represents an event.
                             * Every event is recorded in $prog_text.
                             * A modality is represented by an icon (eg mail icon, phone icon, text icon).
                             * The state of the Modality is represented by the color of the icon:
                             *      CONFIRMED       =   green
                             *      READ            =   blue
                             *      FAILED          =   pink
                             *      SENT/in process =   yellow
                             *      SCHEDULED       =   white
                             * Icons are displayed in their highest state.
                             */
                            $FINAL = '';
                            while ($row = sqlFetchArray($myMedEx)) {
                                // Need to convert $row['msg_date'] to localtime (stored as GMT) & then oeFormatShortDate it.
                                // I believe there is a new GLOBAL for server timezone???  If so, it will be easy.
                                // If not we need to import it from Medex through medex_preferences.  It should really be in openEMR though.
                                // Delete when we figure this out.
                                $other_title = '';
                                if (!empty($row['msg_extra_text'])) {
                                    $local = attr($row['msg_extra_text']) . " |";
                                }
                                $prog_text .= attr(oeFormatShortDate($row['msg_date'])) . " :: " . attr($row['msg_type']) . " : " . attr($row['msg_reply']) . " | " . $local . " |";

                                if ($row['msg_reply'] == 'Other') {
                                    $other_title .= $row['msg_extra_text'] . "\n";
                                    $icon_extra .= str_replace(
                                        "EXTRA",
                                        attr(oeFormatShortDate($row['msg_date'])) . "\n" . xla('Patient Message') . ":\n" . attr($row['msg_extra_text']) . "\n",
                                        $icons[$row['msg_type']]['EXTRA']['html']
                                    );
                                    continue;
                                } elseif ($row['msg_reply'] == "FAILED") {
                                    $appointment[$row['msg_type']]['stage'] = "FAILED";
                                    $icon_here[$row['msg_type']] = $icons[$row['msg_type']]['FAILED']['html'];
                                } elseif (($row['msg_reply'] == "CONFIRMED") || ($FINAL)) {
                                    $appointment[$row['msg_type']]['stage'] = "CONFIRMED";
                                    $FINAL = $icons[$row['msg_type']]['CONFIRMED']['html'];
                                    $icon_here[$row['msg_type']] = $FINAL;
                                    continue;
                                } elseif ($row['msg_type'] == "NOTES") {
                                    $CALLED = "1";
                                    $FINAL = $icons['NOTES']['CALLED']['html'];
                                    $FINAL = str_replace("Call Back: COMPLETED", attr(oeFormatShortDate($row['msg_date'])) . " :: " . xla('Callback Performed') . " | " . xla('NOTES') . ": " . $row['msg_extra_text'] . " | ", $FINAL);
                                    $icon_CALL = $icon_4_call;
                                    continue;
                                } elseif (($row['msg_reply'] == "READ") || ($appointment[$row['msg_type']]['stage'] == "READ")) {
                                    $appointment[$row['msg_type']]['stage'] = "READ";
                                    $icon_here[$row['msg_type']] = $icons[$row['msg_type']]['READ']['html'];
                                } elseif (($row['msg_reply'] == "SENT") || ($appointment[$row['msg_type']]['stage'] == "SENT")) {
                                    $appointment[$row['msg_type']]['stage'] = "SENT";
                                    $icon_here[$row['msg_type']] = $icons[$row['msg_type']]['SENT']['html'];
                                } elseif (($row['msg_reply'] == "To Send") || (empty($appointment['stage']))) {
                                    if (($appointment[$row['msg_type']]['stage'] != "CONFIRMED") &&
                                        ($appointment[$row['msg_type']]['stage'] != "READ") &&
                                        ($appointment[$row['msg_type']]['stage'] != "SENT") &&
                                        ($appointment[$row['msg_type']]['stage'] != "FAILED")) {
                                        $appointment[$row['msg_type']]['stage'] = "QUEUED";
                                        $icon_here[$row['msg_type']] = $icons[$row['msg_type']]['SCHEDULED']['html'];
                                    }
                                }
                                //these are additional icons if present
                                if (($row['msg_reply'] == "CALL") && (!$CALLED)) {
                                    $icon_here = '';
                                    $icon_4_CALL = $icons[$row['msg_type']]['CALL']['html'];
                                    $icon_CALL = "<span onclick=\"doCALLback('" . attr($date_squash) . "','" . attr($appointment['eid']) . "','" . attr($appointment['pc_cattype']) . "')\">" . $icon_4_CALL . "</span>
                                    <span class='hidden' name='progCALLback_" . attr($appointment['eid']) . "' id='progCALLback_" . attr($appointment['eid']) . "'>
                                      <form id='notation_" . attr($appointment['eid']) . "' method='post' 
                                      action='#'>
                                        <h4>" . xlt('Call Back Notes') . ":</h4>
                                        <input type='hidden' name='pc_eid' id='pc_eid' value='" . attr($appointment['eid']) . "'>
                                        <input type='hidden' name='pc_pid' id='pc_pid' value='" . attr($appointment['pc_pid']) . "'>
                                        <input type='hidden' name='campaign_uid' id='campaign_uid' value='" . attr($row['campaign_uid']) . "'>
                                        <textarea name='txtCALLback' id='txtCALLback' rows=6 cols=20></textarea>
                                        <input type='submit' name='saveCALLback' id='saveCALLback' value='" . xla("Save") ."'>
                                      </form>
                                    </span>
                                      ";
                                } elseif ($row['msg_reply'] == "STOP") {
                                    $icon2_here .= $icons[$row['msg_type']]['STOP']['html'];
                                } elseif ($row['msg_reply'] == "Other") {
                                    $icon2_here .= $icons[$row['msg_type']]['Other']['html'];
                                } elseif ($row['msg_reply'] == "CALLED") {
                                    $icon2_here .= $icons[$row['msg_type']]['CALLED']['html'];
                                }
                            }
                            //if pc_apptstatus == '-', update it now to=status
                            if (!empty($other_title)) {
                                $appointment['messages'] = $icon2_here . $icon_extra;
                            }
                        }

                        if ($appointment['pc_additional_info']) {
                            $appointment['pc_additional_info'] = unserialize($appointment['pc_additional_info']);
                            $docname="unassigned";
                            if ($appointment['pc_additional_info']['visibility_status']) {
                                if($form_provider_cli!=""){
                                    $row_id = $appointment['pc_additional_info']['assoc_eid'];
                                    $prov_query = sqlStatement("SELECT pc_aid FROM openemr_postcalendar_events WHERE pc_eid='$row_id' ");

                                    $prov_name = array();
                                    while ($row = sqlFetchArray($prov_query)){
                                            $prov_name = $row['pc_aid'];
                                    }

                                    $doc_query = sqlStatement("SELECT fname,mname,lname from users where id='$prov_name' ");
        
                                    $result2 = array();
                                    while ($row = sqlFetchArray($doc_query)){
                                     $result2['name'] = $row['fname'].' '.$row['mname'].' '.$row['lname'];
                                     $doc__name = $result2['name'];
                                    }


                                    $docname = $doc__name;
                                }
                                else{
                                   continue;
                                }
                            }
                        }

                        // Collect variables and do some processing
                        if($chk_prov[$appointment['uprovider_id']]) {
                            $docname = $chk_prov[$appointment['uprovider_id']];
                            if (strlen($docname) <= 3) {
                                continue;
                            }
                        } else {
                            if(!$docname)
                            $docname = 'Unassigned';
                        }
                        if($found_units[$appointment['uprovider_id']]) {
                            $unit_docname = $found_units[$appointment['uprovider_id']];                    
                            if (strlen($unit_docname) <= 3) {
                                continue;
                            }
                        } else if($appointment['pc_additional_info'] && $appointment['pc_additional_info']['assoc_aid']) {
                            $unit_id = $appointment['pc_additional_info']['assoc_aid'];
                            $unit_query = sqlStatement("SELECT fname,mname,lname from users where id='$unit_id' ");

	                    	$result = array();
                             $i = 0 ;
		                    while ($row = sqlFetchArray($unit_query)){
                             $result['name'] = $row['fname'].' '.$row['mname'].' '.$row['lname'];
                             $unit_docname = $result['name'];
                            }
                           if (strlen($unit_docname) <= 3) {
                                continue;
                            }
                        } else {
                            $unit_docname = "";
                        }
                        if(empty($appointment['lname']))
                        {
                        $ptname = $appointment['fname'] . ' ' . $appointment['mname'];
                        }
                        else{
                            $ptname = $appointment['lname'] . ', ' . $appointment['fname'] . ' ' . $appointment['mname'];
                        }
                        $ptname_short = $appointment['fname'][0] . " " . $appointment['lname'][0];
                        $appt_enc = $appointment['encounter'];
                        $appt_eid = (!empty($appointment['eid'])) ? $appointment['eid'] : $appointment['pc_eid'];
                        $appt_pid = (!empty($appointment['pid'])) ? $appointment['pid'] : $appointment['pc_pid'];
                        if ($appt_pid == 0) {
                            continue; // skip when $appt_pid = 0, since this means it is not a patient specific appt slot
                        }
                        sqlStatement("update patient_tracker set encounter = (select encounter from form_encounter where pid = ".$appt_pid." ORDER BY date DESC LIMIT 1) where eid = ".$appt_eid);
                        $status = (!empty($appointment['status']) && (!is_numeric($appointment['status']))) ? $appointment['status'] : $appointment['pc_apptstatus'];
                        $appt_room = (!empty($appointment['room'])) ? $appointment['room'] : $appointment['pc_room'];
                        $appt_time = (!empty($appointment['appttime'])) ? $appointment['appttime'] : $appointment['pc_startTime'];
                        $tracker_id = $appointment['id'];
                        // reason for visit
                        if ($GLOBALS['ptkr_visit_reason']) {
                            $reason_visit = $appointment['pc_hometext'];
                        }
                        $newarrive = collect_checkin($tracker_id);
                        $newend = collect_checkout($tracker_id);
                        $colorevents = (collectApptStatusSettings($status));
                        $bgcolor = $colorevents['color'];
                        $statalert = $colorevents['time_alert'];
                        // process the time to allow items with a check out status to be displayed
                        if (is_checkout($status) && (($GLOBALS['checkout_roll_off'] > 0) && strlen($form_apptstatus) != 1)) {
                            $to_time = strtotime($newend);
                            $from_time = strtotime($datetime);
                            $display_check_out = round(abs($from_time - $to_time) / 60, 0);
                            if ($display_check_out >= $GLOBALS['checkout_roll_off']) {
                                continue;
                            }
                        }
                        $unit_name = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $unit_docname)));
                        $unitcolor = collectUnitSettings($unit_name);
                        $unit_string = "color:white;background-color:".attr($unitcolor['color']);
                        $patient_string = "color:white;background-color:".attr($bgcolor);

                        $background_string = "background-color:".attr($bgcolor);

                        echo '<tr data-apptstatus="' . attr($appointment['pc_apptstatus']) . '"
                            data-apptcat="' . attr($appointment['pc_catid']) . '"
                            data-facility="' . attr($appointment['pc_facility']) . '"
                            data-provider="' . attr($appointment['uprovider_id']) . '"
                            data-pid="' . attr($appointment['pc_pid']) . '"
                            data-pname="' . attr($ptname) . '"
                            class="text-small" >';
                        if ($GLOBALS['ptkr_show_pid']) {
                            ?>
                            <td class="detail hidden-xs" align="center" name="kiosk_hide">
                                <?php echo text($appt_pid); ?>
                            </td>
                            <?php
                        }
                        $aid = $appointment[pc_eid];
                        $action_query = sqlStatement("SELECT * FROM active_status_appt WHERE pid='$appt_pid' and appt_id='$aid'");
                        $action = array();
                      while ($row = sqlFetchArray($action_query)){
                                $action = $row;
                        }                       
                        $appt_status = $action['appt_status'];
                        $active_status_date = $action['active_status_date'];
                        $active_status_clinic = $action['active_status_clinic'];
                        $active_status_intent = $action['active_status_intent'];
                        $action_name = $action['action'];
                        $patient_chairtype=$action['patient_chairtype'];
                        ?>
                       
                         <td class="detail hidden-xs" align="center" name="kiosk_hide">
                                <?php 
                                 $max_pc_eid='';
                                 $assoc_eid=$appointment['pc_additional_info']['assoc_eid'];
                                 if($assoc_eid!=''){
                                 $pc_aid=sqlstatement("select pc_aid from  openemr_postcalendar_events where pc_eid='$assoc_eid'");
                                 $row = sqlFetchArray($pc_aid);
                                 $pc_aid_value=$row['pc_aid'];

                                 $max_order_pc_eid=sqlstatement("select arrive_order from  openemr_postcalendar_events where pc_eid='$assoc_eid'");
                                 $row_pc_eid = sqlFetchArray($max_order_pc_eid);
                                 $max_pc_eid = $row_pc_eid['arrive_order'];
                                 }
                                if(($max_pc_eid!='' || $appointment['arrive_order']!=0 || $status == '@') && date("Y-m-d") == $appointment['pc_eventDate']) 
                                {
                                    $today_date=date("Y-m-d");
                                   
                                    if($appointment['arrive_order']==0 && $appointment['pc_additional_info']['unit']==1){ //not a provider
                                    $pc_eid= $appointment['pc_eid'];
                                    $pc_aid=$appointment['uprovider_id'];
                                    $max_order=sqlstatement("select MAX(arrive_order) from  openemr_postcalendar_events where pc_aid='$pc_aid' and pc_eventDate='$today_date'");
                                    $row =  sqlFetchArray($max_order);
                                    $max = $row['MAX(arrive_order)']+1;
                                    $query = "update openemr_postcalendar_events set arrive_order = '$max' where pc_eid = '$pc_eid'";
                                    sqlStatement($query);
                                    $appointment['arrive_order']= $max;
                                    }
                                    
                                    else if($appointment['arrive_order']==0){//condition for assign doctor before arrive (beacuse new row create copy max in new row )
                                    if($max_pc_eid==0 && $status == '@' ){//check whether previous max is zero then not copy 
                                        //increse mx by selection maxorder from assoc_eid;
                                     $max_order_pc_aid=sqlstatement("select MAX(arrive_order) from  openemr_postcalendar_events where pc_aid='$pc_aid_value' and pc_eventDate='$today_date'");
                                     $row_pc_aid= sqlFetchArray($max_order_pc_aid);
                                     $max_pc_aid= $row_pc_aid['MAX(arrive_order)']+1;
                                     $query = "update openemr_postcalendar_events set arrive_order = '$max_pc_aid' where pc_eid ='$pc_eid'";
                                     sqlStatement($query);
                                     $query = "update openemr_postcalendar_events set arrive_order = '$max_pc_aid' where pc_eid ='$assoc_eid'";
                                     sqlStatement($query);
                                     $appointment['arrive_order']=$max_pc_aid;
                                    }

                                    else {//otherwise copy from previous data (i.e before assign doctor)
                                     $pc_eid=$appointment["pc_eid"];
                                     $query = "update openemr_postcalendar_events set arrive_order = '$max_pc_eid' where pc_eid ='$pc_eid'";
                                     sqlStatement($query);
                                     $appointment['arrive_order']= $max_pc_eid;
                                    }
                                     
                                    
                                    
                                   }
                                   if($appointment['arrive_order']!=0)
                                   echo $appointment['arrive_order'];
                                   

                                }
                             ?>
                         </td>
                        <td class="detail text-center hidden-xs" name="kiosk_hide" style= <?php echo $patient_string; ?>>
                            <a href="#"
                               onclick="return topatient('<?php echo attr($appt_pid); ?>','<?php echo attr($appt_enc); ?>')">
                                <?php echo text($ptname); ?></a>
                                <span style="display:block;color:black">
                                <?php if($patient_chairtype!=null){echo text($patient_chairtype);}
                                 
                                 
                         ?></span>
                        </td>
                        <td class="detail text-center visible-xs hidden-sm hidden-md hidden-lg"
                            style="white-space: normal;" name="kiosk_hide">
                            <a href="#"
                               onclick="return topatient('<?php echo attr($appt_pid); ?>','<?php echo attr($appt_enc); ?>')">
                                <?php echo text($ptname_short); ?></a>
                        </td>

                        <td class="detail text-center" style="white-space: normal;" name="kiosk_show">
                            <a href="#"
                               onclick="return topatient('<?php echo attr($appt_pid); ?>','<?php echo attr($appt_enc); ?>')">
                                <?php echo text($ptname_short); ?></a>
                        </td>

                        <!-- reason -->
                        <?php if ($GLOBALS['ptkr_visit_reason']) { ?>
                            <td class="detail hidden-xs text-center" name="kiosk_hide">
                                <?php echo text($reason_visit) ?>
                            </td>
                        <?php } ?>
                        <?php if ($GLOBALS['ptkr_show_encounter']) { ?>
                            <td class="detail hidden-xs hidden-sm text-center" name="kiosk_hide">
                                <?php if ($action_name) {
                                    echo text($action_name);
                                }
                                    else{
                                    echo "None";
                                } ?>
                            </td>
                        <?php }
                        if ($GLOBALS['ptkr_date_range'] == '1') { ?>
                            <td class="detail hidden-xs text-center" name="kiosk_hide">
                                <?php echo text(oeFormatShortDate($appointment['pc_eventDate']));
                                if($appt_status == 1){ ?>
                                                            <div class="followUp">
                                <span style="color:red">Follow Up</span>
                                <span  class="followUpInnerText" style="">
                                  <b>Date</b> : <?php echo $active_status_date?><br>
                                  <b>Clinic</b> : <?php echo trim($active_status_clinic)?><br>
                                 <span style='float-left'><b> Intent :</b> </span><?php echo trim($active_status_intent)?><br>
                               </span>
                                  </div>
                            </div>
                            <?php } ?>
                        </td>

                            </td>
                        <?php } ?>
                        <td class="detail" id="parent" align="center">
                            <?php echo oeFormatTime($appt_time); 
                            ?>
                        </td>
                        <td class="detail text-center">
                            <?php
                            if ($newarrive) {
                                echo oeFormatTime($newarrive);
                            }
                            ?>
                        </td>
                        <td class="detail hidden-xs text-center small"  style= <?php echo $background_string; ?> >
                            <?php if (empty($tracker_id)) {
                                    if(!is_null($appt_eid)){
                                        $migratedRecord =  sqlQuery("select external_emr_appointment_id from `openemr_postcalendar_events` where pc_eid =?",array($eid));
                                        if(!is_null($migratedRecord)) {
                                            $is_mig_record = true;
                                            ?>
                                        <a class="appt_status" onclick="return calendarpopupstatusonly(<?php echo attr($appt_eid) . "," . attr($date_squash).", 1"; ?>)">
          <?php
                                        }
                                    }
                             if(!$is_mig_record){ ?>
                        <a class="appt_status" onclick="return calendarpopup(<?php echo attr($appt_eid) . "," . attr($date_squash);?>)">
                                <?php } } else { ?>
                                <a class="appt_status" onclick="return bpopup(<?php echo attr($tracker_id); ?>)">
                                     <?php }
                                        // if ($appointment['room'] > '') {
                                        //     echo getListItemTitle('patient_flow_board_rooms', $appt_room);
                                        // } else {
                                            echo text(getListItemTitle("apptstat", $status)); // drop down list for appointment status
                                        //}
                                    ?>
                                </a>
                        </td>

                        <?php
                        //time in current status
                        $to_time = strtotime(date("Y-m-d H:i:s"));
                        $yestime = '0';
                        if (strtotime($newend) != '') {
                            $from_time = strtotime($newarrive);
                            $to_time = strtotime($newend);
                            $yestime = '0';
                        } else {
                            $from_time = strtotime($appointment['start_datetime']);
                            $yestime = '1';
                        }

                        $timecheck = round(abs($to_time - $from_time) / 60, 0);
                        if ($timecheck >= $statalert && ($statalert > '0')) { // Determine if the time in status limit has been reached.
                            echo "<td class='text-center  js-blink-infinite small' nowrap>  "; // and if so blink
                        } else {
                            echo "<td class='detail text-center' nowrap> "; // and if not do not blink
                        }
                        if (($yestime == '1') && ($timecheck >= 1) && (strtotime($newarrive) != '')) {
                            echo text($timecheck . ' ' . ($timecheck >= 2 ? xl('minutes') : xl('minute')));
                        } else if ($icon_here || $icon2_here || $icon_CALL) {
                            echo "<span style='font-size:0.7em;' onclick='return calendarpopup(" . attr($appt_eid) . "," . attr($date_squash) . ")'>" . implode($icon_here) . $icon2_here . "</span> " . $icon_CALL;
                        } else if ($logged_in) {
                            $pat = $MedEx->display->possibleModalities($appointment);
                            echo "<span style='font-size:0.7em;'>" . $pat['SMS'] . $pat['AVM'] . $pat['EMAIL'] . "</span>";
                        }
                        //end time in current status
                        echo "</td>";
                        ?>
                        <td class="detail hidden-xs text-center" name="kiosk_hide" >
                            <?php echo xlt($appointment['pc_title']); ?>
                        </td>
                        <?php
                        // if (count($found_units) >= 1) { ?>
                            <td class="detail text-center hidden-xs" style= <?php echo $unit_string; ?>>
                                <?php echo text($unit_docname); ?> 
                            </td>
                            <?php
                        // } ?>
                        <?php
                        // if (count($chk_prov) >= 1) { ?>                          
                            <td class="detail text-center hidden-xs">
                                <a onclick="return providerPopup(<?php echo attr($appt_eid) . "," . attr($date_squash); // calls popup for add edit calendar event?>)" class="providerPopup">
                                    <?php echo text($docname); ?>
                                </a>
                            </td>
                            <?php
                        // } ?>
                        <td class="detail text-center">
                            <?php
                            // total time in practice
                            if (strtotime($newend) != '') {
                                $from_time = strtotime($newarrive);
                                $to_time = strtotime($newend);
                            } else {
                                $from_time = strtotime($newarrive);
                                $to_time = strtotime(date("Y-m-d H:i:s"));
                            }
                            $timecheck2 = round(abs($to_time - $from_time) / 60, 0);
                            if (strtotime($newarrive) != '' && ($timecheck2 >= 1)) {
                                echo text($timecheck2 . ' ' . ($timecheck2 >= 2 ? xl('minutes') : xl('minute')));
                            }
                            // end total time in practice
                            echo text($appointment['pc_time']); ?>
                        </td>
                        <td class="detail text-center">


                        </td>
                        <?php
                        if ($GLOBALS['ptkr_show_staff'] == '1') {
                            ?>
                            <td class="detail hidden-xs hidden-sm text-center" name="kiosk_hide">
                                <?php 
                                    $user_name = text($appointment['user']);
                                    $user_fname_lname = sqlQuery("select fname, lname from users where username='$user_name'");
                                    echo $user_fname_lname['fname'].' '.$user_fname_lname['lname'];
                                ?>
                            </td>
                            <?php
                        }
                        if ($GLOBALS['drug_screen']) {
                            if (strtotime($newarrive) != '') { ?>
                                <td class="detail hidden-xs text-center" name="kiosk_hide">
                                    <?php
                                    if (text($appointment['random_drug_test']) == '1') {
                                        echo xl('Yes');
                                    } else {
                                        echo xl('No');
                                    } ?>
                                </td>
                                <?php
                            } ?>
                            <?php
                            if (strtotime($newarrive) != '' && $appointment['random_drug_test'] == '1') { ?>
                                <td class="detail hidden-xs text-center" name="kiosk_hide">
                                    <?php
                                    if (strtotime($newend) != '') {
                                        // the following block allows the check box for drug screens to be disabled once the status is check out ?>
                                        <input type=checkbox disabled='disable' class="drug_screen_completed"
                                               id="<?php echo htmlspecialchars($appointment['pt_tracker_id'], ENT_NOQUOTES) ?>" <?php if ($appointment['drug_screen_completed'] == "1") {
                                                    echo "checked";
} ?>>
                                        <?php
                                    } else {
                                        ?>
                                        <input type=checkbox class="drug_screen_completed"
                                               id='<?php echo htmlspecialchars($appointment['pt_tracker_id'], ENT_NOQUOTES) ?>'
                                               name="drug_screen_completed" <?php if ($appointment['drug_screen_completed'] == "1") {
                                                    echo "checked";
} ?>>
                                        <?php
                                    } ?>
                                </td>
                                <?php
                            } else {
                                echo "  </td>";
                            }
                        }
                        ?>
                        </tr>
                        <?php
                                           } //end foreach
                    ?>
                    </tbody>
                </table>
                <?php
                if (!$_REQUEST['flb_table']) { ?>
            </div>
        </div>
    </div><?php //end container ?>
    <!-- form used to open a new top level window when a patient row is clicked -->
    <form name='fnew' method='post' target='_blank'
          action='../main/main_screen.php?auth=login&site=<?php echo attr($_SESSION['site_id']); ?>'>
        <input type='hidden' name='patientID' value='0'/>
        <input type='hidden' name='encounterID' value='0'/>
    </form>

    <?php echo myLocalJS(); ?>
</body>
</html>
<?php
    } //end of second !$_REQUEST['flb_table']

    //$content = ob_get_clean();
    //echo $content;

    exit;

    function myLocalJS() {
?>
        <script type="text/javascript">       
            $(document).ready(function(){
                refreshMe();;
                status_summary();
            });
            $("#flb select").change(function(){
                status_summary();
            });
             function status_summary(){
                var count = 0;
                var count2 = 1;
                var count_array = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
                var len =  0;
                var appt_status = "";
                $(".appt_status").each(function(){
                    var tr = $(this).parents("tr");
                    if($(this).parents("tr").is(":visible")){
                        len++;
                    }
                });
                var str2 = "<span style=\"margin:0 10px;\"><em>Total patients:</em> <span class=\"badge\">"+len+"</span></span>";
                var appt_status = $(".appt_status").toArray();
                if(len != 0){
                    $.each(appt_status,function(index,value){
                        if($(this).parents("tr").is(":visible")){

                            count++;
                            var str = $(this).html().replace(/[^a-zA-Z ]/g, "");
                            str = str.replace(/\s+/g,' ').trim();
                            if(str=="Departed"){
                                count_array[0]++;                            
                            }
                            else if(str=="Admitted to PCS"){
                                count_array[1]++;
                            }
                            else if(str=="No show"){
                                count_array[2]++;
                            }
                            else if(str=="None"){
                                count_array[3]++;
                            }
                            else if(str=="Arrived"){
                                count_array[4]++;
                            }
                            else if(str=="Seen By Doctor"){
                                count_array[5]++;
                            }
                            else if(str=="Transferred"){
                                count_array[6]++;
                            }
                            else if(str.includes("Discharged")){
                                count_array[7]++;
                            }
                            else if(str.includes("Seen By Nurse")){
                                count_array[8]++;
                            }
                            else if(str.includes("Canceled")){
                                count_array[9]++;
                            }
                            else if(str.includes("Treatment Completed")){
                                count_array[10]++;
                            }
                            else if(str.includes("Admitted to TCU")){
                                count_array[11]++;
                            } 
                            else if(str.includes("l Left wo visit")){
                                count_array[12]++;
                            }     
                            else if(str.includes("amp Seen By Vitals Nurse")){   
                                count_array[13]++;
                            }  
                            else if(str.includes("Seen By Clinic Nurse")){   
                                count_array[14]++;
                            }  
                            else if(str.includes("Seen By Ward Nurse")){   
                                count_array[15]++;
                            } 
                              if(len == count){
                                $.each(count_array,function(index,value){
                                    if(count2 ==1 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Departed:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==2 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Admitted to PCS:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==3 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>No show:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==4 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>None:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==5 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Arrived:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==6 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Seen By Doctor:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==7 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Transferred:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==8 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Discharged:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==9 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Seen By Nurse:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==10 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Canceled:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==11 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Treatment Completed:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==12 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Admitted to TCU:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==13 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>l Left w/o visit:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==14 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>& Seen By Vitals Nurse:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==15 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Seen By Clinic Nurse:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    else if(count2 ==16 && count_array[index]!=0){
                                        str2 = str2+" | "+"<span style=\"margin:0 10px;\"><em>Seen By Ward Nurse:</em> <span class=\"badge\">"+value+"</span></span>";
                                    }
                                    count2++;
                                });
                            }
                        }   
                    });
                }
                $("#status_summary").empty();
                $("#status_summary").append(str2);
            }

            var auto_refresh = null;
            //this can be refined to redact HIPAA material using @media print options.
            top.restoreSession();
            if (top.tab_mode) {
                window.parent.$("[name='flb']").attr('allowFullscreen', 'true');
            } else {
                    $(this).attr('allowFullscreen', 'true');
            }
            <?php
            if ($_REQUEST['kiosk'] == '1') { ?>
            $("[name='kiosk_hide']").hide();
            $("[name='kiosk_show']").show();
            <?php } else { ?>
                $("[name='kiosk_hide']").show();
                $("[name='kiosk_show']").hide();
            <?php  }   ?>
            function print_FLB() {
                window.print();
            }

            function toggleSelectors() {
                if ($("#flb_selectors").css('display') === 'none') {
                    $.post("<?php echo $GLOBALS['webroot'] . "/interface/patient_tracker/patient_tracker.php"; ?>", {
                        'setting_selectors': 'block',
                        success: function (data) {

                            $("#flb_selectors").slideToggle();
                            $("#flb_caret").css('color', '#000');
                        }
                    });
                } else {
                    $.post("<?php echo $GLOBALS['webroot'] . "/interface/patient_tracker/patient_tracker.php"; ?>", {
                        'setting_selectors': 'none',
                        success: function (data) {
                            $("#flb_selectors").slideToggle();
                            $("#flb_caret").css('color', 'red');
                        }
                    });
                }
                $("#print_caret").toggleClass('fa-caret-up').toggleClass('fa-caret-down');
            }

            /**
                * This function refreshes the whole flb_table according to our to/from dates.
                */
            function refreshMe() {
                top.restoreSession();
                var posting = $.post('../patient_tracker/patient_tracker.php', {
                    flb_table: '1',
                    form_from_date: $("#form_from_date").val(),
                    form_to_date: $("#form_to_date").val(),
                    form_facility: $("#form_facility").val(),
                    form_provider: $("#form_provider").val(),
                    form_provider_cli: $("#form_provider_cli").val(),
                    form_apptstatus: $("#form_apptstatus").val(),
                    form_patient_name: $("#form_patient_name").val().trim(),
                    form_patient_id: $("#form_patient_id").val(),
                    form_apptcat: $("#form_apptcat").val(),
                    kiosk: $("#kiosk").val()
                }).done(
                    function (data) {
                        $("#flb_table").html(data);
                        if ($("#kiosk").val() === '') {
                            $("[name='kiosk_hide']").show();
                            $("[name='kiosk_show']").hide();
                        } else {
                            $("[name='kiosk_hide']").hide();
                            $("[name='kiosk_show']").show();
                        }

                        refineMe();
                        status_summary();
                    });
            }
            function refreshme() {
                // Just need this to support refreshme call from the popup used for recurrent appt
                refreshMe();
            }

            /**
                * This function hides all then shows only the flb_table rows that match our selection, client side.
                * It is called on initial load, on refresh and 'onchange/onkeyup' of a flow board parameter.
                */
            function refineMe() {
                var apptcatV = $("#form_apptcat").val();
                var apptstatV = $("#form_apptstatus").val();
                var facV = $("#form_facility").val();
                var provV = $("#form_provider").val();
                var provV_cli = $("#form_provider_cli").val();
                var pidV = String($("#form_patient_id").val());
                var pidRE = new RegExp(pidV, 'g');
                var pnameV = $("#form_patient_name").val().trim();
                var pnameRE = new RegExp(pnameV, 'ig');

                //and hide what we don't want to show
                $('#flb_table tbody tr').filter(function () {
                    var d = $(this).data();
                    meets_cat = (apptcatV === '') || (apptcatV == d.apptcat);
                    meets_stat = (apptstatV === '') || (apptstatV == d.apptstatus);
                    meets_fac = (facV === '') || (facV == d.facility);
                    meets_prov = (provV === '') || (provV == d.provider);
                    meets_prov_cli = (provV_cli === '') || (provV_cli == d.provider);
                    meets_pid = (pidV === '');
                    if ((pidV > '') && pidRE.test(d.pid)) {
                        meets_pid = true;
                    }
                    meets_pname = (pnameV === '');
                    if ((pnameV > '') && pnameRE.test(d.pname.trim())) {
                        meets_pname = true;
                    }
                    return meets_pname && meets_pid && meets_cat && meets_stat && meets_fac && meets_prov && meets_prov_cli;
                }).show();
            }

            function bpopup(tkid) {
                top.restoreSession();
								var is_physicians = '<?php echo $_SESSION['isPhysicians']; ?>';
                //dlgopen('../patient_tracker/patient_tracker_status.php?tracker_id=' + tkid + '&from_appointment_list=yes', '_blank', 500, 250);
								if(is_physicians == 'true') {
									dlgopen('../patient_tracker/patient_tracker_status.php?tracker_id=' + tkid + '&from_appointment_list=yes', '_blank',580, 480, '', '', {          
										onClosed: 'refreshme'                   
									});
								} else {
									dlgopen('../patient_tracker/patient_tracker_status.php?tracker_id=' + tkid + '&from_appointment_list=yes', '_blank',480, 300, '', '', {          
										onClosed: 'refreshme'                   
									});
								}
                return false;
            }        
            function refreshme() {
                top.restoreSession();
                location.reload();
            }
            // popup for calendar add edit
            function calendarpopup(eid, date_squash) {
                top.restoreSession();
                dlgopen('../main/calendar/add_edit_event.php?eid=' + eid + '&date=' + date_squash, '_blank', 775, 500);
                return false;
            }

            function calendarpopupstatusonly(eid, date_squash,appointment_status_mig=0) {
                top.restoreSession();
                dlgopen('../main/calendar/add_edit_event.php?eid=' + eid + '&date=' + date_squash + '&appointment_status_mig='+ appointment_status_mig,'_blank', 500, 250);
                return false;
            }
            // popup for provider popup
            function providerPopup(tkid) {
                top.restoreSession();
                dlgopen('../patient_tracker/provider_type.php?tracker_id=' + tkid + '&from_appointment_list=yes'  , '_blank', 500, 250);
                return false;
            }

            // used to display the patient demographic and encounter screens
            function topatient(newpid, enc) {
                if ($('#setting_new_window').val() === 'checked') {
                    openNewTopWindow(newpid, enc);
                }
                else {
                    top.restoreSession();
                    if (enc > 0) {
                        top.RTop.location = "<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/summary/demographics.php?set_pid=" + newpid + "&set_encounterid=" + enc;
                    }
                    else {
                        top.RTop.location = "<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/summary/demographics.php?set_pid=" + newpid;
                    }
                }
            }

            function doCALLback(eventdate, eid, pccattype) {
                $("#progCALLback_" + eid).parent().removeClass('js-blink-infinite').css('animation-name', 'none');
                $("#progCALLback_" + eid).removeClass("hidden");
                clearInterval(auto_refresh);
            }

            // opens the demographic and encounter screens in a new window
            function openNewTopWindow(newpid, newencounterid) {
                document.fnew.patientID.value = newpid;
                document.fnew.encounterID.value = newencounterid;
                top.restoreSession();
                document.fnew.submit();
            }

            //opens the two-way SMS phone app
            /**
                * @return {boolean}
                */
            function SMS_bot(pid) {
                top.restoreSession();
                var from = '<?php echo attr($from_date); ?>';
                var to = '<?php echo attr($to_date); ?>';
                var oefrom = '<?php echo attr(oeFormatShortDate($from_date)); ?>';
                var oeto = '<?php echo attr(oeFormatShortDate($to_date)); ?>';
                window.open('../main/messages/messages.php?nomenu=1&go=SMS_bot&pid=' + pid + '&to=' + to + '&from=' + from + '&oeto=' + oeto + '&oefrom=' + oefrom, 'SMS_bot', 'width=370,height=600,resizable=0');
                return false;
            }

            function kiosk_FLB() {
                $("#kiosk").val('1');
                $("[name='kiosk_hide']").hide();
                $("[name='kiosk_show']").show();
                var i = document.getElementById("flb_table");
                // go full-screen
                if (i.requestFullscreen) {
                    i.requestFullscreen();
                } else if (i.webkitRequestFullscreen) {
                    i.webkitRequestFullscreen();
                } else if (i.mozRequestFullScreen) {
                    i.mozRequestFullScreen();
                } else if (i.msRequestFullscreen) {
                    i.msRequestFullscreen();
                }
                // refreshMe();
            }
              
            $(document).ready(function () {
                refineMe();
                $("#kiosk").val('');
                $("[name='kiosk_hide']").show();
                $("[name='kiosk_show']").hide();

                onresize = function () {
                    var state = 1 >= outerHeight - innerHeight ? "fullscreen" : "windowed";
                    if (window.state === state) return;
                    window.state = state;
                    var event = document.createEvent("Event");
                    event.initEvent(state, true, true);
                    window.dispatchEvent(event);
                };

                addEventListener('windowed', function (e) {
                    $("#kiosk").val('');
                    $("[name='kiosk_hide']").show();
                    $("[name='kiosk_show']").hide();
                    //alert(e.type);
                }, false);
                addEventListener('fullscreen', function (e) {
                    $("#kiosk").val('1');
                    $("[name='kiosk_hide']").hide();
                    $("[name='kiosk_show']").show();
                    //alert(e.type);
                }, false);

                <?php if ($GLOBALS['pat_trkr_timer'] != '0') { ?>
                    var reftime = "<?php echo attr($GLOBALS['pat_trkr_timer']); ?>";
                    var parsetime = reftime.split(":");
                    parsetime = (parsetime[0] * 60) + (parsetime[1] * 1) * 1000;
                    if (auto_refresh) clearInteral(auto_refresh);
                    auto_refresh = setInterval(function () {
                        refreshMe() // this will run after every parsetime seconds
                    }, parsetime);
                    <?php } ?>

                    $('#settings').css("display", "none");
                    $('.js-blink-infinite').each(function () {
                        // set up blinking text
                        var elem = $(this);
                        setInterval(function () {
                            if (elem.css('visibility') === 'hidden') {
                                elem.css('visibility', 'visible');
                            } else {
                                elem.css('visibility', 'hidden');
                            }
                        }, 500);
                    });
                    // toggle of the check box status for drug screen completed and ajax call to update the database
                    $(".drug_screen_completed").change(function () {
                        top.restoreSession();
                        if (this.checked) {
                            testcomplete_toggle = "true";
                        } else {
                            testcomplete_toggle = "false";
                        }
                        $.post("../../library/ajax/drug_screen_completed.php", {
                            trackerid: this.id,
                            testcomplete: testcomplete_toggle
                        });
                    });

                    // mdsupport - Immediately post changes to setting_new_window
                    $('#setting_new_window').click(function () {
                        $('#setting_new_window').val(this.checked ? 'checked' : ' ');
                        $.post("<?php echo basename(__FILE__) ?>", {
                            'setting_new_window': $('#setting_new_window').val(),
                            success: function (data) {
                            }
                        });
                    });

                    $('#setting_cog').click(function () {
                        $(this).css("display", "none");
                        $('#settings').css("display", "inline");
                    });

                    $('#refreshme').click(function () {
                        refreshMe();
                        refineMe();
                    });
      
                    $('#filter_submit').click(function (e) {
                        e.preventDefault;
                        top.restoreSession;
                        refreshMe();
                        refineMe();
                    });

                    $('[data-toggle="tooltip"]').tooltip();

                    $('.datepicker').datetimepicker({
                        <?php $datetimepicker_timepicker = false; ?>
                        <?php $datetimepicker_showseconds = false; ?>
                        <?php $datetimepicker_formatInput = true; ?>
                        <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
                        <?php // can add any additional javascript settings to datetimepicker here; need to prepend first setting with a comma ?>
                    });

            });
        </script>
        <script>
        function autorefresh(){
            refineMe('provider');
            refreshMe();
            refineMe();
        }
        </script>
    <?php } ?>
