<?php

require 'dolibarr.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT . '/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once MULTISALARY_DOCUMENT_ROOT . '/lib/multisalaryinput.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("multisalaryinput@multisalaryinput", "bills", "users", "salaries"));

if (isModEnabled('project')) {
    $langs->load("projects");
}

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$note = GETPOST('note', 'restricthtml');
$label = GETPOST('label', 'alphanohtml');

$employees = GETPOST("employees", "array");
$usergroup = GETPOST("usergroup", "int");

$accountid = GETPOSTISSET('accountid') ? GETPOST('accountid', 'int') : 0;
$paymenttype = GETPOSTISSET('paymenttype') ? GETPOST('paymenttype', 'int') : 0;
$numpayment = GETPOSTISSET('num_payment') ? GETPOST('num_payment', 'int') : 0;
$projectid = GETPOSTISSET('fk_project') ? GETPOST('fk_project', 'int') : 0;

if (GETPOSTISSET('auto_create_paiement') || $action === 'add-multiple' || $action === 'save-multiple') {
    $auto_create_paiement = GETPOST("auto_create_paiement", "int");
} else {
    $auto_create_paiement = empty(getDolGlobalString('CREATE_NEW_SALARY_WITHOUT_AUTO_PAYMENT'));
}

if (GETPOSTISSET('closepaidsalary') || $action === 'add-multiple' || $action === 'save-multiple') {
    $closepaidsalary = GETPOST("closepaidsalary", "int");
} else {
    $closepaidsalary = 1;
}

$datep = dol_mktime(12, 0, 0, GETPOST("datepmonth", 'int'), GETPOST("datepday", 'int'), GETPOST("datepyear", 'int'));
$datev = dol_mktime(12, 0, 0, GETPOST("datevmonth", 'int'), GETPOST("datevday", 'int'), GETPOST("datevyear", 'int'));
$datesp = dol_mktime(0, 0, 0, GETPOST("datespmonth", 'int'), GETPOST("datespday", 'int'), GETPOST("datespyear", 'int'));
$dateep = dol_mktime(23, 59, 59, GETPOST("dateepmonth", 'int'), GETPOST("dateepday", 'int'), GETPOST("dateepyear", 'int'));

$employeesSalaryAmount = GETPOST('employees_salary_amount', 'array');

$object = new Salary($db);
$extrafields = new ExtraFields($db);

$childids = $user->getAllChildIds(1);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('multisalarycard', 'salarycard', 'globalcard'));

restrictedArea($user, 'salaries', $object->id, 'salary', '');

$title = $langs->trans('MultiSalaryInput') . " - " . $langs->trans('Card');
$help_url = "";

$formproject = new FormProjets($db);

/**
 * Actions
 */
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', [], $object, $action);

if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    $error = 0;

    switch ($action) {
        case 'add-multiple':
            $error = 0;
            if (empty($employees) && $usergroup <= 0) {
                setEventMessages($langs->trans("ErrorEmployeesOrUserGroupRequired"), null, 'errors');
                $error++;
            }

            if (!empty($auto_create_paiement) && empty($datep)) {
                $datePaymentMsg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DATE_PAIEMENT"));
                setEventMessages($datePaymentMsg, null, 'errors');
                $error++;
            }

            if (empty($datesp) || empty($dateep)) {
                $dateMsg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date"));
                setEventMessages($dateMsg, null, 'errors');
                $error++;
            }

            if (!empty($auto_create_paiement) && (empty($paymenttype) || $paymenttype < 0)) {
                setEventMessages(
                        $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PaymentMode")), null,
                        'errors');
                $error++;
            }

            if (!empty(isModEnabled('banque')) && !empty($auto_create_paiement) && !$accountid > 0) {
                $bankAccountMsg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount"));
                setEventMessages($bankAccountMsg, null, 'errors');
                $error++;
            }

            if ($error > 0) {
                $action = '';
                break;
            }

            break;

        case 'save-multiple':
            $error = 0;

            if (empty($employeesSalaryAmount)) {
                setEventMessages($langs->trans("ErrorMissingSalary"), null, 'errors');
                $action = 'add-multiple';
                break;
            }

            $db->begin();

            foreach ($employeesSalaryAmount as $employeeId => $employeeSalaryAmount) {
                if (empty($employeeSalaryAmount)) {
                    setEventMessages($langs->trans("ErrorMissingSalary"), null, 'errors');
                    $error++;
                    break;
                }

                if (price2num($employeeSalaryAmount) <= 0) {
                    setEventMessages($langs->trans("ErrorSalaryShouldBeAPositiveNumber"), null, 'errors');
                    $error++;
                    break;
                }

                $salary = new Salary($db);

                $salary->amount = price2num($employeeSalaryAmount);
                $salary->accountid = $accountid;
                $salary->fk_user = $employeeId;
                $salary->label = $label;
                $salary->datev = $datev;
                $salary->datep = $datep;
                $salary->datesp = $datesp;
                $salary->dateep = $dateep;
                $salary->note = $note;
                $salary->type_payment = ($paymenttype > 0 ? $paymenttype : 0);
                $salary->fk_user_author = $user->id;
                $salary->fk_project = $projectid;

                // Set user current salary as ref salary for the payment
                $fuser = new User($db);
                $fuser->fetch(GETPOST("fk_user", "int"));
                $salary->salary = $fuser->salary;

                // Fill array 'array_options' with data from add form
                $retSetOptionals = $extrafields->setOptionalsFromPost(null, $salary);

                if ($retSetOptionals < 0) {
                    $error++;
                    setEventMessages($extrafields->error, $extrafields->errors, 'errors');
                    break;
                }

                $retCreate = $salary->create($user);

                if ($retCreate < 0) {
                    setEventMessages($salary->error, $salary->errors, 'errors');
                    $error++;
                    break;
                }

                if (empty($auto_create_paiement)) {
                    continue;
                }

                // Create a line of payments
                $paiement = new PaymentSalary($db);
                $paiement->chid = $salary->id;
                $paiement->datepaye = $datep;
                $paiement->datev = $datev;
                $paiement->amounts = array($salary->id => price2num($employeeSalaryAmount)); // Tableau de montant
                $paiement->paiementtype = $paymenttype;
                $paiement->num_payment = $numpayment;
                $paiement->note = $note;

                $paymentid = $paiement->create($user, $closepaidsalary);

                if ($paymentid < 0) {
                    $error++;
                    setEventMessages($paiement->error, $paiement->errors, 'errors');
                    break;
                }

                $result = $paiement->addPaymentToBank($user, 'payment_salary', '(SalaryPayment)', $accountid, '', '');

                if ($result < 0) {
                    $error++;
                    setEventMessages($paiement->error, $paiement->errors, 'errors');
                    break;
                }
            }

            if (empty($error)) {
                $db->commit();
                header("Location: " . dol_buildpath('/salaries/list.php', 1));
                exit;
            }

            $db->rollback();
            $action = 'add-multiple';
            break;

        default:
            break;
    }
}



/**
 * Data
 */
$allEmployeesArray = [];
$errors = [];
$ret = getEmployeeArray($allEmployeesArray, $errors);

if ($ret < 0) {
    llxHeader("", $title, $help_url);
    dol_print_error($db, '', $errors);
    llxFooter();
}

if ($action == 'add-multiple') {
    $employeesArray = [];

    if (!empty($employees)) {
        foreach ($employees as $employeeId) {
            $employee = new User($db);

            $retFetch = $employee->fetch($employeeId);

            if ($retFetch < 0) {
                llxHeader("", $title, $help_url);
                dol_print_error($db, $employee->error, $employee->errors);
                llxFooter();
                exit;
            } elseif (empty($retFetch)) {
                setEventMessage('EmployeeNotFound', 'error');
                $action = '';
            }

            $employeesArray[] = $employee;
        }
    }

    if ($usergroup > 0) {
        $userGroupObject = new UserGroup($db);

        $userGroupObject->id = $usergroup;
        $userList = $userGroupObject->listUsersForGroup('COALESCE(employee, 0) <> 0');

        if (is_numeric($userList) && $userList < 0) {
            llxHeader("", $title, $help_url);
            dol_print_error($db, $userGroupObject->error, $userGroupObject->errors);
            llxFooter();
            exit;
        }

        if (empty($employeesArray)) {
            $employeesArray = $userList;
        } else {
            foreach ($userList as $groupuser) {
                $userFound = false;

                foreach ($employeesArray as $selectedEmployee) {
                    if ($groupuser->id == $selectedEmployee->id) {
                        $userFound = true;
                        break;
                    }
                }

                if (!$userFound) {
                    $employeesArray [] = $groupuser;
                }
            }
        }
    }
}


/*
 * 	View
 */

$form = new Form($db);

$year_current = strftime("%Y", dol_now());
$pastmonth = strftime("%m", dol_now()) - 1;
$pastmonthyear = $year_current;
if ($pastmonth == 0) {
    $pastmonth = 12;
    $pastmonthyear--;
}

if (empty($datesp) || empty($dateep)) { // We define  default date_start and date_end
    $datesp = dol_get_first_day($pastmonthyear, $pastmonth, false);
    $dateep = dol_get_last_day($pastmonthyear, $pastmonth, false);
}

llxHeader("", $title, $help_url);

print load_fiche_titre($langs->trans("NewSalaries"), '', 'salary');

print dol_get_fiche_head('', '');

if ($action != 'add-multiple') {
    require MULTISALARY_DOCUMENT_ROOT . '/tpl/multisalaryinput_firstpage.tpl.php';
} else {
    require MULTISALARY_DOCUMENT_ROOT . '/tpl/multisalaryinput_secondpage.tpl.php';
}

print dol_get_fiche_end();

llxFooter();
exit;
