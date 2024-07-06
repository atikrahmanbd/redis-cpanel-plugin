<?php

/**
 * Class RedisManager
 *
 * @category    	Plugin (cPanel)
 * @author          Atik Rahman <ar[at]atikrahman.com>
 * @version			v1.0
 * @link 			https://github.com/atikrahmanbd/redis-cpanel-plugin
 * @link			https://atikrahman.com
 * @license			http://www.apache.org/licenses/LICENSE-2.0
 */

class RedisManager
{
    private $cpanel;
    private $configDir;
    private $configFile;
    private $logDir;
    private $logFile;
    private $redisCli;
    private $userRedisDir;
    private $pidFile;
    public $homeDir;
    public $username;
    public $userdetails;

    public function __construct($cpanel)
    {
        $this->cpanel = $cpanel;
        $userData = $this->getAllUserData($cpanel);
        $this->userdetails = $userData;
        $this->username = $userData['main_domain']['user'];
        $this->homeDir = $userData['main_domain']['homedir'];
        $this->configDir = "{$this->homeDir}/.cpanel/plugin/redis";
        $this->configFile = "{$this->configDir}/redis.conf";
        $this->logDir = "{$this->configDir}/log";
        $this->logFile = "{$this->logDir}/{$this->username}.log";
        $this->userRedisDir = "{$this->homeDir}/.cpanel/plugin/redis/data";
        $this->pidFile = "{$this->configDir}/redis.pid";
        $this->redisCli = trim(shell_exec("which redis-cli"));
    }

    private function getAllUserData($cpanel)
    {
        $userData = $cpanel->uapi('DomainInfo', 'domains_data', array('format' => 'hash'));

        if ($userData['cpanelresult']['result']['status'] === 1) {
            return $userData['cpanelresult']['result']['data'];
        } else {
            throw new Exception('FAILED TO RETRIEVE USER DATA: ' . json_encode($userData['cpanelresult']['result']['errors']));
        }
    }

    private function log($message)
    {
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        file_put_contents($this->logFile, date('[Y-m-d H:i:s] ') . strtoupper($message) . PHP_EOL, FILE_APPEND);
    }

    private function findAvailablePort()
    {
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($sock === false) {
            throw new Exception('Unable to create socket: ' . socket_strerror(socket_last_error()));
        }

        if (!socket_bind($sock, '127.0.0.1', 0)) {
            throw new Exception('Unable to bind socket: ' . socket_strerror(socket_last_error($sock)));
        }

        if (!socket_listen($sock, 1)) {
            throw new Exception('Unable to listen on socket: ' . socket_strerror(socket_last_error($sock)));
        }

        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        if ($port) {
            return $port;
        } else {
            throw new Exception('Unable to find an available port');
        }
    }

    private function createRedisConfig()
    {
        if (!file_exists($this->configFile)) {
            $this->log("CREATING NEW REDIS CONFIG FOR {$this->username}");
            $password = bin2hex(random_bytes(8));

            if (!file_exists($this->configDir)) {
                mkdir($this->configDir, 0755, true);
            }
            file_put_contents($this->configFile, '');
            chown($this->configDir, $this->username);
            chmod($this->configFile, 0644);

            $port = $this->findAvailablePort();
            $config = [
                "bind 127.0.0.1",
                "port $port",
                "requirepass $password",
                "dir {$this->userRedisDir}",
                "pidfile {$this->pidFile}",
                "maxmemory 256mb",
                "databases 16",
            ];

            foreach ($config as $line) {
                file_put_contents($this->configFile, $line . PHP_EOL, FILE_APPEND);
            }

            if (!file_exists($this->userRedisDir)) {
                mkdir($this->userRedisDir, 0755, true);
            }
            chown($this->userRedisDir, $this->username);
        }
    }

    public function startRedis()
    {
        $this->createRedisConfig();
        $output = shell_exec("redis-server {$this->configFile} --daemonize yes 2>&1");
        $this->log("REDIS START COMMAND OUTPUT: $output");

        // Wait for the PID file to be created
        $timeout = 10; // seconds
        $start_time = time();
        while (!file_exists($this->pidFile) && (time() - $start_time) < $timeout) {
            sleep(1); // sleep for 1 second
        }

        // Check if the PID file was created
        if (file_exists($this->pidFile)) {
            $this->log("STARTED REDIS FOR {$this->username}");
            $this->addCronJob();
        } else {
            $this->log("FAILED TO START REDIS FOR {$this->username}: PID FILE NOT FOUND");
        }
    }

    public function stopRedis()
    {
        if (file_exists($this->pidFile)) {
            $pid = trim(file_get_contents($this->pidFile));
            if (posix_kill($pid, 15)) {
                unlink($this->pidFile);
                $this->log("STOPPED REDIS FOR {$this->username}");
                $this->removeCronJob();
            } else {
                $this->log("FAILED TO STOP REDIS FOR {$this->username}: UNABLE TO KILL PROCESS WITH PID $pid");
            }
        } else {
            $this->log("FAILED TO STOP REDIS FOR {$this->username}: PID FILE NOT FOUND");
        }
    }

    public function checkRedisStatus()
    {
        if (!file_exists($this->configFile)) {
            echo "UNINITIATED PLEASE CLICK ON THE BUTTON BELOW TO \"<STRONG>START REDIS<STRONG>\". FOR THE FIRST TIME, IT MAY TAKE UPTO A FEW MINUTES TO START REDIS FOR YOU.";
            $this->log("REDIS CONFIG FILE NOT FOUND FOR {$this->username}");
            return;
        }

        $port = trim(shell_exec("grep '^port' {$this->configFile} | awk '{print $2}'"));
        $password = trim(shell_exec("grep '^requirepass' {$this->configFile} | awk '{print $2}'"));
        $maxmemory = trim(shell_exec("grep '^maxmemory' {$this->configFile} | awk '{print $2}'"));
        $databases = trim(shell_exec("grep '^databases' {$this->configFile} | awk '{print $2}'"));

        if (file_exists($this->pidFile) && file_exists("/proc/" . trim(file_get_contents($this->pidFile)))) {
            echo "RUNNING $port $password $maxmemory $databases";
            $this->log("REDIS IS RUNNING FOR {$this->username} ON PORT $port");
        } else {
            echo "INACTIVE";
            $this->log("REDIS IS INACTIVE FOR {$this->username}");
        }
    }

    private function addCronJob()
    {
        $cronJob = "* * * * * /usr/bin/flock -n {$this->configDir}/redis.lock redis-server {$this->configFile} --daemonize yes >> /dev/null 2>&1";
        shell_exec("(crontab -l; echo \"$cronJob\") | crontab -");
        $this->log("ADDED CRON JOB FOR REDIS");
    }

    private function removeCronJob()
    {
        $cronJob = "* * * * * /usr/bin/flock -n {$this->configDir}/redis.lock redis-server {$this->configFile} --daemonize yes >> /dev/null 2>&1";
        shell_exec("crontab -l | grep -v '$cronJob' | crontab -");
        $this->log("REMOVED CRON JOB FOR REDIS");
    }
}
