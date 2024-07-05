# Redis cPanel Plugin (redis-cpanel-plugin)

A cPanel Plugin that empowers individual cPanel users to control their own Redis instances seamlessly.

# Key Features:

![alt Plugin Feature Image](https://github.com/atikrahmanbd/redis-cpanel-plugin/blob/main/Redis_cPanel_Plugin_By_Atik.gif?raw=true)

**Individual Control:** Each cPanel user can independently start or stop their own Redis instance.  
**Enhanced Security:** Every Redis instance comes with unique authentication credentials to prevent unauthorized access.  
**Efficient Resource Use:** Utilizes Redis's Least Recently Used (LRU) algorithm to manage memory usage effectively.

## Prerequisites

- **Redis Installed:** Ensure Redis is installed on your server before using this plugin.

## Environment Tested

- cPanel 120.0.11 on AlmaLinux 9
- cPanel 120.0.11 on CloudLinux 9

## Installation

To install the plugin, follow these steps:

1. Login to your server via SSH.
2. Clone the repository:

```
git clone https://github.com/atikrahmanbd/redis-cpanel-plugin.git
```

3. As root, do the following:

```
cd ./redis-cpanel-plugin
chmod +x ./plugin/install.sh
./plugin/install.sh
```

# Usage

Once installed, cPanel user can manage their Redis instance through the cPanel interface.
Navigate to the Software section and click on Redis, from there you can start or stop the Redis instance with just a click.

# Contributing

I welcome contributions from the community! If you have suggestions, improvements, or bug fixes,
please create a pull request on GitHub. Make sure to follow the contribution guidelines in the repository.

# License

Copyright [2024] [cPanel, Inc.]

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

This software is distributed on an “AS IS” BASIS, WITHOUT WARRANTIES
OR CONDITIONS OF ANY KIND, either express or implied. See the License for the
specific language governing permissions and limitations under the License.

# Support

For support and troubleshooting, please visit the GitHub Issues page or contact me through email (ar[at]atikrahman.com).

# Acknowledgements

**Please verify that everything works perfectly in your environment before deploying to a production server. Use at your own risk.**

By using this plugin, you agree to the terms and conditions set forth in the License. Thank you for using Redis cPanel Plugin!
