<?php

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';



// Load translation files required by the page
$langs->loadLangs(array("compta", "banks", "bills", "users", "salaries", "hrm", "trips"));
if (!empty($conf->projet->enabled)) {
	$langs->load("projects");
}

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');


$label = GETPOST('label', 'alphanohtml');

$datep = dol_mktime(12, 0, 0, GETPOST("datepmonth", 'int'), GETPOST("datepday", 'int'), GETPOST("datepyear", 'int'));
$datev = dol_mktime(12, 0, 0, GETPOST("datevmonth", 'int'), GETPOST("datevday", 'int'), GETPOST("datevyear", 'int'));
$datesp = dol_mktime(12, 0, 0, GETPOST("datespmonth", 'int'), GETPOST("datespday", 'int'), GETPOST("datespyear", 'int'));
$dateep = dol_mktime(12, 0, 0, GETPOST("dateepmonth", 'int'), GETPOST("dateepday", 'int'), GETPOST("dateepyear", 'int'));

$object = new Salary($db);
$extrafields = new ExtraFields($db);

$childids = $user->getAllChildIds(1);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('salarycard', 'globalcard'));

restrictedArea($user, 'salaries', $object->id, 'salary', '');

$permissiontoread = $user->rights->salaries->read;
$permissiontoadd = $user->rights->salaries->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->salaries->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);


/**
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/salaries/list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/salaries/card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		/*var_dump($cancel);
		 var_dump($backtopage);exit;*/
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}
}

/*
 *	View
 */

$form = new Form($db);

$title = $langs->trans('Salary')." - ".$langs->trans('Card');
$help_url = "";

$year_current = strftime("%Y", dol_now());
$pastmonth = strftime("%m", dol_now()) - 1;
$pastmonthyear = $year_current;
if ($pastmonth == 0) {
    $pastmonth = 12;
    $pastmonthyear--;
}

llxHeader("", $title, $help_url);

print load_fiche_titre($langs->trans("NewSalary"), '', 'salary');


print dol_get_fiche_head('', '');

print '<table class="border centpercent">';


// Label
print '<tr><td>';
print $form->editfieldkey('Label', 'label', '', $object, 0, 'string', '', 1).'</td><td>';
print '<input name="label" id="label" class="minwidth300" value="'.(GETPOST("label") ?GETPOST("label") : $langs->trans("Salary")).'">';
print '</td></tr>';

// Date start period
print '<tr><td>';
print $form->editfieldkey('DateStartPeriod', 'datesp', '', $object, 0, 'string', '', 1).'</td><td>';
print $form->selectDate($datesp, "datesp", '', '', '', 'add');
print '</td></tr>';

// Date end period
print '<tr><td>';
print $form->editfieldkey('DateEndPeriod', 'dateep', '', $object, 0, 'string', '', 1).'</td><td>';
print $form->selectDate($dateep, "dateep", '', '', '', 'add');
print '</td></tr>';

// Amount
print '<tr><td>';
print $form->editfieldkey('Amount', 'amount', '', $object, 0, 'string', '', 1).'</td><td>';
print '<input name="amount" id="amount" class="minwidth75 maxwidth100" value="'.GETPOST("amount").'">&nbsp;';
print '<button class="dpInvisibleButtons datenow" id="updateAmountWithLastSalary" name="_useless" type="button">'.$langs->trans('UpdateAmountWithLastSalary').'</a>';
print '</td>';
print '</tr>';


// Project
if (!empty($conf->projet->enabled)) {
    $formproject = new FormProjets($db);

    print '<tr><td>'.$langs->trans("Project").'</td><td>';
    print img_picto('', 'project', 'class="pictofixedwidth"');
    print $formproject->select_projects(-1, $projectid, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 1);
    print '</td></tr>';
}

// Comments
print '<tr>';
print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
print '<td class="tdtop"><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_3.'">'.GETPOST('note', 'restricthtml').'</textarea></td>';
print '</tr>';


print '<tr><td colspan="2"><hr></td></tr>';


// Auto create payment
print '<tr><td><label for="auto_create_paiement">'.$langs->trans('AutomaticCreationPayment').'</label></td>';
print '<td><input id="auto_create_paiement" name="auto_create_paiement" type="checkbox" ' . (empty($auto_create_paiement) ? '' : 'checked="checked"') . ' value="1"></td></tr>'."\n";	// Date payment

// Bank
if (!empty($conf->banque->enabled)) {
    print '<tr><td id="label_fk_account">';
    print $form->editfieldkey('BankAccount', 'selectaccountid', '', $object, 0, 'string', '', 1).'</td><td>';
    print img_picto('', 'bank_account', 'class="paddingrighonly"');
    $form->select_comptes($accountid, "accountid", 0, '', 1); // Affiche liste des comptes courant
    print '</td></tr>';
}

// Type payment
print '<tr><td id="label_type_payment">';
print $form->editfieldkey('PaymentMode', 'selectpaymenttype', '', $object, 0, 'string', '', 1).'</td><td>';
$form->select_types_paiements(GETPOST("paymenttype", 'aZ09'), "paymenttype", '');
print '</td></tr>';

// Date payment
print '<tr class="hide_if_no_auto_create_payment"><td>';
print $form->editfieldkey('DatePayment', 'datep', '', $object, 0, 'string', '', 1).'</td><td>';
print $form->selectDate((empty($datep) ? '' : $datep), "datep", 0, 0, 0, 'add', 1, 1);
print '</td></tr>';

// Date value for bank
print '<tr class="hide_if_no_auto_create_payment"><td>';
print $form->editfieldkey('DateValue', 'datev', '', $object, 0).'</td><td>';
print $form->selectDate((empty($datev) ?-1 : $datev), "datev", '', '', '', 'add', 1, 1);
print '</td></tr>';

// Number
if (!empty($conf->banque->enabled)) {
    // Number
    print '<tr class="hide_if_no_auto_create_payment"><td><label for="num_payment">'.$langs->trans('Numero');
    print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
    print '</label></td>';
    print '<td><input name="num_payment" id="num_payment" type="text" value="'.GETPOST("num_payment").'"></td></tr>'."\n";
}

// Bouton Save payment
/*
print '<tr class="hide_if_no_auto_create_payment"><td>';
print $langs->trans("ClosePaidSalaryAutomatically");
print '</td><td><input type="checkbox" checked value="1" name="closepaidsalary"></td></tr>';
*/

// Other attributes
$parameters = array();
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (empty($reshook)) {
    print $object->showOptionals($extrafields, 'create');
}

print '</table>';

print dol_get_fiche_end();

print '<div class="center">';

print '<div class="hide_if_no_auto_create_payment paddingbottom">';
print '<input type="checkbox" checked value="1" name="closepaidsalary">'.$langs->trans("ClosePaidSalaryAutomatically");
print '</div>';

print '</div>';

$addition_button = array(
    'name' => 'saveandnew',
    'label_key' => 'SaveAndNew',
);
print $form->buttonsSaveCancel("Save", "Cancel", $addition_button);

print '</form>';
print '<script>';
print '$( document ).ready(function() {';
    print '$("#updateAmountWithLastSalary").on("click", function updateAmountWithLastSalary() {
                console.log("We click on link to autofill salary amount");
                var fk_user = $("#fk_user").val()
                var url = "'.DOL_URL_ROOT.'/salaries/ajax/ajaxsalaries.php?fk_user="+fk_user;
                if (fk_user != -1) {
                    $.get(
                        url,
                        function( data ) {
                            if(data!=null) {
                                console.log("Data returned: "+data);
                                item = JSON.parse(data);
                                if(item[0].key == "Amount") {
                                    value = item[0].value;
                                    if (value != null) {
                                        $("#amount").val(item[0].value);
                                    } else {
                                        console.error("Error: Ajax url "+url+" has returned a null value.");
                                    }
                                } else {
                                    console.error("Error: Ajax url "+url+" has returned the wrong key.");
                                }
                            } else {
                                console.error("Error: Ajax url "+url+" has returned an empty page.");
                            }
                        }
                    );

                } else {
                    alert("'.dol_escape_js($langs->trans("FillFieldFirst")).'");
                }
    });

})';
print '</script>';