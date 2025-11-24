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
    <li class="breadcrumb-item"><a href="/apps/explorer"><?php echo __('Explorer') ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Accounts') ?></li>
</ol>

<form class="app-search d-block pt-0" method="get" action="">
    <div class="position-relative">
        <input type="text" class="form-control" placeholder="<?php echo __('Search: Address') ?>" name="search" value="<?php echo $_GET['search'] ?>">
        <button class="btn btn-primary" type="submit"><i class="bx bx-search-alt align-middle"></i></button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-sm table-striped dataTable">
        <thead class="table-light">
            <tr>
                <th><?php echo __('Id') ?></th>
                <th><?php echo __('Public key') ?></th>
                <th><?php echo __('Block') ?></th>
                <?php echo sort_column('/apps/explorer/accounts.php?',$dm,'balance',__('Balance')) ?>
                <?php echo sort_column('/apps/explorer/accounts.php?',$dm,'height',__('Height')) ?>
                <?php echo sort_column('/apps/explorer/accounts.php?',$dm,'maturity',__('Maturity')) ?>
                <?php echo sort_column('/apps/explorer/accounts.php?',$dm,'weight',__('Weight')) ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($accounts as $account) { ?>
                <tr>
                    <td><?php echo explorer_address_link($account['id']) ?></td>
                    <td><?php echo explorer_address_pubkey($account['public_key']) ?></td>
                    <td><?php echo explorer_block_link($account['block']) ?></td>
                    <td align="right"><?php echo num($account['balance']); ?></td>
                    <td>
                        <a href="/apps/explorer/block.php?height=<?php echo $account['height']; ?>"><?php echo $account['height']; ?></a>
                    </td>
                    <td align="right"><?php echo $account['maturity']; ?></td>
                    <td align="right"><?php echo $account['weight']; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php echo $dm['paginator'] ?>

<?php
require_once __DIR__ . '/../common/include/bottom.php';
?>
