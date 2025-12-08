<?php
require_once dirname(__DIR__)."/apps.inc.php";
define("PAGE", true);
define("APP_NAME", "Messages");
require_once ROOT. '/web/apps/explorer/include/functions.php';

$address = isset($_GET['address']) ? san_host($_GET['address']) : '';

function getAddressMessages($address) {
    global $db;
    if (empty($address)) {
        return [];
    }
    $sql = "SELECT * FROM transactions
            WHERE type = 1 AND message != '' AND dst = :address
            ORDER BY height DESC";
    return $db->run($sql, [":address" => $address]);
}

$messages = getAddressMessages($address);

require_once __DIR__. '/../common/include/top.php';
?>

<ol class="breadcrumb m-0 ps-0 h4">
    <li class="breadcrumb-item"><a href="/apps/explorer">Explorer</a></li>
    <li class="breadcrumb-item"><a href="/apps/messages">Messages</a></li>
    <li class="breadcrumb-item">Address</li>
</ol>

<form class="row mb-3" method="get" action="">
    <div class="col-lg-4">
        <label for="address">Address</label>
        <input type="text" class="form-control p-1" placeholder="Enter address" name="address" id="address" value="<?php echo htmlspecialchars($address) ?>">
    </div>
    <div class="col-lg-2">
        <label>&nbsp;</label>
        <button type="submit" class="btn btn-primary btn-sm w-100">Search</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-sm table-striped dataTable">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>Date</th>
            <th>Height</th>
            <th>Source</th>
            <th>Destination</th>
            <th>Message</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($messages)) { ?>
            <?php foreach($messages as $tx) { ?>
                <tr>
                    <td><?php echo explorer_tx_link($tx['id'], true) ?></td>
                    <td><?php echo display_date($tx['date']) ?></td>
                    <td><?php echo $tx['height'] ?></td>
                    <td><?php echo explorer_address_link($tx['src'], true) ?></td>
                    <td><?php echo explorer_address_link($tx['dst'], true) ?></td>
                    <td><?php echo htmlspecialchars($tx['message']) ?></td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="6">No messages found for this address.</td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php
require_once __DIR__ . '/../common/include/bottom.php';
?>
