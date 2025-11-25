<?php

if (!defined("ADMIN_TAB")) {
	exit;
}

global $action, $db;

if($action=="delete_tx") {
	$id = $_GET['id'];
    Mempool::delete($id);
	header("location: ".APP_URL."/?view=mempool");
	exit;
}

if($action=="empty_mempool") {
    Util::emptyMempool();
	header("location: ".APP_URL."/?view=mempool");
	exit;
}

$transactions = Transaction::mempool(100, true, false);

$count=count($transactions);

?>
<h3>
    <?= __('Mempool Transactions') ?>
    <span class="float-end badge bg-primary"><?= $count ?></span>
</h3>
<div class="table-responsive">
	<table class="table table-sm table-striped">
		<thead class="table-light">
		<tr>
			<th><?= __('Id') ?></th>
			<th><?= __('Height') ?></th>
			<th><?= __('Date') ?></th>
			<th><?= __('Src') ?></th>
			<th><?= __('Dst') ?></th>
			<th><?= __('Value') ?></th>
			<th><?= __('Fee') ?></th>
			<th><?= __('Type') ?></th>
			<th><?= __('Message') ?></th>
			<th><?= __('Peer') ?></th>
			<th><?= __('Error') ?></th>
            <th></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($transactions as $transaction) { ?>
			<tr class="<?php if (!empty($transaction['error'])) { ?>table-danger<?php } ?>">
				<td>
					<a href="/apps/explorer/tx.php?id=<?= $transaction['id'] ?>"><?= $transaction['id'] ?></a>
				</td>
                <td><?= $transaction['height'] ?></td>
				<td><?= date("Y-m-d H:i:s",$transaction['date']) ?></td>
				<td><a href="/apps/explorer/address.php?address=<?= $transaction['src'] ?>"><?= $transaction['src'] ?></a></td>
				<td><a href="/apps/explorer/address.php?address=<?= $transaction['dst'] ?>"><?= $transaction['dst'] ?></a></td>
				<td><?= $transaction['val'] ?></td>
				<td><?= $transaction['fee'] ?></td>
				<td><?= $transaction['type'] ?></td>
				<td style="word-break: break-all"><?= $transaction['message'] ?></td>
				<td><?= $transaction['peer'] ?></td>
				<td><?= $transaction['error'] ?></td>
                <td>
                    <a class="btn btn-danger btn-xs" href="<?= APP_URL ?>/?view=mempool&action=delete_tx&id=<?= $transaction['id']  ?>"
                       onclick="if(!confirm('<?= __('Delete mempool transaction?') ?>')) return false;"><?= __('Delete') ?></a>
                </td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
</div>
<a href="<?= APP_URL ?>/?view=mempool&action=empty_mempool" class="btn btn-danger"
    onclick="if(!confirm('<?= __('Confirm?') ?>')) return false"><?= __('Clear mempool') ?></a>
