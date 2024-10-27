DRUMKIT
=======

Mercure hub, in PHP.

How to run it
-------------

The easiest way is probably to run it in docker:

```
docker run \
    -e DRUMKIT_TLS_KEY=/ssl/mercure-router.local-key.pem \
    -e DRUMKIT_TLS_CERT=/ssl/mercure-router.local.pem \
    -e DRUMKIT_CORSORIGIN=mercure-router.local \
    -e DRUMKIT_SECURITY_PUBLISHER_KEY='!ChangeThisMercureHubJWTSecretKey!' \
    -e DRUMKIT_SECURITY_SUBSCRIBER_KEY='!ChangeThisMercureHubJWTSecretKey!' \
    -v ./ssl:/ssl \
    --rm -it nekdev/drumkit
```

Please notice that you must provide SSL certificates to run drumkit.

You may want to use mkcert to quickly generate certificates in local:

```bash
# Generate a certification and install it in your browsers
mkcert -install

# Create a certificate for mercure-router.local
# Change the value of the option corsOrigin to make it work with DRUMKIT
mkcert -cert-file ssl/mercure-router.local.pem -key-file ssl/mercure-router.local-key.pem "mercure-router.local"
```

Prepare dev environment
-----------------------

```bash
make configure-dev
composer install
# To avoid SSL issues, use this domain which is the one configured in the makefile
echo "127.0.0.1	mercure-router.local" | sudo tee --append /etc/host > /dev/null
```

Run it with:

```bash
./bin/drumkit \
    --tls-cert=ssl/mercure-router.local.pem \
    --tls-key=ssl/mercure-router.local-key.pem \
    --security-publisher-key='!ChangeThisMercureHubJWTSecretKey!' \
    --security-subscriber-key='!ChangeThisMercureHubJWTSecretKey!' \
    --corsOrigin=mercure-router.local \
    [--dev]
```

Then open https://mercure-router.local in your browser.

If you are running the command with `--dev` option, you should be redirected to
https://mercure-router.local/.well-known/mercure/ui/

:information_source: You can also use a file to configure DRUMKIT, see documentation for more information.

Roadmap for v1.0.0
------------------

- [] Support Redis as event storage
- [] Add no-ssl option (to make it possible to run it behinds a proxy easily)

Running in production
---------------------

Read this: https://amphp.org/production or use the docker implementation.

Backward compatibility promise
------------------------------

This project is NOT designed to be used as a library. It provides a single node mercure server.

This is why **no backward compatibility is provided** on any class ATM.

But you can expect no behavior change in minor version, including:
- Configuration files format
- Command options

This project follows [semver](https://semver.org/) and so we may break any of the previous statement on major version
learn more in the CHANGELOG.md file provided as well.
