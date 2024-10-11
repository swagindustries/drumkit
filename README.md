Drumkit
=======

Mercure hub, in PHP.

Work in progress
----------------

This project is currently a work in progress, here is a list of the rest to be done:

- [x] Active subscriptions events
- [x] Active subscriptions API
- [ ] Security: JWS verifications (on all endpoints)
- [ ] Security: CORS configuration (on all endpoints)
- [x] Configuration by file
- [ ] Test suite
- [ ] Docker image
- [ ] Benchmark
- [x] Use new amphp version (with fibers)
- [x] Header `Last-Event-ID` https://mercure.rocks/spec#reconciliation
- [x] Redact help modal in UI
- [ ] Fix all TODO remaining in the code
- [x] Fix dependencies to something stable

Prepare dev environment
-----------------------

```
make configure-dev
composer install
# To avoid SSL issues, use this domain which is the one configured in the makefile
echo "127.0.0.1	mercure-router.local" | sudo tee --append /etc/host > /dev/null
```

Run it with:

```
./bin/drumkit --tls-cert=ssl/mercure-router.local.pem --tls-key=ssl/mercure-router.local-key.pem --security-publisher-key='!ChangeThisMercureHubJWTSecretKey!' --security-subscriber-key='!ChangeThisMercureHubJWTSecretKey!' [--dev]
```

Then open https://mercure-router.local in your browser.


Running in production
---------------------

Read this: https://amphp.org/production


Note about the original mercure
-------------------------------

To compare behavior with the official mercure distribution it may be interesting to run it. Here is the
procedure to achieve this.

Running it:

```
MERCURE_PUBLISHER_JWT_KEY='!ChangeMe!' \ 
MERCURE_SUBSCRIBER_JWT_KEY='!ChangeMe!' \
./mercure run -config Caddyfile.dev
```


```
docker run \
    -e MERCURE_PUBLISHER_JWT_KEY='!ChangeThisMercureHubJWTSecretKey!' \
    -e MERCURE_SUBSCRIBER_JWT_KEY='!ChangeThisMercureHubJWTSecretKey!' \
    -e MERCURE_EXTRA_DIRECTIVES='demo\
subscriptions'\
    -p 80:80 \
    -p 443:443 \
    dunglas/mercure
```

And go to https://localhost/.well-known/mercure/ui/

Backward compatibility promise
------------------------------

This project is NOT designed to be used as a library. It provides a single node mercure server.

This is why **no backward compatibility is provided** on any class ATM.

But you can expect no behavior change in minor version, including:
- Configuration files format
- Command options

This project follows [semver](https://semver.org/) and so we may break any of the previous statement on major version
learn more in the CHANGELOG.md file provided as well.
