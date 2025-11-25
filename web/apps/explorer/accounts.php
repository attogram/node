<?php
require_once dirname(__DIR__)."/apps.inc.php";
define("PAGE", true);
define("APP_NAME", "Explorer");
require_once ROOT. '/web/apps/explorer/include/functions.php';
$dm = get_data_model(Account::getCount(), "/apps/explorer/accounts.php?");
$accounts = Account::getAccounts($dm);

?>

<?php
require_once __DIR__. '/../common/include/top.php';
?>

<ol class="breadcrumb m-0 ps-0 h4">
    <li class="breadcrumb-item"><a href="/apps/explorer"><?= __('Explorer') ?></a></li>
    <li class="breadcrumb-item active"><?= __('Accounts') ?></li>
</ol>

<form class="app-search d-block pt-0" method="get" action="">
    <div class="position-relative">
        <input type="text" class="form-control" placeholder="<?= __('Search: Address') ?>" name="search" value="<?= $_GET['search'] ?>">
        <button class="btn btn-primary" type="submit"><i class="bx bx-search-alt align-middle"></i></button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-sm table-striped dataTable">
        <thead class="table-light">
            <tr>
                <th><?= __('Id') ?></th>
                <th><?= __('Public key') ?></th>
                <th><?= __('Block') ?></th>
                <?= sort_column('/apps/explorer/accounts.php?',$dm,'balance',__('Balance')) ?>
                <?= sort_column('/apps/explorer/accounts.php?',$dm,'height',__('Height')) ?>
                <?= sort_column('/apps/explorer/accounts.php?',$dm,'maturity',__('Maturity')) ?>
                <?= sort_column('/apps/explorer/accounts.php?',$dm,'weight',__('Weight')) ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($accounts as $account) { ?>
                <tr>
                    <td><?= explorer_address_link($account['id']) ?></td>
                    <td><?= explorer_address_pubkey($account['public_key']) ?></td>
                    <td><?= explorer_block_link($account['block']) ?></td>
                    <td align="right"><?= num($account['balance']); ?></td>
                    <td>
                        <a href="/apps/explorer/block.php?height=<?= $account['height']; ?>"><?= $account['height']; ?></a>
                    </td>
                    <td align="right"><?= $account['maturity']; ?></td>
                    <td align="right"><?= $account['weight']; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?= $dm['paginator'] ?>

<?php
require_once __DIR__ . '/../common/include/bottom.php';
?>
