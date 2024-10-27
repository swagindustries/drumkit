# Configure DRUMKIT from env vars

_I'd recommend to configure DRUMKIT with a file rather than from env vars but this kind of configuration may be
convenient while working with dockers, this is mostly why it exists._

| Env var name                    | Example of value                  |
|---------------------------------|-----------------------------------|
| DRUMKIT_TLS_KEY                 | /ssl/mercure-router.local-key.pem |
| DRUMKIT_TLS_CERT                | /ssl/mercure-router.local.pem     |
| DRUMKIT_SECURITY_PUBLISHER_KEY  | !SomePasswordToBeChanged!         |
| DRUMKIT_SECURITY_SUBSCRIBER_KEY | !SomePasswordToBeChanged!         |
| DRUMKIT_SECURITY_PUBLISHER_ALG  | sha256                            |
| DRUMKIT_SECURITY_SUBSCRIBER_ALG | sha256                            |
| DRUMKIT_ACTIVE_SUBSCRIPTION     | 1                                 |
| DRUMKIT_HTTPS_PORT              | 443                               |
| DRUMKIT_HTTP_PORT               | 80                                |
| DRUMKIT_DEV                     | 1                                 |
