<?php
require_once "/usr/local/cpanel/php/cpanel.php";
require_once "RedisManager.php";

$cpanel = new CPANEL();
$redisManager = new RedisManager($cpanel);

try {
    $action = $_GET['action'] ?? 'status';
    $username = $redisManager->username;
    $userdetails = $redisManager->userdetails;

    switch ($action) {
        case 'start':
            $redisManager->startRedis();
            break;
        case 'stop':
            $redisManager->stopRedis();
            break;
        case 'status':
            // Capture the output instead of printing it
            ob_start();
            $redisManager->checkRedisStatus();
            $status_output = ob_get_clean();
            break;
        default:
            throw new Exception("Invalid Action: $action");
    }

    if ($action == 'start' || $action == 'stop') {
        header("Location: index.live.php");
        exit;
    }

    // Fetch and display the status information
    if (!isset($status_output)) {
        ob_start();
        $redisManager->checkRedisStatus();
        $status_output = ob_get_clean();
    }

    $status_info = explode(' ', $status_output);

    if (count($status_info) >= 5 && strtolower($status_info[0]) == 'running') {
        $status = 'Running';
        $port = $status_info[1];
        $password = $status_info[2];
        $max_memory = $status_info[3];
        $databases = $status_info[4];
        $uninitiated = 'N/A';
    } else if (count($status_info) >= 1 && strtolower($status_info[0]) == 'uninitiated') {
        $status = 'Not Running (Never Started)';
        $port = 'N/A';
        $password = 'N/A';
        $max_memory = 'N/A';
        $databases = 'N/A';
        $uninitiated = $status_info[0];
    } else {
        $status = 'Not Running (Stopped)';
        $port = 'N/A';
        $password = 'N/A';
        $max_memory = 'N/A';
        $databases = 'N/A';
        $uninitiated = 'N/A';
    }

    $status_info = [
        'status' => $status,
        'port' => $port,
        'password' => $password,
        'ip' => '127.0.0.1',
        'user' => 'root',
        'max_memory' => $max_memory,
        'max_databases' => $databases,
        'uninitiated' => $uninitiated,
    ];

    $stylesheetsAndMetaTags = '<link rel="stylesheet" href="redis_style.css" charset="utf-8"/>';
    $cpanelHeader = str_replace('</head>', $stylesheetsAndMetaTags . '</head>', $cpanel->header("Redis Manager"));
    echo $cpanelHeader;

?>
    <div class="body-content">
        <hr>
        <br>
        <p><strong><a href="https://redis.io/">Redis</a></strong> is an open source (BSD licensed), an in-memory data store used by millions of developers as a cache, vector database, document database, streaming engine, and message broker. Redis has built-in replication and different levels of on-disk persistence. It supports complex data types (for example, strings, hashes, lists, sets, sorted sets, and JSON), with atomic operations defined on those data types.
        </p>
        <br>
        <br>
        <?php
        // For Debugging
        echo "<pre>";
        echo "<br>";
        if ($status_info['status'] == 'Running') {
            echo "Debugging Area: For Admin Only...";
            echo "<br>";
            echo "Running - " . "Port: " . $status_info['port'] . " | " . "Password: " . $status_info['password']  . " | " . "User: " . $status_info['user'];
        } else {
            echo "Debugging Area: For Admin Only...";
        }
        echo "<br>";
        echo "<br>";
        // print_r($status_output);
        // echo "<br>";
        // print_r($userdetails);
        echo "</pre>";
        ?>
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="header-section">
                    <img src="./redis_icon.webp" alt="Redis" width="50" />
                    <h4>Configuration:</h4>
                </div>
                <div class="status-section">
                    <?php if ($status_info['status'] == 'Running') : ?>
                        <p><strong>Status:</strong>
                            <font color="<?= $status_info['status'] == 'Running' ? 'green' : 'red' ?>"><?= $status_info['status'] ?></font>
                        </p>
                        <p><strong>IP:</strong> <?= $status_info['ip'] ?></p>
                        <p><strong>Port:</strong> <?= $status_info['port'] ?></p>
                        <p><strong>Password:</strong> <?= $status_info['password'] ?></p>
                        <p><strong>Maximum Memory:</strong> <?= $status_info['max_memory'] ?></p>
                        <p><strong>Maximum Databases:</strong> <?= $status_info['max_databases'] ?></p>
                    <?php elseif ($status_info['status'] == 'Not Running (Never Started)') : ?>
                        <p><strong>Status:</strong>
                            <font color="red">Not Running (Never Started)</font>
                        </p>
                        <p><strong>Message:</strong> Please click on the button below to <strong>Start Redis</strong>. For the first time, it may take upto a <font color="red">Few Minutes</font> to Start Redis for you.</p>
                    <?php else : ?>
                        <p><strong>Status:</strong>
                            <font color="red">Not Running</font>
                        </p>

                    <?php endif; ?>
                </div>
                <hr>
                <form method="get" class="form-inline">
                    <span class="developed-by">
                        Developed by <a href="https://www.linkedin.com/in/atikrahmanbd/" target="_blank"><strong>Atik Rahman</strong></a> with help from <a href="https://www.openai.com/chatgpt" target="_blank">ChatGPT</a>
                    </span>
                    <input type="hidden" name="action" value="<?= $status_info['status'] == 'Running' ? 'stop' : 'start' ?>">
                    <button class="btn <?= $status_info['status'] == 'Running' ? 'btn-danger' : 'btn-success' ?>" type="submit"><?= $status_info['status'] == 'Running' ? 'Stop Redis' : 'Start Redis' ?></button>
                </form>
            </div>
        </div>
    </div>

<?php
    print $cpanel->footer();
    $cpanel->end();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>