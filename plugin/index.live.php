<?php
$action = $_GET['action'] ?? 'status';
$username = $_ENV['REMOTE_USER'];

function manageRedis($action, $username)
{
    $output = shell_exec("/usr/local/bin/manage_redis.sh $action $username 2>&1");
    return $output;
}

$result = manageRedis($action, $username);
$status_info = ['status' => 'inactive', 'port' => '', 'password' => ''];

if ($result) {
    if (strpos($result, 'running') !== false) {
        list($status, $port, $password) = explode(' ', trim($result));
        $status_info = [
            'status' => 'Running',
            'port' => $port,
            'password' => $password,
            'ip' => '127.0.0.1',
            'user' => $username,
            'max_memory' => '256MB',
            'max_databases' => '16',
        ];
    }
}

if ($action == 'start' || $action == 'stop') {
    header("Location: index.live.php");
    exit;
}
?>

<?php
require_once("/usr/local/cpanel/php/cpanel.php");

//Create new cpanel object to integrate.
$cpanel = new cPanel();

$stylesheetsAndMetaTags = '
    <link rel="stylesheet" href="style.css" charset="utf-8"/>
';

$cpanelHeader = str_replace('</head>', $stylesheetsAndMetaTags . '</head>', $cpanel->header("HostingSewa Redis"));
echo $cpanelHeader;

?>


<h1>Redis Manager By Atik</h1>
<p>Current Status: <?= $status_info['status'] ?></p>
<?php if ($status_info['status'] == 'Running') : ?>
    <p>IP: <?= $status_info['ip'] ?></p>
    <p>User: <?= $status_info['user'] ?></p>
    <p>Port: <?= $status_info['port'] ?></p>
    <p>Password: <?= $status_info['password'] ?></p>
    <p>Maximum Memory: <?= $status_info['max_memory'] ?></p>
    <p>Maximum Databases: <?= $status_info['max_databases'] ?></p>
<?php endif; ?>
<form method="get">
    <input type="hidden" name="action" value="<?= $status_info['status'] == 'Running' ? 'stop' : 'start' ?>">
    <button type="submit"><?= $status_info['status'] == 'Running' ? 'Stop Redis' : 'Start Redis' ?></button>
</form>


// New Design
<div id="content" class="cp-layout-main-content  cp-layout-main-content--with-main-menu   cp-layout-main-content--with-header ">
    <div class="container-lg">

        <!-- Banner Section - now controlled via a plugin -->
        <!-- banner -->
        <!-- banner -->

        <h1 class="page-header">
            <div id="pageHeading" class="page-title-section">
                <span class="page-title">Redis</span>
            </div>
        </h1>

        <div class="body-content">

            <hr>
            <p><strong><a href="https://redis.io/">Redis</a></strong> is an open source (BSD licensed), in-memory data structure store, used as a database,
                cache and message broker. It supports data structures such as strings, hashes, lists, sets, sorted sets with range queries, bitmaps, hyperloglogs
                and geospatial indexes with radius queries. Redis has built-in replication, Lua scripting, LRU eviction, transactions and different levels of on-disk
                persistence.
            </p>

            <div class="panel panel-default">
                <div class="panel-body">
                    Current SCnfiguration: <br>
                    <hr>Current Status: <font color="green">Running</font> <br>IP: 127.0.0.1<br>Port: 55243<br>Password: tpaqVymYNqNykAcBjZF<br>Maximum Memory: 128mb<br>Maximum Databases 16<br>
                    <hr>
                    <form method="post">
                        <input type="hidden" id="status" name="status" value="Stop">
                        <input class="btn btn-danger" id="remove" type="submit" value="Stop">
                    </form>

                </div>
            </div>


        </div><!-- end body-content -->
        <!-- PAGE TEMPLATE'S CONTENT END -->

    </div>
</div>


<?php
print $cpanel->footer();

$cpanel->end();
?>