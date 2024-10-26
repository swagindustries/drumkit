Drumkit
=======

Mercure hub, in PHP.

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

:information_source: You can also use a file to configure drumkit, see documentation for more information.

Roadmap for v1.0.0
------------------

- [] Support Redis as event storage

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
