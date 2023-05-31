<form name="multi-salary-form" action="<?= $_SERVER["PHP_SELF"] ?>" method="post">
    <input type="hidden" name="action" value="save-multiple"/>
    <input type="hidden" name="token" value="<?= newToken() ?>"/>
    <div style="display:none">
        <input type="hidden" name="label"  id="label" value="<?= $label ? : $langs->trans("Salary") ?>">
        <?= $formproject->select_projects(-1, $projectid, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 1) ?>
        <?= $form->select_comptes($accountid, "accountid", 0, '', 1) ?>
        <?= $form->select_types_paiements($paymenttype, "paymenttype", '', 0, 1, 0, 0, 1, '', 1) ?>*
        <?= $form->selectDate($datesp, "datesp", '', '', '', 'add') ?>
        <?= $form->selectDate($dateep, "dateep", '', '', '', 'add') ?>
        <?= $form->selectDate((empty($datep) ? '' : $datep), "datep", 0, 0, 0, 'add', 1, 1) ?>
        <?= $form->selectDate((empty($datev) ? -1 : $datev), "datev", '', '', '', 'add', 1, 1) ?>
        <input id="num_payment" name="num_payment" type="text" value="<?= $numpayment ?>">
        <input id="auto_create_paiement" name="auto_create_paiement" value="<?= $auto_create_paiement ?>">
        <input id="closepaidsalary" name="closepaidsalary" value="<?= $closepaidsalary ?>">
        <textarea name="note" wrap="soft" cols="60" rows="<?= ROWS_3 ?>"><?= $note ?></textarea>
        <?= $form->multiselectarray('employees', $allEmployeesArray, $employees, '', 0, '', 0, '50%')?>
        <?= $form->select_dolgroups($usergroup, 'usergroup', 1) ?>
<?php $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); ?>
        <?=$hookmanager->resPrint; ?>
<?php if (empty($reshook)) : ?>
        <?= $object->showOptionals($extrafields, 'create'); ?>
<?php endif; ?>

    </div>
    <table class="border centpercent">
<?php foreach ($employeesArray as $employee): ?>
        <tr>
            <td><?= $employee->getNomUrl() ?></td>
            <td class="left">
                <input autocomplete="off" name="employees_salary_amount[<?= $employee->id ?>]"
                       value="<?= $employeesSalaryAmount[$employee->id] ? : '' ?>"
                       placeholder="€" />
            </td>
        </tr>
<?php endforeach; ?>
    </table>
    <br>
    <br>
    <div class="center">
        <input type="submit" class="button button-save" name="save" value="<?= $langs->trans('SaveSalaries') ?>">
        <a class="butAction" href="<?= $_SERVER["PHP_SELF"] ?>"><?= $langs->trans('Cancel') ?></a>
    </div>
</form>
