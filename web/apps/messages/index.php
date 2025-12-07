<?php
require_once dirname(__DIR__)."/apps.inc.php";
define("PAGE", true);
define("APP_NAME", "Messages");
require_once ROOT. '/web/apps/explorer/include/functions.php';

$search_blocks = isset($_GET['blocks']) ? intval($_GET['blocks']) : 1000;

function getAddressesWithMessages($limit) {
    global $db;
    $current_height = Block::getHeight();
    $start_height = $current_height - $limit;
    $sql = "SELECT src AS address FROM transactions WHERE type = 1 AND message != '' AND height >= :start_height AND src IS NOT NULL AND src != ''
            UNION
            SELECT dst AS address FROM transactions WHERE type = 1 AND message != '' AND height >= :start_height AND dst IS NOT NULL AND dst != ''
            ORDER BY address ASC";
    $rows = $db->run($sql, [":start_height" => $start_height]);
    return array_column($rows, 'address');
}

$addresses = getAddressesWithMessages($search_blocks);

require_once __DIR__. '/../common/include/top.php';
?>

<ol class="breadcrumb m-0 ps-0 h4">
    <li class="breadcrumb-item"><a href="/apps/explorer">Explorer</a></li>
    <li class="breadcrumb-item">Addresses with Messages</li>
</ol>

<form class="row mb-3" method="get" action="">
    <div class="col-lg-4">
        <label for="blocks">Search last blocks</label>
        <input type="text" class="form-control p-1" placeholder="Number of blocks" name="blocks" id="blocks" value="<?php echo htmlspecialchars($search_blocks) ?>">
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
            <th>Address</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($addresses)) { ?>
            <?php foreach($addresses as $address) { ?>
                <tr>
                    <td><a href="/apps/messages/address.php?address=<?php echo urlencode($address) ?>"><?php echo htmlspecialchars($address) ?></a></td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td>No addresses with messages found in the last <?php echo htmlspecialchars($search_blocks) ?> blocks.</td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php
require_once __DIR__ . '/../common/include/bottom.php';
?>
