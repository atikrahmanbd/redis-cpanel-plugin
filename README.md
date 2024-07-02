# Redis cPanel Plugin (redis-cpanel-plugin)

A cPanel Plugin For Redis Instances Controlled By Individual cPanel Users.

# Key Features:

**Individual Control:** Users can start or stop their own Redis instance (one per cPanel account).
**Enhanced Security:** Each user’s Redis instance includes unique authentication to safeguard against unauthorized access.
**Efficient Resource Use:** Redis instances employ the Least Recently Used (LRU) algorithm to optimize memory usage.

# Installation

To install the plugin, please do the following:

1. Login to your server via SSH
2. Clone the repo:

```
    git clone https://github.com/atikrahman/redis-cpanel-plugin.git
```

3. As root, do the following:

```
    cd ./redis-cpanel-plugin
    chmod +x install.sh
    ./install.sh
```

# Contributing

Any contribution is welcome. Please create pull requests that I'll review.

# License

Copyright [2024] [cPanel, Inc.]

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
