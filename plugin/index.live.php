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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Redis Manager</title>
</head>

<body>
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
</body>

</html>