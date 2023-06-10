<form name="multi-salary-form" action="<?= $_SERVER["PHP_SELF"] ?>" method="post">
    <input type="hidden" name="action" value="add-multiple"/>
    <input type="hidden" name="token" value="<?= newToken() ?>"/>
    <table class="border centpercent">
        <tr>
            <td>
                <?= $form->editfieldkey('Employees', 'employees', '', $object, 0, 'string', '', 1) ?>
            </td>
            <td>
                <?= $form->multiselectarray('employees', $allEmployeesArray, $employees, '', 0, '', 0, '50%')
                    . $form->select_dolgroups($usergroup, 'usergroup', 1) ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= $form->editfieldkey('Label', 'label', '', $object, 0, 'string', '', 1) ?>
            </td>
            <td>
                <input
                    name="label"
                    id="label"
                    class="minwidth300"
                    value="<?= ($label ? : $langs->trans("Salary")) ?>">
            </td>
        </tr>
        <tr>
            <td>
                <?= $form->editfieldkey('DateStartPeriod', 'datesp', '', $object, 0, 'string', '', 1) ?>
            </td>
            <td>
                <?= $form->selectDate($datesp, "datesp", '', '', '', 'add') ?>
            </td>
        </tr>
        <tr>
            <td>
                <?= $form->editfieldkey('DateEndPeriod', 'dateep', '', $object, 0, 'string', '', 1) ?>
            </td>
            <td>
                <?= $form->selectDate($dateep, "dateep", '', '', '', 'add') ?>
            </td>
        </tr>
<?php if (!empty(isModEnabled('project'))) : ?>
        <tr>
            <td>
                <?= $langs->trans("Project") ?>
            </td>
            <td>
                <?= img_picto('', 'project', 'class="pictofixedwidth"')
            . $formproject->select_projects(-1, $projectid, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 1) ?>
            </td>
        </tr>
<?php endif; ?>
        <tr>
            <td class="tdtop">
                <?= $langs->trans("Comments") ?>
            </td>
            <td class="tdtop">
                <textarea name="note" wrap="soft" cols="60" rows="<?= ROWS_3 ?>"><?= $note ?></textarea>
            </td>
        </tr>
        <tr>
            <td colspan="2"><hr></td>
        </tr>
        <tr>
            <td>
                <label for="auto_create_paiement"><?= $langs->trans('AutomaticCreationPayment') ?></label>
            </td>
            <td>
                <input
                    id="auto_create_paiement"
                    name="auto_create_paiement"
                    type="checkbox"
                    <?= (empty($auto_create_paiement) ? '' : 'checked="checked"') ?>
                    value="1">
            </td>
        </tr>

<?php if (!empty(isModEnabled("banque"))) : ?>
        <tr>
            <td id="label_fk_account">
                <?= $form->editfieldkey('BankAccount', 'selectaccountid', '', $object, 0, 'string', '', 1) ?>
            </td>
            <td>
                <?= img_picto('', 'bank_account', 'class="paddingrightonly"') ?>
                <?php $form->select_comptes($accountid, "accountid", 0, '', 1) ?>
            </td>
        </tr>
<?php endif; ?>
        <tr>
            <td id="label_type_payment">
                <?= $form->editfieldkey('PaymentMode', 'selectpaymenttype', '', $object, 0, 'string', '', 1) ?>
            </td>
            <td>
                <?= $form->select_types_paiements($paymenttype, "paymenttype", '', 0, 1, 0, 0, 1, '', 1) ?>
            </td>
        </tr>
        <tr class="hide_if_no_auto_create_payment">
            <td>
                <?= $form->editfieldkey('DatePayment', 'datep', '', $object, 0, 'string', '', 1) ?>
            </td>
            <td>
                <?= $form->selectDate((empty($datep) ? '' : $datep), "datep", 0, 0, 0, 'add', 1, 1) ?>
            </td>
        </tr>
        <tr class="hide_if_no_auto_create_payment">
            <td>
                <?= $form->editfieldkey('DateValue', 'datev', '', $object, 0) ?>
            </td>
            <td>
                <?= $form->selectDate((empty($datev) ? -1 : $datev), "datev", '', '', '', 'add', 1, 1) ?>
            </td>
        </tr>

<?php if (!empty(isModEnabled("banque"))) : ?>
        <tr class="hide_if_no_auto_create_payment">
            <td>
                <label for="num_payment"><?= $langs->trans('Numero') ?> <em>(<?= $langs->trans("ChequeOrTransferNumber") ?>)</em></label>
            </td>
            <td>
                <input name="num_payment" id="num_payment" type="text" value="<?= $numpayment ? : '' ?>">
            </td>
        </tr>
<?php endif; ?>

<?php $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); ?>
        <?=$hookmanager->resPrint; ?>
<?php if (empty($reshook)) : ?>
        <?= $object->showOptionals($extrafields, 'create'); ?>
<?php endif; ?>

    </table>
    <div class="center">
        <div class="hide_if_no_auto_create_payment paddingbottom">
            <input type="checkbox" <?= ($closepaidsalary > 0 ? 'checked' : '') ?> value="<?= $closepaidsalary ?>" name="closepaidsalary"><?= $langs->trans("ClosePaidSalaryAutomatically") ?>
        </div>
    </div>
    <div class="center">
        <input class="butAction" type="submit" value="<?= $langs->trans("Next") ?>"/>
    </div>
</form>
