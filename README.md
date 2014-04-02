Consumerr/php
=============


Main settings
--------------

| key               | default value                                | description                     |
|:------------------|:---------------------------------------------|:--------------------------------|
| `id`              | *(string)*                                   | Application identifier          |
| `secret`          | *(string)*                                   | Application secret code         |
| `url`             | *(string)* **https://service.consumerr.io/** | Url or IP address to API server |
| `sender`          | *(string)* **ConsumErr\Sender\PhpSender**    | Class who send data             |
| `exclude`         | *(array)*                                    | *Section*                       |
| `exclude` `ip`    | *(array)* **[]**                             | List of exclude IP address      |
| `exclude` `error` | *(array)* **[]**                             | List of exclude error types     |
| `cache`           | *(array)*                                    | *Section*                       |
| `cache` `enable`  | *(bool)* **FALSE**                           | Send multiple request at once   |

